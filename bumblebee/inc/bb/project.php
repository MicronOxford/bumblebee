<?php
# $Id$
# Project object (extends dbo), with extra customisations for other links

include_once 'dbrow.php';
include_once 'textfield.php';
include_once 'radiolist.php';
include_once 'droplist.php';
include_once 'multilist.php';

class Project extends DBRow {
  
  function Project($id) {
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
    $f->setFormat("id", "%s", array("name"));
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
    $f = new MultiList("projectgroups",
                       "projectid", $this->id, 
                       "groups", "Group membership");
    $percentfield = new TextField("grouppc", "");
    $percentfield->isInvalidTest = "is_not_number";
    $f->addElement($percentfield);
    $controlfield = new DropList("groupid", "Group");
    $controlfield->connectDB("groups", array("id", "name"));
    $controlfield->list->prepend(array("-1","(none)"));
    $controlfield->setFormat("id", "%s", array("name"), " (%s)", array("longname"));
    $f->controlField($controlfield, "groupid", array('total' => 3));
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
