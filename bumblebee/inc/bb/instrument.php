<?php
# $Id$
# Instrument object (extends dbo), with extra customisations for other links

include_once 'dbforms/dbrow.php';
include_once 'dbforms/textfield.php';
include_once 'dbforms/radiolist.php';
include_once 'dbforms/exampleentries.php';

class Instrument extends DBRow {
  
  function Instrument($id) {
    global $CONFIG;
    $this->DBRow('instruments', $id);
    $this->editable = 1;
    $f = new IdField('id', 'Instrument ID');
    $f->editable = 0;
    $this->addElement($f);
    $f = new TextField('name', 'Name');
    $attrs = array('size' => '48');
    $f->required = 1;
    $f->isValidTest = 'is_nonempty_string';
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('longname', 'Description');
    $f->required = 1;
    $f->isValidTest = 'is_nonempty_string';
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('location', 'Location');
    $f->required = 1;
    $f->isValidTest = 'is_nonempty_string';
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('usualopen', 'Calendar start time (HH:MM)');
    $f->required = 1;
    $f->defaultValue = $CONFIG['instruments']['usualopen'];
    $f->isValidTest = 'is_valid_time';
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('usualclose', 'Calendar end time (HH:MM)');
    $f->required = 1;
    $f->defaultValue = $CONFIG['instruments']['usualclose'];
    $f->isValidTest = 'is_valid_time';
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('calprecision', 'Precision of calendar display (seconds)');
    $f->required = 1;
    $f->defaultValue = $CONFIG['instruments']['usualprecision'];
    $f->isValidTest = 'is_number';
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('caltimemarks', 'Time-periods per HH:MM displayed');
    $f->required = 1;
    $f->defaultValue = $CONFIG['instruments']['usualtimemarks'];
    $f->isValidTest = 'is_number';
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('timeslotpicture', 'Time slot picture');
    $f->required = 1;
    $f->defaultValue = $CONFIG['instruments']['usualtimeslotpicture'];
    $f->isValidTest = 'is_set';
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new RadioList('class', 'Charging class');
    $f->connectDB('instrumentclass', array('id', 'name'));
    $classexample = new ExampleEntries('id','instruments','class','name',3);
    $classexample->separator = '; ';
    $f->setFormat('id', '%s', array('name'), ' (%40.40s)', $classexample);
    $newclassname = new TextField('name','');
    $newclassname->namebase = 'newclass-';
    $newclassname->setAttr(array('size' => 24));
    $newclassname->isValidTest = 'is_nonempty_string';
    $newclassname->suppressValidation = 0;
    $f->list->append(array('-1','Create new: '), $newclassname);
    $f->setAttr($attrs);
    $f->extendable = 1;
    $f->required = 1;
    $f->isValidTest = 'is_valid_radiochoice';
    $this->addElement($f);
    $this->fill();
    $this->dumpheader = 'Instrument object';
  }

  function display() {
    return $this->displayAsTable();
  }

  function displayAsTable() {
    $t = '<table class="tabularobject">';
    foreach ($this->fields as $k => $v) {
      $t .= $v->displayInTable(2);
    }
    $t .= '</table>';
    return $t;
  }

} //class Instrument
