<?php
/**
* View a bookings calendar and make bookings
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

require_once 'inc/bb/configreader.php';

/** calendar object */
require_once 'inc/bb/calendar.php';
/** date maniuplation objects */
require_once 'inc/date.php';
/** parent object */
require_once 'inc/actions/viewbase.php';

/**
* View a bookings calendar and make bookings
* @package    Bumblebee
* @subpackage Actions
*/
class ActionCalendar extends ActionViewBase {
  /** @var boolean   logged in user can modify booking  */
  var $_haveWriteAccess = false;

  /**
  * Initialising the class
  *
  * @param  BumblebeeAuth $auth  Authorisation object
  * @param  array $pdata   extra state data from the call path
  * @return void nothing
  */
  function ActionCalendar($auth, $PDATA) {
    parent::ActionViewBase($auth, $PDATA);
    $this->mungeInputData();
  }

  function go() {
    if (! isset($this->PD['instrid'])
          || $this->PD['instrid'] < 1
          || $this->PD['instrid'] == '') {
      $err = 'Invalid action specification in actions/calendar.php::go(): no instrument specified: received "' . xssqw($this->PD['instrid']) .'"';
      $this->log($err);
      trigger_error($err, E_USER_WARNING);
      return;
    }

    if (! $this->auth->permitted(BBROLE_VIEW_CALENDAR, $this->instrument)) {
      $this->_viewCalendarForbidden();
      return;
    }


    $this->row = quickSQLSelect('instruments', 'id', $this->instrument);

    if (isset($this->PD['isodate']) &&
                    ! isset($this->PD['bookid']) && ! isset($this->PD['startticks']) ) {
      $this->instrumentDay();
      echo $this->_calendarViewLink($this->instrument);
      echo "<br /><br /><a href='".makeURL('view')."'>". T_('Return to instrument list') ."</a>";
    } else {
      $this->instrumentMonth();
      echo "<br /><br /><a href='".makeURL('view')."'>". T_('Return to instrument list') ."</a>";
    }
  }

  function mungeInputData() {
    parent::mungeInputData();
    if (isset($this->PD['caloffset']) && preg_match("/\d\d\d\d-\d\d-\d\d/", $this->PD['caloffset'])) {
      $then = new SimpleDate($this->PD['caloffset']);
      $now = new SimpleDate(time());
      $this->PD['caloffset'] = floor($then->dsDaysBetween($now));
    }
    echoData($this->PD, 0);
  }

