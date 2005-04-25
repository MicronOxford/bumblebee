<?php
# $Id$
# Calendar object -- holds a collection of booking objects

include_once 'dbforms/date.php';
include_once 'bookings/booking.php';
include_once 'bookings/vacancy.php';
include_once 'bookings/cell.php';
include_once 'bookings/matrix.php';
include_once 'bookings/bookingdata.php';
include_once 'bookings/timeslotrule.php';

class Calendar {
  var $start;
  var $stop;
  var $numDays;
  var $instrument;
  var $fatal_sql = 1;
  var $bookinglist;
  var $_bvlist;
  var $href = '';
  var $dayClass = '';
  var $todayClass = '';
  var $rotateDayClass = '';
  var $rotateDayClassDatePart = '';
  var $timeslots;
  var $isAdminView=0;
  
  var $DEBUG_CAL = 0;    // 0 to turn off debug logging, 10 to turn on all debug logging
  
  /**
   * Create a calendar object, can display bookings in calendar format
   * 
   * @param SimpleDate $start    start time to display bookings from
   * @param SimpleDate $stop     stop time to display bookings until
   * @param integer $instrument  what instrument number to display bookings for
   */ 
  function Calendar($start, $stop, $instrument) {
    $this->start = $start;
    $this->stop  = $stop;
    $this->instrument = $instrument;
    $this->log("Creating calendar from $start->datestring to $stop->datestring", 5);
    $this->_fill();
    $this->_insertVacancies();
    $this->_breakAcrossDays();
  }

  /**
   * set the CSS style names by which 
   *
   * @param string $dayClass   class to use in every day header
   * @param string $today      class to use on today's date
   * @param mixed  $day        string for class on each day, or array to rotate through
   * @param string $dayrotate  time-part ('m', 'd', 'y' etc) on which day CDD should be rotated
   */
  function setOutputStyles($dayClass, $today, $day, $dayrotate='m') {
    $this->dayClass = $dayClass;
    $this->todayClass = $today;
    $this->rotateDayClass = is_array($day) ? $day : array($day);
    $this->rotateDayClassDatePart = $dayrotate;
  }
  
  /**
   * set the time slot picture (passed straight to a TimeSlotRule object) to apply 
   *
   * @param string $pic   timeslot picture for this instrument and this calendar
   */
  function setTimeSlotPicture($pic) {
    $this->timeslots = new TimeSlotRule($pic);
    //break bookings over the predefined pictures
    $this->log('Breaking up bookings according to defined rules');
    $this->_breakAccordingToList($this->timeslots);
  }

  /**
   */
  function _fill() {
    $bookdata = new BookingData (
          array(
            'instrument' => $this->instrument,
            'start'      => $this->start->datetimestring,
            'stop'       => $this->stop->datetimestring
               )
                               );
    $this->bookinglist = $bookdata->dataArray();
  }

  /**
   * Create pseudo-bookings for all vacancies between the start
   * of this calendar and the end.
   *
   * For example:
   *
   *   if we were constructing a calendar from 00:00 on 2004-01-01 to 
   *   23:59 on 2004-01-02, but there was only a booking from 10:00 to 11:00
   *   on 2004-01-01, then we should create vacancy pseudo-bookings from
   *   2004-01-01-00:00 to 2004-01-01-10:00 and from
   *   2004-01-01-11:00 to 2004-01-01-24:00 and from
   *   2004-01-02-00:00 to 2004-01-02-24:00.
   *
   * Bookings are NOT restricted to remaining on one day (i.e. a booking from
   * 20:00:00 until 10:00:00 the next day is OK.
   *
   */
  function _insertVacancies() {
    $this->numDays = $this->stop->partDaysBetween($this->start);
    $this->log("Creating calendar for $this->numDays days", 5);
    //blat over the booking list so we can create the normalised list
    $bookings = $this->bookinglist;
    $this->bookinglist = array();
        
    // put a vacancy at the end so we don't run off the end of the list.
    $v = new Vacancy();
    $v->setTimes($this->stop,$this->stop);
    $bookings[] = $v;
    
    //insert a vacancy between each non-consecutive booking
    $bvlist = array();
    $booking = 0;
    $now = $this->start;
    $this->log('Normalising bookings');    
    while ($now->ticks < $this->stop->ticks) {
      if ($now->ticks < $bookings[$booking]->start->ticks) {
        // then we should create a pseudobooking
        $v = new Vacancy();
        $stoptime = new SimpleDate($bookings[$booking]->start->ticks);
        $v->setTimes($now, $stoptime);
        $bvlist[] = $v;
        $now = $stoptime;
        $this->log('Created vacancy: '.$v->start->datetimestring
                  .' to '.$v->stop->datetimestring, 9);
      } else {
        // then this is the current timeslot
        $bvlist[] = $bookings[$booking];
        $now = $bookings[$booking]->stop;
        $this->log('Included booking: '.
                $bookings[$booking]->start->datetimestring .' to '
               .$bookings[$booking]->stop->datetimestring, 9);
        $booking++;
      }
    }
    $this->bookinglist = $bvlist;
  }
    
