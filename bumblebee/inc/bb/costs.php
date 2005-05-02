<?php
# $Id$
# Group object (extends dbo)

include_once 'dbforms/dbrow.php';
include_once 'dbforms/textfield.php';

class ClassCost extends DBRow {
  
  function ClassCost($id) {
    $this->DBRow('userclass', $id);
    $this->editable = 1;
    $f = new IdField('id', 'UserClass ID');
    $f->editable = 0;
    $this->addElement($f);
    $f = new TextField('name', 'Name');
    $attrs = array('size' => '48');
    $f->setAttr($attrs);
    $f->required = 1;
    $f->isValidTest = 'is_nonempty_string';
    $this->addElement($f);

    $f = new JoinData('costs',
                       'userclass', $this->id,
                       'classlabel', 'Cost settings');
    $instrfield = new DropList('instrumentclass', 'Instrument Class');
    $instrfield->connectDB('instrumentclass', array('id', 'name'));
    $classexample = new ExampleEntries('id','instruments','class','name',3);
    $classexample->separator = '; ';
    $instrfield->setFormat('id', '%s', array('name'), ' (%40.40s)', $classexample);
    $instrfield->editable=0;
    $f->addElement($instrfield);

    $f->addElement($instrfield);
    $cost = new TextField('costfullday', 'Full day cost');
    $f->addElement($cost);
    $halfs= new TextField('hourfactor', 'Hourly rate multiplier');
    $f->addElement($halfs);
    $hours= new TextField('halfdayfactor', 'Half-day rate multiplier');
    $f->addElement($hours);
    $f->joinSetup('instrumentclass', array('minspare' => 0));
    $f->colspan = 2;
    $this->addElement($f);

    $this->fill($id);
    $this->dumpheader = 'Cost object';
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

} //class ClassCost
