<?php
# $Id$
# Group object (extends dbo)

include_once 'dbforms/dbrow.php';
include_once 'dbforms/textfield.php';

class Costs extends DBRow {
  
  function Costs($id) {
    $this->DBRow('userclass', $id);
    $this->editable = 1;
    $f = new TextField('id', 'UserClass ID');
    $f->editable = 0;
    $this->addElement($f);
    $f = new TextField('name', 'Name');
    $attrs = array('size' => '48');
    $f->setAttr($attrs);
    $f->required = 1;
    $f->isValidTest = 'is_nonempty_string';
    $this->addElement($f);
    $f = new JoinData('userprojects',
                       'userid', $this->id,
                       'projects', 'Project membership');
    $projectfield = new DropList('projectid', 'Project');
    $projectfield->connectDB('projects', array('id', 'name', 'longname'));
    $projectfield->prepend(array('0','(none)'));
    $projectfield->setDefault(0);
    $projectfield->setFormat('id', '%s', array('name'), ' (%s)', array('longname'));
    $f->addElement($projectfield);
    $f->joinSetup('projectid', array('minspare' => 2));
    $f->colspan = 2;
    $this->addElement($f);
    $this->fill($id);
    $this->dumpheader = 'Cost object';
    
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
          #."ORDER BY instruments.name";
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

} //class Group
