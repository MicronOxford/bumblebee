<?php
# $Id$
# textfield object

include_once("field.php");

class TextField extends Field {

  function TextField($name, $longname="", $description="") {
    Field::Field($name, $longname, $description);
  }

  function displayInTable($cols) {
    $t = "<tr><td>$this->longname</td>\n"
        ."<td title='$this->description'>";
    if ($this->editable) {
      $t .= "<input type='text' name='$this->name' value='$this->value' ";
      $t .= (isset($this->attr['size']) ? "size='".$this->attr['size']."' " : "");
      $t .= (isset($this->attr['maxlength']) ? "maxlength='".$this->attr['maxlength']."' " : "");
      $t .= "/>";
    } else {
      $t .= $this->value;
      $t .= "<input type='hidden' name='$this->name' value='$this->value' />";
    }
    $t .= "</td>\n";
    for ($i=0; $i<$cols-2; $i++) {
      $t .= "<td></td>";
    }
    $t .= "</tr>";
    return $t;
  }

} // class TextField


?> 
