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
  var $slotStart;
  var $slot;
  var $representation;
  var $_manualRepresentation = TF_AUTO;
  var $droplist;
  
  function TimeField($name, $longname="", $description="") {
    parent::Field($name, $longname, $description);
    $this->time = new SimpleTime(0);
    $this->slotStart = new SimpleDate(0);
    //$this->DEBUG=10;
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
    #echo "TIME=".$this->time->timestring."\n";
    $this->_determineRepresentation();
    $this->setTime(parent::getValue());
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
        $t .= $this->hidden();
        break;
    }
    return $t;
  }
  
  /**
   * set the representation of this field
   *
   * @param integer $flag (TF_* types from class TimeField constants)
   */
  function setManualRepresentation($flag) {
    $this->log("Manual representation set to $flag",10);
    $this->_manualRepresentation = $flag;
  }
  
  /**
   * Determine what sort of representation is appropriate
   *
   */
  function _determineRepresentation() {
    //preDump($this);
    $this->_findExactSlot();
    if (! isset($this->slot) && $this->slot != 0) {
      $this->representation = TF_FIXED;
      return;
    }
    if ($this->_manualRepresentation != TF_AUTO) {
      $this->representation = $this->_manualRepresentation;
    } elseif (! $this->editable) {
      $this->representation = TF_FIXED;
    } elseif ($this->slot->isFreeForm) {
      $this->representation = TF_FREE;
    } elseif ($this->isStart || $this->slot->numslotsFollowing < 1) {
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
    $durations = $this->slot->allSlotDurations();
    $this->droplist = new DropList($this->name, $this->description);
    $this->droplist->setValuesArray($durations, 'id', 'iv');
    $this->droplist->setFormat('id', '%s', array('iv'));
    //preDump($durations);
    //for ($j = count($durations)-1; $j >=0 && $durations[$j] != $this->value; $j--) {
    //}
    //$this->droplist->setDefault($j);
    $this->droplist->setDefault($this->value);
  }
  
  /**
   * is a dropdown list appropriate here?
   * 
   * @access private
   */
  function _fixedTimeSlots() {
    if ($this->slotStart->ticks == 0) {
      $this->fixedTimeSlots = false;
    } else {
      $this->fixedTimeSlots = $this->slot->numslotsFollowing;
    }
    $this->log('Slot starts at '.$this->slotStart->datetimestring
                .', numFollowing: '.$this->fixedTimeSlots, 8);
    return $this->fixedTimeSlots;
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
      $this->setTime($this->value);
    }
    return $this->changed;
  }

  /**
   * Set the time (and value) from a Date-Time string
   *
   * @param string $time a date-time string (YYYY-MM-DD HH:MM:SS)
   */
  function setDateTime($time) {
    #echo "SETDATETIME = $time\n";
    $date = new SimpleDate($time);
    $this->date = $date;
    $this->setTime($date->timePart());
  }
  
  /**
   * Set the time (and value)
   *
   * @param string $time a time string (HH:MM)
   */
  function setTime($time) {
    #echo "SETTIME = $time\n";
    $this->time = new SimpleTime($time);
    $this->value = $this->time->timestring;
  }

  /** 
   * associate a TimeSlotRule for validation of the times that we are using
   *
   * @param TimeSlotRule $list a valid TimeSlotRule
   */
  function setSlots($list) {
    $this->list = $list;
    //preDump($list);
  }

  /** 
   * set the appropriate date that we are refering to for the timeslot rule validation
   *
   * @param string $date passed to the TimeSlotRule
   */
  function setSlotStart($date) {
    $this->slotStart = new SimpleDate($date);
  }

  function _findExactSlot() {
    $this->log('Looking for slot starting at '.$this->slotStart->datetimestring, 10);
    $this->slot = $this->list->findSlotByStart($this->slotStart);
  }
  
} // class TimeField


?> 
