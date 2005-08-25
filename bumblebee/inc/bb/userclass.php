<?php
# $Id$
# Group object (extends dbo)

include_once 'inc/formslib/dbrow.php';
include_once 'inc/formslib/textfield.php';
include_once 'inc/formslib/idfield.php';

class UserClass extends DBRow {
  
  function UserClass($id) {
    //$this->DEBUG=10;
    $this->DBRow('userclass', $id);
    $this->deleteFromTable = 0;
    $this->editable = 1;
    $f = new IdField('id', 'Class ID');
    $f->editable = 0;
    $this->addElement($f);
    $f = new TextField('name', 'User Class name');
    $attrs = array('size' => '24');
    $f->setAttr($attrs);
    $f->required = 1;
    $f->isValidTest = 'is_nonempty_string';
    $this->addElement($f);
    $this->fill($id);
    $this->dumpheader = 'UserClass object';
  }

  function display() {
    return $this->displayAsTable();
  }

} //class Group
