<?php
# $Id$
# Project object (extends dbo), with extra customisations for other links

include_once 'dbrow.php';
include_once 'textfield.php';
include_once 'radiolist.php';

class Project extends DBRow {
  
  function Project($id) {
    $this->DBRow("projects", $id);
    $this->editable = 1;
    $f = new TextField("id", "Group ID");
    $f->editable = 0;
    $this->addElement($f);
    $f = new TextField("name", "Name");
    $attrs = array('size' => "48");
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField("longname", "");
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new RadioList("defaultclass", "Default charging band");
    $f->connectDB("userclass", array("id", "name"));
    $newchargename = new TextField("name","");
    $newchargename->namebase = "newcharge-";
    $newchargename->setAttr(array('size' => 24));
    $f->list->append(array("-1","Create new: "), $newchargename);
    $f->setAttr($attrs);
    $this->addElement($f);
    $this->fill();
    $this->dumpheader = "Project object";
  }

  function display() {
    return $this->displayAsTable();
  }

  function displayAsTable() {
    $t = "<table class='tabularobject'>";
    foreach ($this->fields as $k => $v) {
      $t .= $v->displayInTable(2);
    }
    $t .= "</table>";
    return $t;
  }

} //class Project
