<?php
# $Id$
# database objects (self-initialising and self-updating object)

include_once("typeinfo.php");
include_once("db.php");
include_once("sql.php");

class DBO {
  var $table,
      $id=-1;
  var $fields;
  var $editable = 0, 
      $changed = 0,
      $invalid = 0,
      $suppressValidation = 0;
  var $dumpheader = "DBO object";
  var $fatal_sql = 1;
  var $namebase;

  function DBO($table, $id) {
    $this->table = $table;
    $this->id = $id;
    $this->fields = array();
  }

  function text_dump() {
    $t  = "<pre>$this->dumpheader $this->table (id=$this->id)\n{\n";
    foreach ($this->fields as $k => $v) {
      $t .= "\t".$v->text_dump();
    }
    $t .= "}\n</pre>";
    return $t;
  }

  function display() {
    return $this->text_dump();
  }

} // class dbo

?> 
