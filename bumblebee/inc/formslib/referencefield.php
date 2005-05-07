<?php
# $Id$
# a non-editable reference object that looks up data from a join table

include_once 'field.php';
include_once 'exampleentries.php';
include_once 'inc/typeinfo.php';

class ReferenceField extends Field {
   var $example;

  function ReferenceField($name, $longname='', $description='') {
    parent::Field($name, $longname, $description);
  }

  function extraInfo($table, $matchfield, $field) {
    $this->example = new ExampleEntries('id', $table, $matchfield, $field, 1);
  }

  function displayInTable($cols) {
    $t = "<tr><td>$this->longname</td>\n"
        ."<td title='$this->description'>";
    $t .= xssqw($this->getValue());
    $refdata = array('id'=>$this->getValue());
    $t .= ' ('. $this->example->format($refdata).')';
    $t .= "<input type='hidden' name='$this->namebase$this->name' "
         ."value='".xssqw($this->getValue())."' />";
    if (isset($this->duplicateName)) {
      $t .= "<input type='hidden' name='$this->duplicateName' "
           ."value='".xssqw($this->getValue())."' />";
    }
    $t .= "</td>\n";
    for ($i=0; $i<$cols-2; $i++) {
      $t .= '<td></td>';
    }
    $t .= '</tr>';
    return $t;
  }

} // class ReferenceField


?> 
