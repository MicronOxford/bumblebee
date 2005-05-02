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
    #$f = new RadioList("class", "Charging class");
    //$f->connectDB("instrumentclass", array("id", "name"));
    //$classexample = new ExampleEntries("id","instruments","class","name",3);
    //$classexample->separator = '; ';
    //$f->setFormat("id", "%s", array("name"), " (%40.40s)", $classexample);
    #$label = new ExampleEntries('id','instruments','class','name',3);
    #$label->separator = '; ';
    #$f->setFormat("id", "%s", array("name"), " (%40.40s)", $classexample);

    $f = new JoinData('costs',
                       'userclass', $this->id,
                       'classlabel', 'Cost settings');
    $instrfield = new DropList('instrumentclass', 'Instrument Class');
    $instrfield->connectDB('instrumentclass', array('id', 'name'));
    //$instrfield->prepend(array('0','(none)'));
    //$instrfield->setDefault(0);
    $classexample = new ExampleEntries('id','instruments','class','name',3);
    $classexample->separator = '; ';
    $instrfield->setFormat('id', '%s', array('name'), ' (%40.40s)', $classexample);
    $instrfield->editable=0;
    $f->addElement($instrfield);

    //$label = new TextField('', 'User class');
    //$label->editable = 0;
    //$instrex = new ExampleEntries('id','instruments','class','name',3);
    //$instrex->separator = '; ';
    //$label->setFormat("id", "%40.40s", $instrex);
    //$f->addElement($label);
    $f->addElement($instrfield);
    $cost = new TextField('costfullday', 'Full day cost');
    $f->addElement($cost);
    $halfs= new TextField('hourfactor', 'Hourly rate multiplier');
    $f->addElement($halfs);
    $hours= new TextField('halfdayfactor', 'Half-day rate multiplier');
    $f->addElement($hours);
    $f->joinSetup('instrumentclass', array('minspare' => 0));
    $f->colspan = 2;
    //preDump($f);
    $this->addElement($f);

    $this->fill($id);
    $this->dumpheader = 'Cost object';
  }
/*    
          $q = "SELECT costs.id AS costid,instrumentclass.id AS instrclassid,"
              ."instrumentclass.name AS instrclassname, "
              ."cost_hour, cost_halfday,cost_fullday,"
              ."userclass.name AS userclassname "
          ."FROM instrumentclass "
          #."LEFT JOIN instrumentclass ON instrumentclass.id=costs.instrumentclass "
          ."LEFT JOIN costs ON instrumentclass.id=costs.instrumentclass "
          ."LEFT JOIN userclass on userclass.id=costs.userclass "
          #."LEFT JOIN stdrates on stdrates.instrid=costs.id "
          #."LEFT JOIN instruments on stdrates.instrid=instruments.id "
          ."WHERE costs.userclass='$userclass' "
          ."ORDER BY instrumentclass.name";
          #."ORDER BY instruments.name";*/

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
