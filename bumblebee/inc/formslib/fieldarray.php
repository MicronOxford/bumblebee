<?php
# $Id$
# FieldArray -- an array of fields under common control
#

include_once('field.php');

class FieldArray {
  var $fields;

  function FieldArray($name, $els) {
    $this->fields = array();
    for ($j=0; $j<count($els); $j++) {
      $this->fields[$j] = $els[$j];
      $this->fields[$j]->namebase = $name;
    }
  }

  function set($j, $value) {
    $this->fields[$j]->value = $value;
  }

  function update($j, $data) {
    return $this->fields[$j]->update($data);
  }

  function selectable($j) {
    #preDump($j);
    #preDump($this->fields[$j]);
    return $this->fields[$j]->selectable();
  }
}

?> 

