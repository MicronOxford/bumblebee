<?php
# $Id$
# textfield object

include_once 'field.php';
include_once 'inc/typeinfo.php';

class DateField extends Field {
  var $date;

  function DateField($name, $longname="", $description="") {
    parent::Field($name, $longname, $description);
    //$this->DEBUG=10;
    $this->date = new SimpleDate(0);
  }

  function displayInTable($cols) {
    $errorclass = ($this->isValid ? "" : "class='inputerror'");
    $t = "<tr $errorclass><td>$this->longname</td>\n"
        ."<td title='$this->description'>";
    if ($this->editable && ! $this->hidden) {
      $t .= $this->selectable();
    } else {
      if (!$this->hidden) $t .= xssqw($this->value);
      $t .= $this->hidden();
    }
    if ($this->duplicateName) {
      $t .= "<input type='hidden' name='$this->duplicateName' "
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
    #echo "DATE=".$this->date->datestring."\n";
    $t  = $this->date->datestring .' ';
    $t .= "<input type='hidden' name='$this->namebase$this->name' "
         ."value='".xssqw($this->date->datestring)."' />";
    return $t;
  }
  
  function hidden() {
    return "<input type='hidden' name='$this->namebase$this->name' "
           ."value='".xssqw($this->value)."' />";
  }

  
  /**
   * Set the date (and value)
   *
   * @param SimpleDate $time 
   */
  function setDate($date) {
    $this->date = new SimpleDate($date);
    $this->value = $this->date->datestring;
//     $this->set($this->time->timestring);
  }
  
  /**
   * overload the parent's update method so that local calculations can be performed
   *
   * @param array $data html_name => value pairs
   *
   * @return boolean the value was updated
   */
  function update($data) {
    if (parent::update($data)) {
      $this->setDate($this->value);
    }
    return $this->changed;
  }

}

?> 
