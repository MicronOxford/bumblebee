<?php
# $Id$
# textfield object

include_once("textfield.php");
include_once("typeinfo.php");

class IdField extends TextField {

  function IdField($name, $longname="", $description="") {
    parent::TextField($name, $longname, $description);
  }

  function displayInTable($cols) {
    if ($this->value != -1) {
      return parent::displayInTable($cols);
    } else {
      return '';
    }
  }

} // class IdField


?> 
