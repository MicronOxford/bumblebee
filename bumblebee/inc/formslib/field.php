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
      $isValid = 0,
      $suppressValidation = -1;
  var $attr,
      $errorclass = "error";
  var $namebase;
  var $isValidTest = "isset";

  function Field($name, $longname="", $description="") {
    $this->name = $name;
    $this->longname = $longname;
    $this->description = $description;
  }

  function update($data) {
    if (isset($data["$this->namebase$this->name"])) {
      $newval = issetSet($data, "$this->namebase$this->name");
      echo "$this->name, $this->value, $newval<br />\n";
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

  function isValid() {
    /**/
    echo "<br />";
    echo $this->name .":". $this->isValidTest.":";
    echo is_callable($this->isValidTest);
    echo "$this->suppressValidation";
    echo "req=$this->required";
    /**/
    $this->isValid = 1;
    if ($this->required) {
      $this->isValid = (isset($this->value) && $this->value != "");
    }
    if ($this->isValid && 
        isset($this->isValidTest) && is_callable($this->isValidTest) 
        && $this->suppressValidation == 0) {
      #echo "checking ";
      $validator = $this->isValidTest;
      #$this->invalid = $this->id == -1 && $validator($this->value);
      $this->isValid = $validator($this->value);
    }
    echo ($this->isValid ? "VALID" : "INVALID");
    return $this->isValid;
  }

  function set($value) {
    #echo "Updating field $this->name. New value=$value\n";
    $this->value = $value;
  }

  /**
   * return a SQL-injection-cleansed string that can be used in an SQL
   * UPDATE or INSERT statement. i.e. "name='Stuart'".
  **/
  function sqlSetStr() {
    return $this->name .'='. qw($this->value);
  }

  function setattr($attrs) {
    foreach ($attrs as $k => $v) {
      $this->attr[$k] = $v;
    }
  }

  function text_dump() {
    $t  = "$this->name =&gt; $this->value ";
    $t .= ($this->editable ? "(editable)" : "(read-only)");
    $t .= ($this->isValid ? "" : "(invalid)");
    $t .= "\n";
    return $t;
  }

  function display() {
    return $this->text_dump();
  }

} // class Field


?> 
