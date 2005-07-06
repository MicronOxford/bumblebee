<?php
# $Id$
# Booking cell object for display in a table

define('MIDDLE_BOOKING',    1);
define('START_BOOKING',     2);
define('START_BOOKING_DAY', 4);

class BookingCell {
  var $booking;
  var $isStart;
  var $isStartDay;
  var $rows;
  
  function BookingCell(&$book, $start=START_BOOKING, $rows=1) {
    $this->booking = $book;
    $this->isStart    = $start & START_BOOKING;
    $this->isStartDay = $start & START_BOOKING_DAY;
    $this->rows    = $rows;
  }

  function addRotateClass($arr, $roton) {
    $this->rotaClass   = $arr;
    $this->roton = $roton;
  }

  function addTodayClass($c) {
    $this->todayClass   = $c;
  }

  function display($class, $href, $isadmin=0) {
    $t = '';
    if ($this->isStart || $this->isStartDay) {
      $class .= ' '.$this->booking->baseclass;
      $t .= '<td rowspan="'.$this->rows.'" class="'.$class.'" '
           .'title="'.$this->booking->generateBookingTitle().'">';
      $this->booking->href = $href;
      $t .= $this->booking->displayInCell($isadmin);
      $t .= '</td>';
    } else {
      $t .= '<!-- c:'.$this->booking->id.'-->';
    }
    return $t;
  }

} //class BookingCell
