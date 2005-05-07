<?php
# $Id$
# Booking Vacancy object

include_once 'inc/date.php';
include_once 'timeslot.php';

class Vacancy extends TimeSlot {
  
  function Vacancy($arr='') {
    if (is_array($arr)) {
      $this->TimeSlot($arr['bookwhen'], $arr['stoptime'], $arr['duration']);
      #echo "Vacancy from ".$this->start->datetimestring." to ".$this->stop->datetimestring."<br />\n";
    }
    $this->isVacant = true;
    $this->baseclass='vacancy';
  }

  function setTimes($start, $stop) {
    $duration = new SimpleTime($stop->subtract($start));
    $this->_TimeSlot_SimpleDate($start, $stop, $duration);
  }

  function display() {
    return $this->displayInTable();
  }
  
  function displayInTable() {
    return '<tr><td>Vacant'
            .'</td><td>'.$this->start->datetimestring
            .'</td><td>'.$this->stop->datetimestring
            .'</td><td>'
            .'</td></tr>'."\n";
  }

  function displayCellDetails($isadmin=0) {
//     preDump(debug_backtrace());
//     preDump($this);
    global $BASEPATH;
    $t = '';
    if ($isadmin || ! $this->isDisabled) {
      $start = isset($this->displayStart) ? $this->displayStart : $this->start;
      $stop  = isset($this->displayStop)  ? $this->displayStop  : $this->stop;
      $startticks = $start->ticks;
      $stopticks = $stop->ticks;
      $timedescription = $start->datetimestring.' - '.$stop->datetimestring;
      //$timedescription = $this->start->timestring.' - '.$this->stop->timestring;
      $isodate = $start->datestring;
      $t .= "<div style='float:right;'><a href='$this->href/$isodate/$startticks-$stopticks' class='but' title='Make booking $timedescription'><img src='$BASEPATH/theme/images/book.png' alt='Make booking $timedescription' class='calicon' /></a></div>&nbsp;";
    }
    return $t;
  }

  function generateBookingTitle() {
    $t = '';
    if ($this->isDisabled) {
      $t .= 'Unavailable from ';
    } else {
      $t .= 'Vacancy from ';
    }
    $t .= $this->start->datetimestring .' - '. $this->stop->datetimestring;
    return $t;
  }

} //class Vacancy