  /**
  * Display the monthly calendar for the selected instrument
  */
  function instrumentMonth() {
    $conf = ConfigReader::getInstance();
    // Show a window $row['calendarlength'] weeks long starting $row['calendarhistory'] weeks
    // before the current date. Displayed week starts on configured day of week.
    $offset = issetSet($this->PD, 'caloffset');
    if ($offset == 'today') $offset = 0;

    $now = new SimpleDate(time());
    $now->dayRound();
    $start = clone($now);
    $start->addDays($offset);

    $callength = 7 * $this->row['callength'];

    $week_offset = $conf->value('language', 'week_offset', 1);
    $start->weekRound();
    if ($now->dow() < $week_offset) $start->addDays(-7);
    $start->addDays($week_offset - 7*$this->row['calhistory']);

    $stop = clone($start);
    $stop->addDays($callength);

    // check to see if this is an allowable calendar view (not too far into the future)
    $futureView = $this->BookingPastNormalCalendar($stop);

    if ($futureView < 0 && ! $this->auth->permitted(BBROLE_VIEW_CALENDAR_FUTURE, $this->instrument)) {
      $off = ceil($futureView / 7) * 7;
      $start->addDays($off);
      $stop->addDays($off);
      $offset += $off;
    }

//     $totaloffset = $offset + $callength - 7*$row['calhistory'] - $start->dow();
//     $this->log("Found total offset of $totaloffset, calfuture=".$row['calfuture']." calhistory=".$row['calhistory']);
//
//     //admin users are allowed to see further into the future.
//     $this->_checkBookingAuth(-1);
//     $futureView = false;
//     echo  ! ($this->auth->permitted(BBROLE_VIEW_CALENDAR_FUTURE, $this->instrument));
//     if (($totaloffset > $row['calfuture']) &&
//         ! ($this->auth->permitted(BBROLE_VIEW_CALENDAR_FUTURE, $this->instrument))) {
//       #echo "Found total offset of $totaloffset, but only ".$row['calfuture']." is permitted. ";
//       $start = clone($now);
//       $offset = $row['calfuture'] - $callength + 7*$row['calhistory'] + 7;
//       $start->addDays($offset);
//       #echo $start->dateTimeString();
//       $futureView = true;
//     }

    // jump backwards to the start of that week.
    //$day = $start->dow(); // the day of the week, 0=Sun, 6=Sat

    $cal = new Calendar($start, $stop, $this->instrument);

    $daystart    = new SimpleTime($this->row['usualopen']);
    $daystop     = new SimpleTime($this->row['usualclose']);
    //configure the calendar view granularity (not the same as booking granularity)
    $granularity = $this->row['calprecision'];
    $timelines   = $this->row['caltimemarks'];
    $cal->setTimeSlotPicture($this->row['timeslotpicture']);
    #$granularity = 60*60;
//     echo $cal->display();

/*    if ($this->auth->permitted($futureView<-$callength ? BBROLE_MAKE_BOOKINGS_FUTURE : BBROLE_MAKE_BOOKINGS, $this->instrument)) {
      $cal->bookhref = makeURL('book', array('instrid'=>$this->instrument));
    } else {
      $cal->bookhref = makeURL('bookcontact', array('instrid'=>$this->instrument));
    }*/
    $cal->bookhrefCallback = array(&$this, 'MakeBookingHref');

    $cal->zoomhref = makeURL('calendar', array('instrid'=>$this->instrument));

    $cal->freeBusyOnly = ! $this->auth->permitted(BBROLE_VIEW_BOOKINGS, $this->instrument);
    $cal->isAdminView = $this->auth->permitted(BBROLE_MAKE_BOOKINGS_FREE, $this->instrument);
    $cal->setOutputStyles('', $conf->value('calendar', 'todaystyle'),
                preg_split('{/}',$conf->value('calendar', 'monthstyle')), 'm');
    echo $this->displayInstrumentHeader();
    echo $this->_linksForwardBack($start,
                                  ($offset-$callength),
                                  ($offset+$callength),
                                  $callength);
    echo $cal->displayMonthAsTable($daystart,$daystop,$granularity,$timelines);
    echo $this->displayInstrumentFooter();
  }

  /**
  * Generate back | today | forward links for the calendar
  * @return string html for links
  */
  function _linksForwardBack($centre, $back, $forward, $calLength, $extra=array()) {
    $links = array();
    $script = 'JumpCalendarTo';
    $today = new SimpleDate(time());
    $today->dayRound();
    $centreOffset = $today->dsDaysBetween($centre);

    $links[] = '<a href="'
                  .makeURL('calendar',
                            array_merge(
                            array('instrid'=>$this->instrument, 'caloffset'=>$back),
                            $extra)
                           )
                .'">&laquo; '. T_('earlier') .'</a>';

    $links[] = $this->_makeQuickJumpLinks(T_('jump back to:'), -1, $calLength, $centre, $centreOffset, $script);

    $links[] = '<a href="'
                  .makeURL('calendar',
                          array_merge(
                              array('instrid'=>$this->instrument, 'caloffset'=>'today'),
                              $extra)
                           )
              .'">'. T_('today') .'</a> ';

    if ($this->ViewCalendarPermitted($forward)) {
      $select = $this->_makeQuickJumpLinks(T_('jump forward to:'), 1, $calLength, $centre, $centreOffset, $script);

      if ($select !== '') $links[] = $select;

      $links[] = '<a href="'
                  .makeURL('calendar',
                          array_merge(
                              array('instrid'=>$this->instrument, 'caloffset'=>$forward),
                              $extra)
                           )
                  .'">'. T_('later') .' &raquo;</a>';
    }

    $linkList = join($links, ' | ');
    $location = makeURL('calendar',
                        array_merge(
                            array('instrid'=>$this->instrument),
                            $extra),
                         false);
    $js = "
          <script type='text/javascript'>
            function $script(offset) {
              newLocation = '$location' + '&caloffset=' + offset;
              document.location.href = newLocation;
            }
          </script>";
    return '<div style="text-align:center">' . $js . $linkList . '</div>';
  }


