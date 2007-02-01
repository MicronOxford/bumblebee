<?php
/**
* Base class for viewing and editing booking information
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Actions
*
* path (bumblebee root)/inc/actions/viewbase.php
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

require_once 'inc/bb/configreader.php';

/** date maniuplation objects */
require_once 'inc/date.php';
/** URL manipulation/generation functions */
require_once 'inc/menu.php';
/** parent object */
require_once 'inc/actions/actionaction.php';

/**
* Base class for booking and viewing bookings
* @package    Bumblebee
* @subpackage Actions
*/
class ActionViewBase extends ActionAction {

  /** @var integer   instrument id number being viewed/booked etc */
  var $instrument;
  /** @var array     row of data representing the database entry for the instrument */
  var $row;
  /** @var integer   maximum number of days into the future that bookings can be made */
  var $maxFutureDays = 0;

  /**
  * Initialising the class
  *
  * @param  BumblebeeAuth $auth  Authorisation object
  * @param  array $pdata   extra state data from the call path
  * @return void nothing
  */
  function ActionViewBase($auth, $PDATA) {
    parent::ActionAction($auth, $PDATA);
    $this->mungeInputData();
  }

  function mungeInputData() {
    parent::mungeInputData();
    $this->instrument = issetSet($this->PD, 'instrid',0);
  }

  /**
  * Set flags according to the permissions of the logged in user
  */
  function _checkBookingAuth($userid) {
    $this->_isOwnBooking = $this->auth->isMe($userid);
    $this->_haveWriteAccess = $this->_isOwnBooking
                              || $this->auth->permitted(BBROLE_MAKE_BOOKINGS, $this->instrument);
  }

  /**
  * Polite "go away" message if someone tries to delete a booking that they can't
  */
  function _forbiddenError($msg) {
    $this->log('Action forbidden: '.$msg);
    echo $this->reportAction(STATUS_FORBIDDEN,
              array(
                  STATUS_FORBIDDEN => $msg.': <br/>'.T_('Sorry, you do not have permission to do this.'),
              )
            );
    return STATUS_FORBIDDEN;
  }

  /**
  * Calculate calendar offset in days
  *
  * calculates the number of days between the current date and the last date that was selected
  * by the user (in making a booking etc)
  * @return string URL string for inclusion in href (examples: '28' '-14')
  */
  function _offset() {
    $now = new SimpleDate(time());
    if (isset($this->PD['isodate'])) {
      $then = new SimpleDate($this->PD['isodate']);
      return floor($then->dsDaysBetween($now));
    } elseif (isset($this->PD['startticks'])) {
      $then = new SimpleDate($this->PD['startticks']);
      return $then->dateString();
    } elseif (isset($this->PD['bookwhen-date'])) {
      $then = new SimpleDate($this->PD['bookwhen-date']);
      return floor($then->dsDaysBetween($now));
    }
  }

  /**
  * Makes a link back to the current calendar
  *
  * @return string URL string for link back to calendar view
  */
  function _calendarViewLink($instrument) {
    return '<br /><br /><a href="'.
        makeURL('calendar', array('instrid'=>$instrument, 'caloffset'=>$this->_offset()))
      .'">'.T_('Return to calendar view') .'</a>';
  }

  /**
  * Display a heading on the page with the instrument name and location
  */
  function displayInstrumentHeader() {
    $name = '';
    $location = '';
    if (is_array($this->instrument) && count($this->instrument) > 1) {
      $name = T_('Combined instrument display');
      $names = array();
      foreach ($this->instrument as $i) {
        $names[] = sprintf(T_('%s (%s)'), $this->row[$i]['longname'], $this->row[$i]['location']);
      }
      $location = join(', ', $names);
    } else {
      $name = $this->row['longname'];
      $location = $this->row['location'];
    }
    $t = '<h2 class="instrumentname">'
        .xssqw($name)
        .'</h2>'
        .'<p class="instrumentlocation">'
        .xssqw($location)
        .'</p>'."\n";
    $t .= $this->_instrumentNotes(false);
    return $t;
  }

