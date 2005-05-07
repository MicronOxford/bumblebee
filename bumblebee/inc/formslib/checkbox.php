<?php
# $Id$
# CheckBox object

include_once("field.php");
include_once("typeinfo.php");

class CheckBox extends Field {

  function CheckBox($name, $longname="", $description="") {
    parent::Field($name, $longname, $description);
    $this->useNullValues = 1;
  }

  function update($data) {
    if (parent::update($data)) {
      $this->log("CHECKBOX $this->name: $this->value, $this->ovalue");
      $this->value = ($this->value ? 1 : 0);
      $this->ovalue = ($this->ovalue ? 1 : 0);
      $this->log("CHECKBOX $this->name: $this->value, $this->ovalue");
      $this->changed = ($this->value != $this->ovalue);
    }
    return $this->changed;
  }

  function displayInTable($cols) {
    $errorclass = ($this->isValid ? "" : "class='inputerror'");
    $t = "<tr $errorclass><td>$this->longname</td>\n"
        ."<td title='$this->description'>";
    if ($this->editable) {
      $t .= $this->selectable();
    } else {
      $t .= xssqw($this->value);
      $t .= "<input type='hidden' name='$this->namebase$this->name' "
           ."value='".xssqw($this->value)."' />";
    }
    $t .= "</td>\n";
    for ($i=0; $i<$cols-2; $i++) {
      $t .= "<td></td>";
    }
    $t .= "</tr>";
    return $t;
  }

  function selectable() {
    $t  = "<input type='checkbox' name='$this->namebase$this->name' "
         ."value='1' ";
    $t .= (($this->value) ? 'checked="1"' : '');
    $t .= '/>';
    return $t;
  }

} // class CheckBox

?> 
