<?php
# $Id$
# Booking cell object for display in a table

class BookingCell {
  var $booking;
  var $isStart;
  var $rows;
  
  function BookingCell(&$book, $start=1, $rows=1) {
    $this->booking = $book;
    $this->isStart = $start;
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
    #preDump(debug_backtrace());
    $t = '';
    #$class = $this->class;
    #$rota = date($this->roton, $this->booking->start->ticks);
    #$class .= ' '.$this->class[$rota%count($this->rotaClass)];
    #$today = new SimpleDate(time());
    #if ($today->datestring==$this->booking->start->datestring) {
      #$class .= ' '.$this->todayClass;
    #}
    if ($this->isStart) {
      $class .= ' '.$this->booking->baseclass;
      $t .= '<td rowspan="'.$this->rows.'" class="'.$class.'"'
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
