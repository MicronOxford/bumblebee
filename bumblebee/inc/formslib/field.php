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
  var $namebase;

  function Field($name, $longname="", $description="") {
    $this->name = $name;
    $this->longname = $longname;
    $this->description = $description;
  }

  function update($data) {
    $newval = $data["$this->namebase$this->name"];
    #echo "$this->name, $this->value, $newval<br />\n";
    if (isset($newval)) {
      $this->changed = ($this->value != $newval);
      if ($this->changed) {
        if ($this->editable) {
          $this->ovalue = $this->value;
          $this->value = $newval;
        } else {
          $this->changed = 0;
          #FIXME this is an error condition... flag it?
        }
      }
    }
    return $this->changed;
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
