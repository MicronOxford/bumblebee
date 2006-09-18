<?php
/**
* View a list of instruments that are available
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Actions
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

/** date maniuplation objects */
require_once 'inc/date.php';
/** parent object */
require_once 'inc/actions/actionaction.php';

/**
* View a list of instruments so the user can view and make bookings
* @package    Bumblebee
* @subpackage Actions
*/
class ActionViewBase extends ActionAction {

  /**
  * logged in user has admin view of booking/calendar
  * @var boolean
  */
  var $_isAdminView     = false;

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

  /**
  * Set flags according to the permissions of the logged in user
  * @todo //TODO: replace with new permissions system
  */
  function _checkBookingAuth($userid) {
    $this->_isOwnBooking = $this->auth->isMe($userid);
    $this->_isAdminView = $this->auth->isSystemAdmin()
                  || $this->auth->isInstrumentAdmin($this->PD['instrid']);
    $this->_haveWriteAccess = $this->_isOwnBooking || $this->_isAdminView;
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
  function displayInstrumentHeader($row) {
    $t = '<h2 class="instrumentname">'
        .xssqw($row['longname'])
        .'</h2>'
       .'<p class="instrumentlocation">'
       .xssqw($row['location']).'</p>'."\n";
    $t .= $this->_instrumentNotes($row, false);
    return $t;
  }

  /**
  * Display a footer for the page with the instrument comments and who looks after the instrument
  */
  function displayInstrumentFooter($row) {
    $t = '';
    $t .= $this->_instrumentNotes($row, true);
    if ($row['supervisors']) {
      $t .= '<h3>'.T_('Instrument supervisors').'</h3>';
      $t .= '<ul>';
      foreach(preg_split('/,\s*/', $row['supervisors']) as $username) {
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
  * @param array $row        instrument db row
  * @param boolean $footer   called in the footer
  * @returns string          header/footer to display for notes section
  *
  * @global array system config
  */
  function _instrumentNotes($row, $footer=true) {
    global $CONFIG;
    $t = '';
    $notesbottom = issetSet($CONFIG['calendar'], 'notesbottom', true);
    if ($notesbottom == $footer && $row['calendarcomment']) {
      $t = '<div class="calendarcomment">'
          .'<p>'
          .preg_replace("/\n+/",
                        '</p><p>',
                          xssqw_relaxed($row['calendarcomment']))
          .'</p></div>';
    }
    return $t;
  }

}
?>
