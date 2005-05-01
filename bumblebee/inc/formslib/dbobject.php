<?php
# $Id$
# database objects (self-initialising and self-updating object)

include_once("typeinfo.php");
include_once("db.php");
include_once("sql.php");

class DBO {
  var $table;
  var $idfield;
  var $idfieldreal;
  var $id=-1;
  var $fields;
  var $editable = 0;
  var $changed = 0;
  var $isValid = 0;
  var $suppressValidation = 0;
  var $dumpheader = "DBO object";
  var $fatal_sql = 1;
  var $namebase;

  var $DEBUG = 0;
  
  function DBO($table, $id, $idfield = 'id') {
    $this->table = $table;
    $this->id = $id;
    if (is_array($idfield)) {
      $this->idfieldreal = $idfield[0];
      $this->idfield = $idfield[1];
    } else {
      $this->idfield = $idfield;
    }
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
  
  function log($logstring, $prio=10) {
    if ($prio <= $this->DEBUG) {
      echo $logstring."<br />\n";
    }
  }

} // class dbo

?> 
