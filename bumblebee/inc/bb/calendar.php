<?php
# $Id$
# Calendar object -- holds a collection of booking objects

include_once 'dbforms/date.php';
include_once 'bookings/booking.php';
include_once 'bookings/vacancy.php';
include_once 'bookings/cell.php';
include_once 'bookings/matrix.php';
include_once 'bookings/bookingdata.php';

class Calendar {
  var $start,
      $stop,
      $numDays,
      $instrument;
  var $fatal_sql = 1;
  var $bookinglist;
  var $href = '';
  var $dayClass = '',
      $todayClass = '',
      $rotateDayClass = '',
      $rotateDayClassDatePart = '';
  
  function Calendar($start, $stop, $instrument) {
    $this->start = $start;
    $this->stop  = $stop;
    $this->instrument = $instrument;
    echo "Creating calendar from $start->datestring to $stop->datestring<br />\n";
    $this->_fill();
    $this->_normalise();
  }

  function setOutputStyles($class, $today, $day, $dayrotate='m') {
    $this->dayClass = $class;
    $this->todayClass = $today;
    $this->rotateDayClass = is_array($day) ? $day : array($day);
    $this->rotateDayClassDatePart = $dayrotate;
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
   * FIXME: day to day rollover not yet implemented
  **/
  function _normalise() {
    $this->numDays = $this->stop->partDaysBetween($this->start);
    $booking = 0;
    //blat over the booking list so we can create the normalised list
    $bookings = $this->bookinglist;
    $this->bookinglist = array();
    $v = new Vacancy();
    $v->setTimes($this->stop,$this->stop);
    $bookings[] = $v;
    $day = 0;
    $now = $this->start;
    while ($now->ticks < $this->stop->ticks) {
      $tomorrow = $now; $tomorrow->addDays(1); $tomorrow->dayRound();
      #$now = $today;
      #preDump(array($tomorrow, $now));
      if ($now->ticks < $bookings[$booking]->start->ticks) {
        // then we should create a pseudobooking
        #echo "Found vacancy:";
        $v = new Vacancy();
        $stoptime = new SimpleDate(min($bookings[$booking]->start->ticks, $tomorrow->ticks),0);
        #preDump($stoptime);
        #echo " from $now->datetimestring to $stoptime->datetimestring<br/>";
          
        $v->setTimes($now, $stoptime);
        $this->bookinglist[] = $v;
        $now = $stoptime;
      } else {
        // then this is the current timeslot
        #echo "Found booking:";
        #preDump($bookings[$booking]->start);
        $this->bookinglist[] = $bookings[$booking];
        $now = $bookings[$booking]->stop;
        $booking++;
      }
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

} //class Calendar
