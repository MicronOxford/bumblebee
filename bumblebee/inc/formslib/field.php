<?php
# $Id$
# database objects (self-initialising and self-updating object)

include_once("typeinfo.php");

class Field {
  var $name,
      $longname,
      $description,
      $value,
      $ovalue;
  var $editable = -1, 
      $changed = 0,
      $invalid = 0;
  var $attr,
      $errorclass = "error";
  var $namebase;
  var $isInvalidTest = "isset";

  function Field($name, $longname="", $description="") {
    $this->name = $name;
    $this->longname = $longname;
    $this->description = $description;
  }

  function update($data) {
    $newval = $data["$this->namebase$this->name"];
    echo "$this->name, $this->value, $newval<br />\n";
    #if (isset($newval) && $this->editable) {
    if ($this->editable) {
      # we ignore new values if the field is not editable
      if ($this->changed = ($this->value != $newval) || $newval == NULL) {
      /*
        //check the validity of the input 
        if (isset($this->validator) && is_callable($this->validator)) {
          echo $this->name .":". $this->validator."<br />" ;
          echo is_empty_string($newval);
          $validator = $this->validator;
          $this->invalid = $validator($newval);
        }
        if (! $this->isvalid) {*/
          $this->ovalue = $this->value;
          $this->value = $newval;
        #}
      }
    }
    return $this->changed;
  }

  function isinvalid() {
    echo $this->name .":". $this->isInvalidTest.":";
    echo is_callable($this->isInvalidTest);
    if (isset($this->isInvalidTest) && is_callable($this->isInvalidTest)) {
      echo "checking ";
      $validator = $this->isInvalidTest;
      #$this->invalid = $this->id == -1 && $validator($this->value);
      $this->invalid = $validator($this->value);
      echo $this->invalid;
    }
    return $this->invalid;
  }

  function set($value) {
    #echo "Updating field $this->name. New value=$value\n";
    $this->value = $value;
  }

  function setattr($attrs) {
    foreach ($attrs as $k => $v) {
      $this->attr[$k] = $v;
    }
  }

  function text_dump() {
    $t  = "$this->name =&gt; $this->value ";
    $t .= ($this->editable ? "(editable)" : "(read-only)");
    $t .= ($this->invalid ? "(invalid)" : "");
    $t .= "\n";
    return $t;
  }

  function display() {
    return $this->text_dump();
  }

} // class Field


?> 
