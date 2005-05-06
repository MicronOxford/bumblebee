<?php
# $Id$
# Booking matrix object for display in a table

class BookingMatrix {
  var $daystart, 
      $daystop,
      $granularity;
  var $bookings;
  var $numCols = 7;
  var $numRows;
  var $rows;
  
  function BookingMatrix($daystart, $daystop, $granularity, &$bookings) {
    $this->daystart    = $daystart;
    $this->daystop     = $daystop;
    $this->granularity = $granularity;
    $this->bookings    = $bookings;
    $this->rows = array();
  }

  function prepareMatrix() {
    $numRowsPerDay = $this->daystop->subtract($this->daystart) / $this->granularity;
    $numBookings = count($this->bookings);
    $startDate = $this->bookings[0]->start;
    $stopDate  = $this->bookings[$numBookings-1]->stop;
    $numDays = $stopDate->daysBetween($startDate)+1;
    $numDayRows = ceil($numDays / $this->numCols);

    $this->numRows = $numDayRows*$numRowsPerDay;
    for ($i=0; $i<$this->numRows; $i++) {
      $this->rows[$i] = array();
    }

    echo "Preparing matrix with $numRowsPerDay rows per day, "
        ."$numDays days recorded in $this->numCols columns, "
        ."giving $numDayRows rows for days and "
        ."$this->numRows rows total.<br/>";

    foreach ($this->bookings as $k => $b) {
      $dayStart = $b->start;
      $dayStart->dayRound();
      $dayStart->addTime($this->daystart);
      #echo $this->daystart->timestring .'/';
      #echo $dayStart->datetimestring .'/';
      $day = floor($dayStart->daysBetween($startDate));
      $dayRow = floor($day / $this->numCols);
      $time = $b->start->subtract($dayStart);
      $timeRow = max(floor($time/$this->granularity), 0);
      $timeRow = min($timeRow, $numRowsPerDay);

      $row = $dayRow * $numRowsPerDay + $timeRow;
      $col = $day % $this->numCols;

      $rowspan = ($b->duration->seconds()-$this->daystart->seconds()) / $this->granularity;
      #FIXME configurable styles here
      $cell = new BookingCell($this->bookings[$k],1,$rowspan);
      $cell->addRotateClass(array('monodd','moneven'),'m');
      $cell->addTodayClass('caltoday');
      $this->rows[$row][$col] = new BookingCell($this->bookings[$k],1,$rowspan,
                                            'calday');
      echo "Allocated $dayRow + $timeRow = $row, $col to booking starting on "
          ."$day day, $time s (".$b->start->datetimestring.")<br/>\n";
    }
  }

} //class BookingMatrix
