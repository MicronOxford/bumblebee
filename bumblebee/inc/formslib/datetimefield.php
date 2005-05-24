<?php
# $Id$
# textfield object

include_once 'field.php';
include_once 'timefield.php';
include_once 'datefield.php';
include_once 'inc/date.php';
include_once 'inc/typeinfo.php';
include_once 'inc/bookings/timeslotrule.php';

class DateTimeField extends Field {

  var $time;
  var $timeeditable;
  var $date;
  var $dateeditable;
  var $list;

  var $representation;
  var $_manualRepresentation = TF_AUTO;
  
  function DateTimeField($name, $longname='', $description='') {
    parent::Field($name, $longname, $description);
    //$this->DEBUG=10;
    $this->time = new TimeField($name.'-time', $longname, $description);
    $this->time->isStart = true;
    $this->date = new DateField($name.'-date', $longname, $description);
  }

  function displayInTable($cols) {
    $errorclass = ($this->isValid ? '' : "class='inputerror'");
    $t = "<tr $errorclass><td>$this->longname</td>\n"
        ."<td title='$this->description'>";
    if ($this->editable && ! $this->hidden) {
      $t .= $this->selectable();
    } else {
      if (!$this->hidden) $t .= xssqw($this->getValue());
      $t .= $this->hidden();
    }
    if ($this->duplicateName) {
      $t .= "<input type='hidden' name='$this->duplicateName' "
             ."value='".xssqw($this->value)."' />";
    }
    $t .= "</td>\n";
    for ($i=0; $i<$cols-2; $i++) {
      $t .= '<td></td>';
    }
    $t .= '</tr>';
    return $t;
  }

  function selectable() {
    //preDump($this->time);
    //echo "Assembling date-time field\ndate";
    $t  = $this->date->getdisplay();
    $t .= ' ';
    //echo "Assembling date-time field\ntime";
    $t .= $this->time->getdisplay();
    return $t;
  }
  
  function hidden() {
    return $this->date->hidden() .' '. $this->time->hidden();
  }
  
  /**
   * calculate the correct values for the separate (and possibly not editable!) parts of the field
   */
  function calcDateTimeParts() {
    $val = ($this->getValue() == '') ? 0 : $this->getValue();
    #echo "datetime=$val\n";
    $this->time->setDateTime($val);
    $this->date->setDate($val);
    $this->value = $this->date->value .' '. $this->time->value;
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
    $datechanged = $this->date->update($data);
    $timechanged = $this->time->update($data);
    if ($datechanged || $timechanged) {
      $this->log('DateTimeField::update');
      $data[$this->namebase.$this->name] = $this->date->value .' '. $this->time->value;
      parent::update($data);
//       $this->calcDateTimeParts();
    }
    return $this->changed;
  }
  
  /** 
   * associate a TimeSlotRule for validation of the times that we are using
   *
   * @param TimeSlotRule $list a TimeSlotRule
   */
  function setSlots($list) {
    $this->list = $list;
    $this->time->setSlots($list);
    $this->calcDateTimeParts();
  }
  
  /** 
   * set the appropriate date that we are refering to for the timeslot rule validation
   *
   * @param string $date passed to the TimeSlotRule
   */
  function setSlotStart($date) {
    $this->time->setSlotStart($date);
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
  
  /**
   *  isValid test (extend Field::isValid), looking at the individual parts of the field
   */
  function isValid() {
    parent::isValid();
    $this->isValid = $this->isValid && $this->date->isValid() && $this->time->isValid();
    return $this->isValid;
  }
  
  function setEditableOutput($date, $time) {
    $this->dateeditable = $date;
    $this->date->editableOutput = $date;
    $this->timeeditable = $time;
    $this->time->editableOutput = $time;
  }

  /**
   * return a SQL-injection-cleansed string that can be used in an SQL
   * UPDATE or INSERT statement. i.e. "name='Stuart'".
   *
   * @return string  in SQL assignable form
   */
  function sqlSetStr($name='') {
    if (empty($name)) {
      $name = $this->name;
    }
    if (! $this->sqlHidden) {
      $date = new SimpleDate($this->getValue());
      return $name .'='. qw($date->datetimestring);
    } else {
      return '';
    }
  }


      
} // class DateTimeField


?> 
