<?php
# $Id$
# Booking matrix object for display in a table

class BookingMatrix {
  var $dayStart;
  var $dayStop;
  var $day; 
  var $granularity;
  var $bookings;
  var $numRows;
  var $rows;
  
  function BookingMatrix($dayStart, $dayStop, $day, $granularity, &$bookings) {
    $this->dayStart    = $dayStart;
    $this->dayStop     = $dayStop;
    $this->day         = $day;
    $this->granularity = $granularity;
    $this->bookings    = $bookings;
    $this->rows = array();
  }

  function prepareMatrix() {
    $this->numRows = $this->dayStop->subtract($this->dayStart)
                        / $this->granularity;
    $numBookings = count($this->bookings);

    #echo "Preparing matrix with $this->numRows rows for date "
        #.$this->day->datestring."<br/>\n";

    foreach ($this->bookings as $k => $b) {
      $bookDay = $b->start;
      $bookDay->dayRound();
      if ($bookDay->ticks == $this->day->ticks) {
        $bookDayStart = $bookDay;
        $mystart = isset($b->displayStart) ? $b->displayStart : $b->start;
        $mystop  = isset($b->displayStop)  ? $b->displayStop  : $b->stop;
        $bookDayStart->setTime($this->dayStart);
        //$starttime = $b->start->subtract($bookDayStart);
        $starttime = $mystart->subtract($bookDayStart);
        if ($starttime > 0) {
          //then the start of the booking is after the start time of the matrix
          $rowstart = floor($starttime/$this->granularity);
        } else {
          //the booking starts before the matrix; starting row adjusted
          $rowstart = 0;
        }
        $bookDayStop = $bookDay;
        $bookDayStop->setTime($this->dayStop);
        //$stoptime = $b->stop->subtract($bookDayStop);
        $stoptime = $mystop->subtract($bookDayStop);
        if ($stoptime < 0) {
          //the stop time is before the stop time of the matrix
          //$stoptimestart = $b->stop->subtract($bookDayStart);
          $stoptimestart = $mystop->subtract($bookDayStart);
          $rowstop = floor($stoptimestart/$this->granularity);
        } else {
          //the stop time is after the stop time of the matrix,
          //adjust the duration
          $rowstop = $this->numRows;
        }
        $rowspan = round($rowstop - $rowstart);

        $cell = new BookingCell($this->bookings[$k],$this->bookings[$k]->isStart,$rowspan);
        $this->rows[$rowstart] = $cell;//new BookingCell($this->bookings[$k],1,$rowspan);
        #echo "Allocated $rowstart-$rowstop = $rowstart, $rowspan to booking starting on "
            #." (".$b->start->datetimestring.")<br/>\n";
      }
    }
  }

} //class BookingMatrix