  /**
  * Display a footer for the page with the instrument comments and who looks after the instrument
  */
  function displayInstrumentFooter() {
    $t = '';
    $t .= $this->_instrumentNotes($this->row, true);
    if ($this->row['supervisors']) {
      $t .= '<h3>'.T_('Instrument supervisors').'</h3>';
      $t .= '<ul>';
      foreach(preg_split('/,\s*/', $this->row['supervisors']) as $username) {
        $user = quickSQLSelect('users', 'username', $username);
        $t .= '<li><a href="mailto:'. xssqw($user['email']) .'">'. xssqw($user['name']) .'</a></li>';
      }
      $t .= '</ul>';
    }
    return $t;
  }

  /**
  * Display the instrument comment in either header or footer as configured
  *
  * @param boolean $footer   called in the footer
  * @returns string          header/footer to display for notes section
  */
  function _instrumentNotes($footer=true) {
    $conf = ConfigReader::getInstance();
    $t = '';
    $notesbottom = $conf->value('calendar', 'notesbottom', true);
    if ($notesbottom == $footer && $this->row['calendarcomment']) {
      $t = '<div class="calendarcomment">'
          .'<p>'
          .preg_replace("/\n+/",
                        '</p><p>',
                          xssqw_relaxed($this->row['calendarcomment']))
          .'</p></div>';
    }
    return $t;
  }


  /**
  * Check if a date is within the permitted booking period for this user on this instrument.
  *
  * @param  SimpleDate   date to be checked
  * @returns   1 if date is within permitted calendar period, returns 0 or number of days (negative) that the booking is beyond the end of the calendar
  */
  function BookingPastNormalCalendar($date, $calcOffset=0) {
    #$this->DEBUG = 10;
    $today = new SimpleDate(time());
    $today->dayRound();
    $totaloffset = $date->dsDaysBetween($today);

    if ($this->maxFutureDays == 0) {
      $this->maxFutureDays = $this->row['calfuture'] + 7 - $today->dow();
    }

    #printf ('today = %s, checkDate = %s, ', $today->dateTimeString(), $date->dateTimeString());
    $this->log("Found total offset of $totaloffset, {$this->maxFutureDays}");

    $offset = floor($this->maxFutureDays - $totaloffset + $calcOffset);
    if ($offset <= 0) {
      return $offset;
    }

    return 1;
  }

  function MakeBookingHref($date) {
    if ($this->MakeBookingPermitted($date)) {
      return makeURL('book',        array('instrid'=>$this->instrument));
    } else {
      return makeURL('bookcontact', array('instrid'=>$this->instrument));
    }
  }

  function MakeBookingPermitted($date) {
    $permission = $this->BookingPastNormalCalendar($date, 1) < 0
                      ? BBROLE_MAKE_BOOKINGS_FUTURE
                      : BBROLE_MAKE_BOOKINGS;
    return $this->auth->permitted($permission, $this->instrument);
  }

  function ViewCalendarPermitted($offset) {
    if (type_is_a($offset, 'SimpleDate')) {
      $date = $offset;
    } else {
      $date = new SimpleDate(time());
      $date->addDays($offset);
    }
    #echo "Checking calendar for ". $date->dateTimeString();
    $permission = $this->BookingPastNormalCalendar($date) < 0
                      ? BBROLE_VIEW_CALENDAR_FUTURE
                      : BBROLE_VIEW_CALENDAR;
    return $this->auth->permitted($permission, $this->instrument);
  }


  function _Forbidden($message=null) {
    if ($message == null) {
      $message = T_('Sorry, you are not permitted to do that with this instrument.');
    }
    echo $this->reportAction(STATUS_FORBIDDEN,
                array(
                  STATUS_FORBIDDEN => $message
                )
              );
  }

}
?>
