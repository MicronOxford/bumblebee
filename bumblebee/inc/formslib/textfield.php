<?php
# $Id$
# textfield object

include_once 'field.php';
include_once 'inc/typeinfo.php';

class TextField extends Field {

  function TextField($name, $longname='', $description='') {
    parent::Field($name, $longname, $description);
  }

  function displayInTable($cols) {
    $t = '';
    if (! $this->hidden) {
      $errorclass = ($this->isValid ? '' : 'class="inputerror"');
      $t .= "<tr $errorclass><td>$this->longname</td>\n"
          ."<td title='$this->description'>";
      if ($this->editable) {
        $t .= $this->selectable();
        } else {
        $t .= $this->selectedValue();
      }
      if ($this->duplicateName) {
        $t .= '<input type="hidden" name="'.$this->duplicateName.'" '
              .'value="'.xssqw($this->getValue()).'" />';
      }
      $t .= "</td>\n";
      for ($i=0; $i<$cols-2; $i++) {
        $t .= "<td></td>";
      }
      $t .= "</tr>";
    } else {
      $t .= $this->hidden();
    }
    return $t;
  }

  function selectedValue() {
    return xssqw($this->getValue()).$this->hidden();
  }

  
  function selectable() {
    $t  = '<input type="text" name="'.$this->namebase.$this->name.'" ';
    $t .= 'title="'.$this->description.'" ';
    $t .= 'value="'.xssqw($this->getValue()).'" ';
    $t .= (isset($this->attr['size']) ? 'size="'.$this->attr['size'].'" ' : '');
    $t .= (isset($this->attr['maxlength']) ? 'maxlength="'.$this->attr['maxlength'].'" ' : '');
    $t .= '/>';
    return $t;
  }
  
  function hidden() {
    return "<input type='hidden' name='$this->namebase$this->name' "
           ."value='".xssqw($this->getValue())."' />";
  }

} // class TextField


?> 
