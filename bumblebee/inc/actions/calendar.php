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
    $instrument = $this->PD['instrid'];
    if (isset($this->PD['isodate']) &&
                    ! isset($this->PD['bookid']) && ! isset($this->PD['startticks']) ) {
      $this->instrumentDay();
      echo $this->_calendarViewLink($instrument);
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
    $row = quickSQLSelect('instruments', 'id', $this->PD['instrid']);
    // Show a window $row['calendarlength'] weeks long starting $row['calendarhistory'] weeks
    // before the current date. Displayed week always starts on Monday
    $offset = issetSet($this->PD, 'caloffset');
    $now = new SimpleDate(time());
    $now->dayRound();
    $start = clone($now);
    $start->addDays($offset);

    // check to see if this is an allowable calendar view (not too far into the future)
    $callength = 7*$row['callength'];
    $totaloffset = $offset + $callength - 7*$row['calhistory'] - $start->dow();
    $this->log("Found total offset of $totaloffset, calfuture=".$row['calfuture']." calhistory=".$row['calhistory']);

    //admin users are allowed to see further into the future.
    $this->_checkBookingAuth(-1);
    if ($totaloffset > $row['calfuture'] && !$this->_isAdminView) {
      #echo "Found total offset of $totaloffset, but only ".$row['calfuture']." is permitted. ";
      $start = clone($now);
      $offset = $row['calfuture'] - $callength + 7*$row['calhistory'] + 7;
      $start->addDays($offset);
      #echo $start->dateTimeString();
    }

    // jump backwards to the start of that week.
    //$day = $start->dow(); // the day of the week, 0=Sun, 6=Sat
    $week_offset = issetSet($CONFIG['language'], 'week_offset', 1);
    $start->weekRound();
    if ($now->dow() < $week_offset) $start->addDays(-7);
    $start->addDays($week_offset-7*$row['calhistory']);

    $stop = clone($start);
    $stop->addDays($callength);

    $cal = new Calendar($start, $stop, $this->PD['instrid']);

    $daystart    = new SimpleTime($row['usualopen']);
    $daystop     = new SimpleTime($row['usualclose']);
    //configure the calendar view granularity (not the same as booking granularity)
    $granularity = $row['calprecision'];
    $timelines   = $row['caltimemarks'];
    $cal->setTimeSlotPicture($row['timeslotpicture']);
    #$granularity = 60*60;
//     echo $cal->display();
    $cal->bookhref = makeURL('book', array('instrid'=>$this->PD['instrid']));
    $cal->zoomhref = makeURL('calendar', array('instrid'=>$this->PD['instrid']));
    $cal->isAdminView = $this->_isAdminView;
    $cal->setOutputStyles('', $CONFIG['calendar']['todaystyle'],
                preg_split('{/}',$CONFIG['calendar']['monthstyle']), 'm');
    echo $this->displayInstrumentHeader($row);
    echo $this->_linksForwardBack(($offset-$callength),
                                  0,($offset+$callength),
                                  $totaloffset <= $row['calfuture'] || $this->_isAdminView);
    echo $cal->displayMonthAsTable($daystart,$daystop,$granularity,$timelines);
    echo $this->displayInstrumentFooter($row);
  }

  /**
  * Generate back | today | forward links for the calendar
  * @return string html for links
  */
  function _linksForwardBack($back, $today, $forward, $showForward=true, $extra=array()) {
    return '<div style="text-align:center">'
        .'<a href="'.makeURL('calendar', array_merge(array('instrid'=>$this->PD['instrid'], 'caloffset'=>$back), $extra)).'">&laquo; '. T_('earlier') .'</a> | '
        .'<a href="'.makeURL('calendar', array_merge(array('instrid'=>$this->PD['instrid'], 'caloffset'=>$today), $extra)).'">'. T_('today') .'</a> '
        .($showForward ?
                ' | '
                .'<a href="'.makeURL('calendar', array_merge(array('instrid'=>$this->PD['instrid'], 'caloffset'=>$forward), $extra)).'">'. T_('later') .' &raquo;</a>'
                : ''
          )
        .'</div>';
  }

  /**
  * Display a single day's calendar for the selected instrument
  */
  function instrumentDay() {
    $row = quickSQLSelect('instruments', 'id', $this->PD['instrid']);
    $granularity = $row['calprecision'];
    $timelines   = $row['caltimemarks'];

    $today = new SimpleDate(time());
    $today->dayRound();
    $start = new SimpleDate($this->PD['isodate']);
    $start->dayRound();
    $offset = issetSet($this->PD, 'caloffset');
    $start->addDays($offset);
    $totaloffset = $start->daysBetween($today);
    $maxfuture = $row['calfuture'] + 7 - $today->dow();
    //admin users are allowed to see further into the future.
    $this->_checkBookingAuth(-1);
    $this->log("Found total offset of $totaloffset, $maxfuture");
    if ($totaloffset > $maxfuture && !$this->_isAdminView) {
      #echo "Found total offset of $totaloffset, but only ".$row['calfuture']." is permitted. ";
      $delta = $maxfuture-$totaloffset;
      #echo "Changing offset by $delta\n";
      $start->addDays($delta);
    }
    $stop = clone($start);
    $stop->addDays(1);
    $cal = new Calendar($start, $stop, $this->PD['instrid']);
    $cal->setTimeSlotPicture($row['timeslotpicture']);

    # FIXME: get this from the instrument table?
    $daystart    = new SimpleTime('00:00:00');
    $daystop     = new SimpleTime('23:59:59');
//     echo $cal->display();
    $cal->bookhref = makeURL('book', array('instrid'=>$this->PD['instrid']));
    $cal->isAdminView = $this->_isAdminView;
    $cal->setOutputStyles('', 'caltoday', array('monodd', 'moneven'), 'm');
    echo $this->displayInstrumentHeader($row);
    echo $this->_linksForwardBack('-1', -1*$totaloffset, '+1',
                                $maxfuture > $totaloffset || $this->_isAdminView,
                                array('isodate'=>$start->dateString()));
    echo $cal->displayDayAsTable($daystart,$daystop,$granularity,$timelines);
    echo $this->displayInstrumentFooter($row);
  }

} // class ActionCalendar
?>
