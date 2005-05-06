<?php
# $Id$
# Project object (extends dbo), with extra customisations for other links

include_once 'dbobject.php';
include_once 'textfield.php';
include_once 'simplelist.php';
include_once 'radiolist.php';

class Project extends DBO {
  
  function Project($id) {
    DBO::DBO("projects", $id);
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
    $bands = new SimpleList("userclass", "id", "name", "NULL");
    $newchargename = new TextField("name","");
    $newchargename->namebase = "newcharge-";
    $newchargename->setAttr(array('size' => 24));
    $bands->append("-1","Create new: ", $newchargename);
    $f->setChoices($bands);
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
