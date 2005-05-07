<?php
# $Id$
# dummyfield object -- has no SQL representation, just place holds to create
# form data

include_once 'field.php';
include_once 'inc/typeinfo.php';

class DummyField extends Field {

  function DummyField($name, $longname="", $description="") {
    parent::Field($name, $longname, $description);
  }

  function displayInTable($cols) {
    $t = "<input type='hidden' name='$this->name' "
             ."value='".xssqw($this->value)."' />";
    return $t;
  }

  function update() {
    return 0;
  }
  
  function isValid() {
    return 1;
  }
  
  function set() {
  }
  
  function sqlSetStr() {
    return '';
  }
  
} // class DummyField


?> 
