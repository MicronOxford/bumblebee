<?php
# $Id$
# FieldArray -- an array of fields under common control
#

include_once('field.php');

/**
  * A class to hold an array of fields that may be subjected to common control
  * 
  * To be used in a many:many management
 **/
class FieldArray {
  var $fields;

  function FieldArray($name, $els) {
    $this->fields = array();
    for ($j=0; $j<count($els); $j++) {
      $this->fields[$j] = $els[$j];
      $this->fields[$j]->namebase = $name;
    }
  }

  /**
    * set an individual element of the array ($j) to $value
   **/
  function set($j, $value) {
    echo "fieldarray::set($j, $value) ";
    $this->fields[$j]->value = $value;
  }

  /**
    * pass the user-input $data to the update method of element
    * $j in the array
   **/
  function update($j, $data) {
    return $this->fields[$j]->update($data);
  }

  /**
    * return a string that contains the graphical representation
    * of the object
   **/
  function selectable($j) {
    return $this->fields[$j]->selectable();
  }
}

?> 
