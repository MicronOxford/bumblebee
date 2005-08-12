<?php
# $Id$
# Booking/Vacancy base object -- designed to be inherited by Vacancy and Booking

include_once 'inc/date.php';

class TimeSlot {
  var $start;             // start of the slot as used for calculating graphical representation
  var $stop;              // end of the slot as used for calculating graphical representation
  var $duration;          // unused?
  var $href = '';
  var $baseclass;
  var $isDisabled=0;
  var $isVacant = 0;
  var $isStart = 0;
  var $displayStart;      // start of the slot for when time should be displayed
  var $displayStop;       // end of the slot for when time should be displayed
  var $arb_start = false;  // start time is arbitrary from truncation due to db lookup
  var $arb_stop  = false;  // stop time is arbitrary from truncation due to db lookup
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

  function displayShort() {
    return '<tr><td>'.get_class($this)
            .'</td><td>'.$this->start->datetimestring
            .'</td><td>'.$this->stop->datetimestring
            .'</td><td>'.$this->displayStart->datetimestring
            .'</td><td>'.$this->displayStop->datetimestring
            .'</td><td>'.$this->isStart
            .'</td></tr>'."\n";
  }

} //class TimeSlot
