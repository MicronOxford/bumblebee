<?php
# $Id$
# database objects (self-initialising and self-updating object)

include_once("typeinfo.php");

class Field {
  var $name,
      $longname,
      $description,
      $required = 0,
      $value,
      $ovalue;
  var $editable = -1, 
      $changed = 0,
      $invalid = 0,
      $suppressValidation = -1;
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
    if (isset($data["$this->namebase$this->name"])) {
      $newval = issetSet($data, "$this->namebase$this->name");
      #echo "$this->name, $this->value, $newval<br />\n";
      if ($this->editable) {
        # we ignore new values if the field is not editable
        if ($this->changed = ($this->value != $newval)) {
          $this->ovalue = $this->value;
          $this->value = $newval;
        }
      }
    }
    #echo $this->changed;
    return $this->changed;
  }

  function isinvalid() {
    /*
    echo "<br />";
    echo $this->name .":". $this->isInvalidTest.":";
    echo is_callable($this->isInvalidTest);
    echo "$this->suppressValidation";
    echo "req=$this->required";
    */
    if ($this->required) {
      $this->invalid = ! (isset($this->value) && $this->value != "");# ? 0 : 1;
    }
    if (! $this->invalid && 
        isset($this->isInvalidTest) && is_callable($this->isInvalidTest) 
        && $this->suppressValidation == 0) {
      #echo "checking ";
      $validator = $this->isInvalidTest;
      #$this->invalid = $this->id == -1 && $validator($this->value);
      $this->invalid = $validator($this->value);
      #echo ($this->invalid ? "INVALID" : "VALID");
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
