<?php
# $Id$
# passwdfield object
# 

include_once("field.php");
include_once("typeinfo.php");

class PasswdField extends Field {

  function PasswdField($name, $longname="", $description="") {
    parent::Field($name, $longname, $description);
    trigger_error("Stub class", E_USER_WARNING);
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
    $t  = "<input type='password' name='$this->namebase$this->name' "
         ."value='".xssqw($this->value)."' ";
    $t .= (isset($this->attr['size']) ? "size='".$this->attr['size']."' " : "");
    $t .= (isset($this->attr['maxlength']) ? "maxlength='".$this->attr['maxlength']."' " : "");
    $t .= "/>";
    return $t;
  }

} // class PasswdField


?> 