  /**
   * Break up bookings that span days (for display purposes only)
   *
   * For example:
   *
   *   If we had a vacancy pseudo-bookings from
   *     2004-01-01-11:00 to 2004-01-02-24:00, then we would 
   *   break it up into two bookings as follows:
   *     2004-01-01-11:00 to 2004-01-01-24:00 and 
   *     2004-01-02-00:00 to 2004-01-02-24:00.
   *
   */
  function _breakAcrossDays() {
    $this->log('Breaking up bookings across days');
    //break bookings over day boundaries
    $daylist = new TimeSlotRule('[0-6]<00:00-24:00/*>');
    $this->_breakAccordingToList($daylist);
  }    
    
  /**
   * Break up bookings that span elements of a defined list (e.g. allowable times or 
   * days). A TimeSlotRule ($list) is used to define how the times should be broken up
   */
  function _breakAccordingToList($list) {
    $bl = $this->bookinglist;
    $this->bookinglist = array();
    $this->log('Breaking up bookings according to list');
    $this->log($list->dump());
    $booking=0;
    for ($bv=0; $bv < count($bl); $bv++) {
      $this->log('considering timeslot #'.$bv.': '
                      .$bl[$bv]->start->datetimestring.' - '.$bl[$bv]->stop->datetimestring, 8);
      $cbook = $bl[$bv];
      $cbook->original = $cbook;
      $slot = $list->findSlotFromWithin($bl[$bv]->start);  
      #$start = $list->findSlotStart($bl[$bv]->start);
      if ($slot == 0) {
        // then the original start time must be outside the proper limits
        $slot = $list->findNextSlot($bl[$bv]->start);
      }
      do {  //until the current booking has been broken up across list boundaries
        $this->log('ostart='.$bl[$bv]->start->datetimestring 
              .' ostop='.$bl[$bv]->stop->datetimestring, 10);
        $stop  = $slot->stop;
        $this->log('cstart='.$slot->start->datetimestring
              .' cstop='.$slot->stop->datetimestring, 10);
        $this->bookinglist[$booking] = $cbook;
        
        // while PHP's handling of methods is broken, we have to this as a two-step operation:
        // all we want to do is:
        //    $this->bookinglist[$booking]->start->max($slot->start);
        // but that causes the start property to change from and Object to an &Object (see a var_dump)
        // see http://bugs.php.net/bug.php?id=24485 and http://bugs.php.net/bug.php?id=30787
        $newstart = $this->bookinglist[$booking]->start;
        $newstart->max($slot->start);
        $this->bookinglist[$booking]->start = $newstart;

        //...and again:          
        //$this->bookinglist[$booking]->stop->min($stop);
        $newstop = $this->bookinglist[$booking]->stop;
        $newstop->min($stop);
        $this->bookinglist[$booking]->stop = $newstop;
        
        $this->bookinglist[$booking]->isDisabled = ! $slot->isAvailable;
        
        $this->log('sstart='.$this->bookinglist[$booking]->start->datetimestring
              .' sstop='.$this->bookinglist[$booking]->stop->datetimestring, 10);
        $slot = $list->findNextSlot($slot->start);
        $booking++;
        $this->log('oticks='.$this->bookinglist[$booking-1]->original->stop->ticks
                   .'nticks='.$slot->start->ticks,10);
        $this->log('nextstart='.$slot->start->datetimestring,10);
        $this->log('');
      } while ($this->bookinglist[$booking-1]->original->stop->ticks > $slot->start->ticks);
    }
  }

  
  /**
   * Generate a booking matrix for all the days we are interested in
  **/
  function _collectMatrix($daystart, $daystop, $granularity) {
    $matrixlist = array();
    for ($day = 0; $day < $this->numDays; $day++) {
      $today = $this->start;
      $today->addDays($day);
      $matrix = new BookingMatrix($daystart, $daystop, $today, 
                                        $granularity, $this->bookinglist);
      $matrix->prepareMatrix();
      $matrixlist[] = $matrix;
    }
    return $matrixlist;
  }

