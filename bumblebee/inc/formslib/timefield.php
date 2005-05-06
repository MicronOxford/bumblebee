<?php
# $Id$
# textfield object

include_once 'field.php';
include_once 'typeinfo.php';

define('TF_FIXED', 0);
define('TF_DROP', 1);
define('TF_FREE', 2);
define('TF_AUTO', -1);

class TimeField extends Field {
  var $time;
  var $list;
  var $isStart=0;
  
  var $date;
  var $representation;
  var $manualRepresentation = TF_AUTO;
  var $droplist;

  
  var $DEBUG = 10;
  
  function TimeField($name, $longname="", $description="") {
    parent::Field($name, $longname, $description);
    $this->time = new SimpleTime(0);
    $this->date = new SimpleDate(0);
  }

  function displayInTable($cols) {
    $errorclass = ($this->isValid ? "" : "class='inputerror'");
    $t = "<tr $errorclass><td>$this->longname</td>\n"
        ."<td title='$this->description'>";
    if ($this->editable && ! $this->hidden) {
      $t .= $this->selectable();
    } else {
      if (!$this->hidden) $t .= xssqw($this->value);
      $t .= $this->hidden();
    }
    if ($this->duplicateName) {
      $t .= "<input type='hidden' name='$this->duplicateName' "
             ."value='".xssqw($this->value)."' />";
    }
    $t .= "</td>\n";
    for ($i=0; $i<$cols-2; $i++) {
      $t .= "<td></td>";
    }
    $t .= "</tr>";
    return $t;
  }

  function selectable() {
    echo "TIME=".$this->time->timestring."\n";
    $this->_determineRepresentation();
    $t = '';
    switch ($this->representation) {
      case TF_DROP:
        $this->_prepareDropDown();
        $t .= $this->droplist->selectable();
        break;
      case TF_FREE:
        $t .= "<input type='text' name='$this->namebase$this->name' "
            ."value='".xssqw($this->time->timestring)."' ";
        $t .= (isset($this->attr['size']) ? "size='".$this->attr['size']."' " : "");
        $t .= (isset($this->attr['maxlength']) ? "maxlength='".$this->attr['maxlength']."' " : "");
        $t .= "/>";
        break;
      case TF_FIXED:
        $t .= $this->time->timestring;
        $t .= $this->hidden;
        break;
    }
    return $t;
  }
  
  function hidden() {
    return "<input type='hidden' name='$this->namebase$this->name' "
           ."value='".xssqw($this->value)."' />";
  }

  
  /**
   * Determine what sort of representation is appropriate
   *
   */
  function _determineRepresentation() {
    if ($this->manualRepresentation != TF_AUTO) {
      $this->representation = $this->manualRepresentation;
    } elseif (! $this->editable) {
      $this->representation = TF_FIXED;
    } elseif ($this->_fixedTimeSlots()) {
      $this->representation = TF_DROP;
    } else {
      $this->representation = TF_FREE;
    }
    $this->log('Determined representation was '. $this->representation, 10);
  }
  
  /**
   * Calculate data for the dropdown list of permissible times
   *
   * We should:
   *    1. determine if a dropdown list is appropriate (is numslots=* ?)
   *    2. 
   */
  function _prepareDropDown() {
    $this->droplist = new DropList($this->name, $this->description);
    $possibleTimes = $this->_obtainPossibleTimes();
    $this->droplist->setValuesArray($possibleTimes, 'id', 'iv');
    $this->droplist->setFormat('id', '%s', array('iv'));
  }
  
  /**
   * is a dropdown list appropriate here?
   * 
   * @access private
   */
  function _fixedTimeSlots() {
    $this->log('Are time slots fixed?', 10);
    if (! $this->isStart || $this->date->ticks == 0) {
      $this->log('No ticks for date so can\'t do fixed slots',8);
      return false;
    }
    $date = new SimpleDate($this->date);
    $date->setTime($this->time);
    $startslot = $this->list->findSlotStart($date);
    $this->fixedTimeSlots = $this->list->slotGranular($startslot);
    $this->log('Slot starts at '.$startslot->datetimestring.': '.$this->fixedTimeSlots, 8);
    return $this->fixedTimeSlots;
  }
  
  /**
   * what list of times should be used for the dropdown list
   */
  function _obtainPossibleTimes() {
    $date = new SimpleDate($this->date);
    $date->setTime($this->time);
    if ($this->isStart) {
      return $this->list->allSlotStart($date);
    } else {
      return $this->list->allSlotDurations($date);
    }
  }
  
  /** 
   * overload the parent's set() method as we need to do some magic in here
   */
  function set($value) {
    parent::set($value);
    if (strpos($value, '-') === false) {
      $this->setTime($value);
    } else {
      $this->setDateTime($value);
    }
//     $this->calcDropDown();
  }

  /**
   * overload the parent's update method so that local calculations can be performed
   *
   * @param array $data html_name => value pairs
   *
   * @return boolean the value was updated
   */
  function update($data) {
    if (parent::update($data)) {
      $this->calDropDown();
    }
    return $this->changed;
  }
  
  /**
   * Set the time (and value) from a Date-Time string
   *
   * @param string $time a date-time string (YYYY-MM-DD HH:MM:SS)
   */
  function setDateTime($time) {
    echo "SETDATETIME = $time\n";
    $date = new SimpleDate($time);
    $this->date = $date;
    $this->time = $date->timePart();
/*    $this->time->setStr($date->timePart());
    $this->set($this->time->timestring);*/
  }
  
  /**
   * Set the time (and value)
   *
   * @param string $time a time string (HH:MM)
   */
  function setTime($time) {
    echo "SETTIME = $time\n";
    $this->time->setStr($time);
//     $this->set($this->time->timestring);
  }

  /** 
   * create a TimeSlotRule for validation of the times that we are using
   *
   * @param string $list initialisation string for a TimeSlotRule
   */
  function setSlotPicture($list) {
    $this->list = new TimeSlotRule($list);
//     $this->calcDropDown();
  }

  function log($logstring, $prio=10) {
    if ($prio <= $this->DEBUG) {
      echo $logstring."<br />\n";
    }
  }
  
    
} // class TimeField


?> 
