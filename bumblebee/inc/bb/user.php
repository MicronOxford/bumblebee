<?php
# $Id$
# User object (extends dbo), with extra customisations for other links

include_once 'dbforms/dbrow.php';
include_once 'dbforms/textfield.php';
include_once 'dbforms/radiolist.php';

class User extends DBRow {
  
  function User($id) {
    $this->DBRow("projects", $id);
    $this->editable = 1;
    $f = new TextField("id", "Project ID");
    $f->editable = 0;
    $this->addElement($f);
    $f = new TextField("name", "Name");
    $attrs = array('size' => "48");
    $f->required = 1;
    $f->isInvalidTest = "is_empty_string";
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField("longname", "");
    $f->required = 1;
    $f->isInvalidTest = "is_empty_string";
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new RadioList("defaultclass", "Default charging band");
    $f->connectDB("userclass", array("id", "name"));
    $f->setFormat("id", "%s", array("name"), " %s", array(""));
    $newchargename = new TextField("name","");
    $newchargename->namebase = "newcharge-";
    $newchargename->setAttr(array('size' => 24));
    $newchargename->isInvalidTest = "is_empty_string";
    $newchargename->suppressValidation = 0;
    $f->list->append(array("-1","Create new: "), $newchargename);
    $f->setAttr($attrs);
    $f->required = 1;
    $f->isInvalidTest = "is_invalid_radiochoice";
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

} //class User