  function _getDayClass($today, $t) {
    $class = $this->dayClass;
    $class .= ' '.$this->rotateDayClass[date($this->rotateDayClassDatePart, $t->ticks) 
                      % count($this->rotateDayClass)];
    if ($today->datestring==$t->datestring) {
      $class .= ' '.$this->todayClass;
    }
    return $class;
  }

  function display() {
    return $this->displayAsTable();
  }

  function displayAsTable() {
    $t = "<table class='tabularobject'>";
    foreach ($this->bookinglist as $k => $v) {
      #$t .= '<tr><td>'.$v[0].'</td><td>'.$v[1].'</td></tr>'."\n";
      $t .= $v->displayShort();
    }
    $t .= "</table>";
    return $t;
  }

  /**
   * Display the booking details in a table with rowspan based on
   * the duration of the booking
   */
  function displayMonthAsTable($daystart, $daystop, $granularity, 
                                    $reportPeriod) {
    global $BASEPATH;
    $matrix = $this->_collectMatrix($daystart, $daystop, $granularity);
    $numRowsPerDay =  $daystop->subtract($daystart) / $granularity;
    $numRows = ceil($this->numDays/7) * $numRowsPerDay;
   
    #report the time in a time column on the LHS every nth row:
    $timecolumn = array();
    $time = $daystart;
    for ($row=0; $row<$numRowsPerDay; $row++) {
      $timecolumn[$row] = $time;
      $time->addSecs($granularity);
    }

    $today = new SimpleDate(time());
    
    $t = "<table class='tabularobject calendar'>";
    $weekstart = $this->start;
    $weekstart->addDays(-7);
    $t .= '<tr><th></th>';
    for ($day=0; $day<7; $day++) {
      $current = $weekstart;
      $current->addDays($day);
      $t .= "<th class='caldow'>".date('D', $current->ticks).'</th>';
    }
    $t .= '</tr>';
    for ($row = 0; $row < $numRows; $row++) {
      $dayRow = $row % $numRowsPerDay;
      if ($dayRow == 0) {
        $weekstart->addDays(7);
        $t .= "<tr><td></td>";
        for ($day=0; $day<7; $day++) {
          $current = $weekstart;
          $current->addDays($day);
          $isodate = $current->datestring;
          $class = $this->_getDayClass($today, $current);
          $t .= "<td class='caldate $class'>";
          $t .= "<div style='float:right;'><a href='$this->href/$isodate' class='but' title='Zoom in on date: $isodate'><img src='$BASEPATH/theme/images/zoom.png' alt='Zoom in on $isodate' class='calicon' /></a></div>\n";
          $t .= "<div class='caldate'>" . strftime("%e", $current->ticks);
          $t .= "<span class='calmonth "
          #.($month == $lastmonth ? "contmonth" : "startmonth") . "'> "
            ."startmonth" . "'> "
            . strftime("%B", $current->ticks)
          ."</span>";
          $t .= "</div>";
          $t .= "</td>";
        }
        $t .= "</tr>";
      }
      $t .= '<tr>';
      if ($dayRow % $reportPeriod == 0) {
        $t .= '<td rowspan="'.$reportPeriod.'">';
        $t .= $timecolumn[$dayRow]->timestring;
        $t .= '</td>';
      }
      for ($day=0; $day<7; $day++) {
        $current = $weekstart;
        $current->addDays($day);
        $currentidx = $current->dsDaysBetween($this->start);
        if (isset($matrix[$currentidx]->rows[$dayRow])) {
          #$t .= '<td>';
          #preDump($matrix[$currentidx]->rows[$dayRow]);
          $b =& $matrix[$currentidx]->rows[$dayRow];
          $class = $this->_getDayClass($today, $b->booking->start);
          $class .= ($b->booking->isDisabled ? ' disabled' : '');
          //echo "$class <br />\n";
          $t .= "\n\t".$b->display($class, $this->href, $this->isAdminView)."\n";
          #$t .= '</td>';
        }
      }
      $t .= '</tr>';
    }
    $t .= "</table>";
    return $t;
  }

