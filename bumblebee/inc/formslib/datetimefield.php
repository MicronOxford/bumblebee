<?php
# $Id$
# textfield object

include_once 'field.php';
include_once 'timefield.php';
include_once 'datefield.php';
include_once 'date.php';
include_once 'typeinfo.php';
include_once 'inc/bookings/timeslotrule.php';

class DateTimeField extends Field {

  var $time;
  var $date;
  var $list;

  var $representation;
  var $_manualRepresentation = TF_AUTO;
  
  function DateTimeField($name, $longname="", $description="") {
    parent::Field($name, $longname, $description);
    $this->time = new TimeField($name.'-time', $longname, $description);
    $this->time->isStart = true;
    $this->date = new DateField($name.'-date', $longname, $description);
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
    $t  = $this->date->selectable();
    $t .= $this->time->selectable();
    return $t;
  }
  
  function hidden() {
    return "<input type='hidden' name='$this->namebase$this->name' "
           ."value='".xssqw($this->value)."' />";
  }
  
  /**
   * calculate the correct values for the separate (and possibly not editable!) parts of the field
   */
  function calcDateTimeParts() {
    $val = ($this->getValue() == '') ? 0 : $this->getValue();
    #echo "datetime=$val\n";
    $this->time->setDateTime($val);
    $this->date->setDate($val);
  }
  
  /** 
   * overload the parent's value as we need to do some magic in here
   */
  function set($value) {
    #echo "V=$value\n";
    parent::set($value);
    $this->calcDateTimeParts();
  }
  
  /**
   * overload the parent's update method so that local calculations can be performed
   *
   * @param array $data html_name => value pairs
   *
   * @return boolean the value was updated
   */
  function update($data) {
    if ($this->date->update($data) || $this->time->update($data)) {
      $data[$this->namebase.$this->name] = $this->date->value .' '. $this->time->value;
      parent::update($data);
//       $this->calcDateTimeParts();
    }
    return $this->changed;
  }
  
  /** 
   * create a TimeSlotRule for validation of the times that we are using
   *
   * @param string $list initialisation string for a TimeSlotRule
   */
  function setSlotPicture($list) {
    $this->list = new TimeSlotRule($list);
    $this->time->setSlotPicture($list);
    $this->calcDateTimeParts();
  }
  
  /**
   * pass on any flags about the representation that we should use to our members
   *
   * @param integer $flag (TF_* types from class TimeField constants)
   */
  function setManualRepresentation($flag) {
    $this->_manualRepresentation = $flag;
    $this->time->setManualRepresentation($flag);
  }

} // class DateTimeField


?> 
