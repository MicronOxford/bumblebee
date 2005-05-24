<?php
# $Id$
# textfield object

include_once 'textfield.php';
include_once 'inc/typeinfo.php';

class IdField extends TextField {

  function IdField($name, $longname='', $description='') {
    parent::TextField($name, $longname, $description);
  }

  function displayInTable($cols) {
    if ($this->value != -1) {
      $this->editable = 0;
      $t = parent::displayInTable($cols);
      $this->editable = 1;
      return $t;
    } else {
      return $this->hidden();
    }
  }

} // class IdField


?> 
