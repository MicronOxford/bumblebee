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
  var $href = '';
  var $dayClass = '';
  var $todayClass = '';
  var $rotateDayClass = '';
  var $rotateDayClassDatePart = '';
  var $timeslots;
  
  var $DEBUG_CAL = 0;
  
  function Calendar($start, $stop, $instrument) {
    $this->start = $start;
    $this->stop  = $stop;
    $this->instrument = $instrument;
    $this->log("Creating calendar from $start->datestring to $stop->datestring");
    $this->_fill();
    $this->_normalise();
  }

  function setOutputStyles($class, $today, $day, $dayrotate='m') {
    $this->dayClass = $class;
    $this->todayClass = $today;
    $this->rotateDayClass = is_array($day) ? $day : array($day);
    $this->rotateDayClassDatePart = $dayrotate;
  }
  
  function setTimeSlotPicture($pic) {
    $this->timeslots = new TimeSlotRule($pic);
  }

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
   *   2004-01-01-11:00 to 2004-01-01-23:59 and from
   *   2004-01-02-00:00 to 2004-01-02-23:59.
   *
   * Actually, 24:00 will be used rather than 23:59 as it makes more
   * sense within the scope of non-overlapping bookings.
   *
   * Bookings are NOT restricted to remaining on one day (i.e. a booking from
   * 20:00:00 until 10:00:00 the next day is OK.
   *
  **/
  function _normalise() {
    $this->numDays = $this->stop->partDaysBetween($this->start);
    $this->log("Creating calendar for $this->numDays days");
    //blat over the booking list so we can create the normalised list
    $bookings = $this->bookinglist;
    $this->bookinglist = array();
        
    // put a vacancy at the end so we don't run off the end of the list.
    $v = new Vacancy();
    $v->setTimes($this->stop,$this->stop);
    $bookings[] = $v;
    
    //First: insert a vacancy between each non-consecutive booking
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
                  .' to '.$v->stop->datetimestring);
      } else {
        // then this is the current timeslot
        $bvlist[] = $bookings[$booking];
        $now = $bookings[$booking]->stop;
        $this->log('Included booking: '.
                $bookings[$booking]->start->datetimestring .' to '
               .$bookings[$booking]->stop->datetimestring);
        $booking++;
      }
    }
    
    $this->log('Breaking up bookings');
    //Second: break bookings over day boundaries
    $booking=0;
    for ($bv=0; $bv < count($bvlist); $bv++) {
      $this->log('considering booking #'.$bv);
      $cbook = $bvlist[$bv];
      $cbook->original = $cbook;     
      $today = $bvlist[$bv]->start; $today->dayRound();
      do {  //until the current booking has been broken up across day boundaries(
        $this->log('start='.$bvlist[$bv]->start->datetimestring
              .' stop='.$bvlist[$bv]->stop->datetimestring);
        $this->bookinglist[$booking] = $cbook;
        $tomorrow = $today; $tomorrow->addDays(1); $tomorrow->dayRound();
        $this->bookinglist[$booking]->start->max($today);
        $this->bookinglist[$booking]->stop->min($tomorrow);
        $today->addDays(1); $today->dayRound();
        $booking++;
      } while ($this->bookinglist[$booking-1]->original->stop->ticks > $tomorrow->ticks);
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
      $matrix = new bookingMatrix($daystart, $daystop, $today, 
                                        $granularity, $this->bookinglist);
      $matrix->prepareMatrix();
      $matrixlist[] = $matrix;
    }
    return $matrixlist;
  }

  function _getDayClass($today, $t) {
    $class = $this->dayClass;
    $class .= ' '.$this->rotateDayClass[date($this->rotateDayClassDatePart, $t->ticks) % count($this->rotateDayClass)];
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
      $t .= $v->display();
    }
    $t .= "</table>";
    return $t;
  }

  /**
   * Display the booking details in a table with rowspan based on
   * the duration of the booking
  **/
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
        $currentidx = $current->daysBetween($this->start);
        if (isset($matrix[$currentidx]->rows[$dayRow])) {
          #$t .= '<td>';
          #preDump($matrix[$currentidx]->rows[$dayRow]);
          $b =& $matrix[$currentidx]->rows[$dayRow];
          $class = $this->_getDayClass($today, $b->booking->start);
          $t .= "\n\t".$b->display($class, $this->href)."\n";
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
  **/
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
        $t .= "\n\t".$b->display($class, $this->href)."\n";
        #$t .= '</td>';
      }
      $t .= '</tr>';
    }
    $t .= "</table>";
    return $t;
  }
  
  function log ($string, $prio=10) {
    if ($prio <= $this->DEBUG_CAL) {
      echo $string."<br />\n";
    }
  }

} //class Calendar