  function _makeQuickJumpLinks($caption, $direction, $calLength, $centre, $centreOffset, $script) {
    $nonEmpty = false;
    $backJumps = array('<option value="0">'. $caption .'</option>');
    for ($i=1; $i <= 20; $i++) {
      $offset = $direction * $i * $calLength;
      $offsetDate = clone($centre);
      $offsetDate->addDays($offset);
      $offset -= $centreOffset;
      if ($direction < 0 || $this->ViewCalendarPermitted($offset)) {
        $nonEmpty = true;
        $date = $offsetDate->dateString();
        $backJumps[] = "<option value='$offset'>$date</option>";
      } else {
        break;
      }
    }
    if ($nonEmpty) {
      return "<select name='backLink' onChange='$script(this.value)'>"
                  .join($backJumps, "\n")
                  .'</select>';
    } else {
      return '';
    }
  }

  /**
  * Display a single day's calendar for the selected instrument
  *
  * @todo ///TODO: combine this function with instrumentMonth to reduce duplication
  */
  function instrumentDay() {
    $granularity = $this->row['calprecision'];
    $timelines   = $this->row['caltimemarks'];

    $offset = issetSet($this->PD, 'caloffset');
    $today = new SimpleDate(time());
    $today->dayRound();

    if ($offset == 'today') {
      $start = clone($today);
      $offset = 0;
    } else {
      $start = new SimpleDate($this->PD['isodate']);
      $start->dayRound();
      $start->addDays($offset);
    }

    $stop = clone($start);
    $stop->addDays(1);

    // check to see if this is an allowable calendar view (not too far into the future)
    $futureView = $this->BookingPastNormalCalendar($start);

    if ($futureView < 0 && ! $this->auth->permitted(BBROLE_VIEW_CALENDAR_FUTURE, $this->instrument)) {
      $start->addDays($futureView);
      $stop->addDays($futureView);
      $offset += $futureView;
    }


/*
    $totaloffset = $start->daysBetween($today);
    $maxfuture = $row['calfuture'] + 7 - $today->dow();
    //admin users are allowed to see further into the future.
    $this->_checkBookingAuth(-1);
    $this->log("Found total offset of $totaloffset, $maxfuture");

    $futureView = false;
    if ($totaloffset > $maxfuture && ! $this->auth->permitted(BBROLE_VIEW_CALENDAR_FUTURE, $this->instrument)) {
      #echo "Found total offset of $totaloffset, but only ".$row['calfuture']." is permitted. ";
      $delta = $maxfuture-$totaloffset;
      #echo "Changing offset by $delta\n";
      $start->addDays($delta);
      $futureView = true;
    }*/
    $cal = new Calendar($start, $stop, $this->instrument);
    $cal->setTimeSlotPicture($this->row['timeslotpicture']);

    /// FIXME: get this from the instrument table?
    $daystart    = new SimpleTime('00:00:00');
    $daystop     = new SimpleTime('24:00:00');
//     echo $cal->display();
    $cal->bookhrefCallback = array(&$this, 'MakeBookingHref');

    $cal->freeBusyOnly = ! $this->auth->permitted(BBROLE_VIEW_BOOKINGS, $this->instrument);
    $cal->isAdminView = $this->auth->permitted(BBROLE_MAKE_BOOKINGS_FREE, $this->instrument);
    $cal->setOutputStyles('', 'caltoday', array('monodd', 'moneven'), 'm');
    echo $this->displayInstrumentHeader();
    echo $this->_linksForwardBack($start,
                                  '-1', '+1', 1,
                                  array('isodate'=>$start->dateString()));
    echo $cal->displayDayAsTable($daystart,$daystop,$granularity,$timelines);
    echo $this->displayInstrumentFooter();
  }

  function _viewCalendarForbidden() {
    $this->_Forbidden(T_('Sorry, you are not permitted to view this instrument\'s calendar.'));
  }

} // class ActionCalendar
?>
