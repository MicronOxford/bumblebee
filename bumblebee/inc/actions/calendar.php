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
  /**
  * logged in user can modify booking
  * @var boolean
  */
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
    global $CONFIG;
    $row = quickSQLSelect('instruments', 'id', $this->instrument);
    // Show a window $row['calendarlength'] weeks long starting $row['calendarhistory'] weeks
    // before the current date. Displayed week always starts on Monday
    $offset = issetSet($this->PD, 'caloffset');
    if ($offset == 'today') $offset = 0;
    $now = new SimpleDate(time());
    $now->dayRound();
    $start = clone($now);
    $start->addDays($offset);

    // check to see if this is an allowable calendar view (not too far into the future)
    $futureView = $this->BookingPastNormalCalendar($row, $start, 7*($row['callength']-$row['calhistory']));

    if ($futureView < 0 && ! $this->auth->permitted(BBROLE_VIEW_CALENDAR_FUTURE, $this->instrument)) {
      $start->addDays($futureView);
      $offset += $futureView;
    }

     $callength = 7 * $row['callength'];
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
    $week_offset = issetSet($CONFIG['language'], 'week_offset', 1);
    $start->weekRound();
    if ($now->dow() < $week_offset) $start->addDays(-7);
    $start->addDays($week_offset - 7*$row['calhistory']);

    $stop = clone($start);
    $stop->addDays($callength);

    $cal = new Calendar($start, $stop, $this->instrument);

    $daystart    = new SimpleTime($row['usualopen']);
    $daystop     = new SimpleTime($row['usualclose']);
    //configure the calendar view granularity (not the same as booking granularity)
    $granularity = $row['calprecision'];
    $timelines   = $row['caltimemarks'];
    $cal->setTimeSlotPicture($row['timeslotpicture']);
    #$granularity = 60*60;
//     echo $cal->display();

    if ($this->auth->permitted($futureView<-$callength ? BBROLE_MAKE_BOOKINGS_FUTURE : BBROLE_MAKE_BOOKINGS, $this->instrument)) {
      $cal->bookhref = makeURL('book', array('instrid'=>$this->instrument));
    } else {
      $cal->bookhref = makeURL('bookcontact', array('instrid'=>$this->instrument));
    }
    $cal->zoomhref = makeURL('calendar', array('instrid'=>$this->instrument));

    $cal->freeBusyOnly = ! $this->auth->permitted(BBROLE_VIEW_BOOKINGS, $this->instrument);
    $cal->isAdminView = $this->auth->permitted(BBROLE_MAKE_BOOKINGS_FREE, $this->instrument);
    $cal->setOutputStyles('', $CONFIG['calendar']['todaystyle'],
                preg_split('{/}',$CONFIG['calendar']['monthstyle']), 'm');
    echo $this->displayInstrumentHeader($row);
    echo $this->_linksForwardBack(($offset-$callength),
                                  ($offset+$callength),
                                  ($futureView>0) || $this->auth->permitted(BBROLE_VIEW_CALENDAR_FUTURE,
                                  $this->instrument));
    echo $cal->displayMonthAsTable($daystart,$daystop,$granularity,$timelines);
    echo $this->displayInstrumentFooter($row);
  }

  /**
  * Generate back | today | forward links for the calendar
  * @return string html for links
  */
  function _linksForwardBack($back, $forward, $showForward=true, $extra=array()) {
    return '<div style="text-align:center">'
        .'<a href="'.makeURL('calendar', array_merge(array('instrid'=>$this->instrument, 'caloffset'=>$back), $extra)).'">&laquo; '. T_('earlier') .'</a> | '
        .'<a href="'.makeURL('calendar', array_merge(array('instrid'=>$this->instrument, 'caloffset'=>'today'), $extra)).'">'. T_('today') .'</a> '
        .($showForward ?
                ' | '
                .'<a href="'.makeURL('calendar', array_merge(array('instrid'=>$this->instrument, 'caloffset'=>$forward), $extra)).'">'. T_('later') .' &raquo;</a>'
                : ''
          )
        .'</div>';
  }

  /**
  * Display a single day's calendar for the selected instrument
  *
  * @todo ///TODO: combine this function with instrumentMonth to reduce duplication
  */
  function instrumentDay() {
    $row = quickSQLSelect('instruments', 'id', $this->instrument);
    $granularity = $row['calprecision'];
    $timelines   = $row['caltimemarks'];

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



    // check to see if this is an allowable calendar view (not too far into the future)
    $futureView = $this->BookingPastNormalCalendar($row, $start, 1);

    if ($futureView < 0 && ! $this->auth->permitted(BBROLE_VIEW_CALENDAR_FUTURE, $this->instrument)) {
      $start->addDays($futureView);
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
    $stop = clone($start);
    $stop->addDays(1);
    $cal = new Calendar($start, $stop, $this->instrument);
    $cal->setTimeSlotPicture($row['timeslotpicture']);

    /// FIXME: get this from the instrument table?
    $daystart    = new SimpleTime('00:00:00');
    $daystop     = new SimpleTime('23:59:59');
//     echo $cal->display();
    if ($this->auth->permitted($futureView ? BBROLE_MAKE_BOOKINGS_FUTURE : BBROLE_MAKE_BOOKINGS, $this->instrument)) {
      $cal->bookhref = makeURL('book', array('instrid'=>$this->instrument));
    } else {
      $cal->bookhref = makeURL('bookcontact', array('instrid'=>$this->instrument));
    }

    $cal->freeBusyOnly = ! $this->auth->permitted(BBROLE_VIEW_BOOKINGS, $this->instrument);
    $cal->isAdminView = $this->auth->permitted(BBROLE_MAKE_BOOKINGS_FREE, $this->instrument);
    $cal->setOutputStyles('', 'caltoday', array('monodd', 'moneven'), 'm');
    echo $this->displayInstrumentHeader($row);
    echo $this->_linksForwardBack('-1', '+1',
                                ($futureView>0) || $this->auth->permitted(BBROLE_VIEW_CALENDAR_FUTURE, $this->instrument),
                                array('isodate'=>$start->dateString()));
    echo $cal->displayDayAsTable($daystart,$daystop,$granularity,$timelines);
    echo $this->displayInstrumentFooter($row);
  }


  function BookingPastNormalCalendar($row, $start, $length) {
    //$this->DEBUG = 10;

    $today = new SimpleDate(time());
    $today->dayRound();
    $totaloffset = $start->daysBetween($today);
    $maxfuture = $row['calfuture'] + 7 - $today->dow() - $length;

    $this->log("Found total offset of $totaloffset, $maxfuture");

    if ($totaloffset >= $maxfuture) {
      return floor($maxfuture - $totaloffset);
    }

    return 1;
  }

} // class ActionCalendar
?>
