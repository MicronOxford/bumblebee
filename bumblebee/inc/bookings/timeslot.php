<?php
# $Id$
# Booking/Vacancy object

include_once 'inc/date.php';

class TimeSlot {
  var $start;
  var $stop;
  var $duration;
  var $href = '';
  var $baseclass;
  var $isDisabled=0;
  var $isVacant = 0;
  var $isStart = 1;
  var $displayStart;
  var $displayStop;
  var $slotRule;
  
  function TimeSlot($start, $stop, $duration=0) {
    $this->start = new SimpleDate($start);
    $this->stop = new SimpleDate($stop);
    if ($duration==0) {
      $this->duration = new SimpleTime($this->stop->ticks - $this->start->ticks);
    } else {
      $this->duration = new SimpleTime($duration);
    }
  }

  function _TimeSlot_SimpleDate($start, $stop, $duration) {
    $this->start = $start;
    $this->stop = $stop;
    $this->duration = $duration;
  }

//   function displayInCell($isadmin=0) {
//     #return "Time slot $start->datetimestring for $duration->timestring\n";
//     //$t = '';
//     //global $BASEPATH;
//     //$isodate = $this->start->datestring;
//     /*$t .= "<div style='float:right;'><a href='$this->href/$isodate' class='but' title='Zoom in on date: $isodate'><img src='$BASEPATH/theme/images/zoom.png' alt='Zoom in on $isodate' class='calicon' /></a></div>";
//     $t .= "<div class='caldate'>" . strftime("%e", $this->start->ticks);
//     $t .= "<span class='calmonth "
//         #.($month == $lastmonth ? "contmonth" : "startmonth") . "'> "
//         ."contmonth" . "'> "
//         . strftime("%B", $this->start->ticks)
//         ."</span>";
//     $t .= "</div>";
//     */
// /*    $t .= $this->displayCellDetails($isadmin);
//     return $t;*/
//     return $this->displayCellDetails($isadmin);
//   }

  function displayShort() {
    return '<tr><td>'.get_class($this)
            .'</td><td>'.$this->start->datetimestring
            .'</td><td>'.$this->stop->datetimestring
            .'</td><td>'
            .'</td></tr>'."\n";
  }

} //class TimeSlot
