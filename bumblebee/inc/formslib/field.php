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
      $changed = 0;
  var $attr;

  function Field($name, $longname="", $description="") {
    $this->name = $name;
    $this->longname = $longname;
    $this->description = $description;
  }

  function update($data, $base="") {
    if (isset($data["$base-$this->name"])) {
      $this->ovalue = $this->value;
      $this->value = $data["$base-$this->name"];
      $this->changed = ($this->value != $this->ovalue);
    }
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
    $t .= "\n";
    return $t;
  }

  function display() {
    return $this->text_dump();
  }

} // class Field


?> 
