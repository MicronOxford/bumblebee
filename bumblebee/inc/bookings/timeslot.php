<?php
# $Id$
# Booking/Vacancy object

include_once 'inc/dbforms/date.php';

class TimeSlot {
  var $start,
      $stop,
      $duration;
  var $href = '';
  var $baseclass;
  
  function TimeSlot($start, $stop, $duration) {
    $this->start = new SimpleDate($start,1);
    $this->stop = new SimpleDate($stop,1);
    $this->duration = new SimpleTime($duration,1);
  }

  function _TimeSlot_SimpleDate($start, $stop, $duration) {
    $this->start = $start;
    $this->stop = $stop;
    $this->duration = $duration;
  }

  function displayInCell() {
    #return "Time slot $start->datetimestring for $duration->timestring\n";
    $t = '';
    global $BASEPATH;
    $isodate = $this->start->datestring;
    /*$t .= "<div style='float:right;'><a href='$this->href/$isodate' class='but' title='Zoom in on date: $isodate'><img src='$BASEPATH/theme/images/zoom.png' alt='Zoom in on $isodate' class='calicon' /></a></div>";
    $t .= "<div class='caldate'>" . strftime("%e", $this->start->ticks);
    $t .= "<span class='calmonth "
        #.($month == $lastmonth ? "contmonth" : "startmonth") . "'> "
        ."contmonth" . "'> "
        . strftime("%B", $this->start->ticks)
        ."</span>";
    $t .= "</div>";
    */
    $t .= $this->displayCellDetails();
    return $t;
  }

} //class TimeSlot