  /**
   * Display the booking details in a table with rowspan based on
   * the duration of the booking
   *
   * @param SimpleTime $daystart    time from which bookings should be displayed
   * @param SimpleTime $daystop     time up until which bookings should be displayed
   * @param integer    $granularity seconds per row in display
   * @param integer    $reportPeriod  seconds between reporting the time in a column down the side
   */
  function displayDayAsTable($daystart, $daystop, $granularity, 
                                    $reportPeriod) {
    global $BASEPATH;
    $matrix = $this->_collectMatrix($daystart, $daystop, $granularity);
    $numRowsPerDay =  ceil($daystop->subtract($daystart) / $granularity);
    $numRows = $numRowsPerDay;

    #report the time in a time column on the LHS every nth row:
    $timecolumn = array();
    $time = $daystart;
    for ($row=0; $row<$numRowsPerDay; $row++) {
      $timecolumn[$row] = $time;
      $time->addSecs($granularity);
    }

    $today = new SimpleDate(time());
    
    $t = "<table class='tabularobject calendar'>";
    $t .= '<tr><th></th>';
    $t .= "<td class='caldayzoom'>";
    $t .= "<div class='caldate'>" . strftime("%e", $this->start->ticks);
    $t .= "<span class='calmonth "
    #.($month == $lastmonth ? "contmonth" : "startmonth") . "'> "
      ."startmonth" . "'> "
      . strftime("%B", $this->start->ticks)
    ."</span>";
    $t .= "</div>";
    $t .= "</td>";
    $t .= "</tr>";
    for ($row = 0; $row < $numRows; $row++) {
      $t .= "<tr>";
      if ($row % $reportPeriod == 0) {
        $t .= '<td rowspan="'.$reportPeriod.'">';
        $t .= $timecolumn[$row]->timestring;
        $t .= '</td>';
      }
      if (isset($matrix[0]->rows[$row])) {
        #$t .= '<td>';
        #preDump($matrix[$currentidx]->rows[$dayRow]);
        $b =& $matrix[0]->rows[$row];
        $class = $this->_getDayClass($today, $b->booking->start);
        $t .= "\n\t".$b->display($class, $this->href, $this->isAdminView)."\n";
        #$t .= '</td>';
      }
      $t .= '</tr>';
    }
    $t .= "</table>";
    return $t;
  }
  
  /** 
   * logging function -- logs debug info to stdout
   *
   * @param string $string  text to be logged
   * @param integer $prio optional (default value 10) debug level of the message 
   *
   * The higher $prio, the more verbose (in the debugging sense) the output.
   */
  function log ($string, $prio=10) {
    if ($prio <= $this->DEBUG_CAL) {
      echo $string."<br />\n";
    }
  }

} //class Calendar
