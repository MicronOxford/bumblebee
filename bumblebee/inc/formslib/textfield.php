<?php
# $Id$
# textfield object

include_once("field.php");

class TextField extends Field {

  function TextField($name, $longname="", $description="") {
    Field::Field($name, $longname, $description);
  }

  function displayInTable($cols) {
    $errorclass = ($this->invalid ? "class='inputerror'" : "");
    $t = "<tr $errorclass><td>$this->longname</td>\n"
        ."<td title='$this->description'>";
    if ($this->editable) {
      $t .= $this->selectable();
    } else {
      $t .= $this->value;
      $t .= "<input type='hidden' name='$this->namebase$this->name' "
           ."value='$this->value' />";
    }
    $t .= "</td>\n";
    for ($i=0; $i<$cols-2; $i++) {
      $t .= "<td></td>";
    }
    $t .= "</tr>";
    return $t;
  }

  function selectable() {
    $t  = "<input type='text' name='$this->namebase$this->name' "
         ."value='$this->value' ";
    $t .= (isset($this->attr['size']) ? "size='".$this->attr['size']."' " : "");
    $t .= (isset($this->attr['maxlength']) ? "maxlength='".$this->attr['maxlength']."' " : "");
    $t .= "/>";
    return $t;
  }

} // class TextField


?> 
