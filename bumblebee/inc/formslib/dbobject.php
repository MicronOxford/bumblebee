<?php
# $Id$
# database objects (self-initialising and self-updating object)

include_once("typeinfo.php");
include_once("db.php");

class DBO {
  var $table,
      $id=-1;
  var $fields;
  var $editable = 0, 
      $changed = 0;
  var $dumpheader = "DBO object";

  function DBO($table, $id) {
    $this->table = $table;
    $this->id = $id;
    $this->fields = array();
  }

  function update($data, $base="") {
    foreach ($this->fields as $k => $v) {
      if (isset($data["$base-$k"])) {
        #$this->changed += $this->fields[$k]->update($data)
        $this->changed += $v->update($data, $base);
      }
    }
  }

  function sync() {
    #returns 
    $vals = $this->_sqlvals();
    if ($id == -1) {
      #it's an existing record, so update
      $q = "UPDATE ".$this->table." SET $vals WHERE id=".qw($this->id);
    } else {
      #it's a new record, insert it
      $q = "INSERT ".$this->table." SET $vals";
    }
    return db_quiet($q);
  }

  function _sqlvals() {
    $vals = array();
    if ($this->changed) {
      foreach ($this->fields as $k => $v) {
        if ($v->changed) {
          $vals[] = "$k=" . qw($v->value);
        }
      }
    }
    return join(",",$vals);
  }

  function addElement($el) {
    $this->fields[$el->name] = $el;
    if ($this->fields[$el->name]->editable == -1) {
      $this->fields[$el->name]->editable = $this->editable;
    }
    #echo $el->name;
    #echo "foo:".$this->fields[$el->name]->name.":bar";
  }

  function addElements($els) {
    foreach ($els as $e) {
      #echo $e->text_dump();
      $this->addElement($e);
    }
  }

  function fill() {
    $q = "SELECT * FROM $this->table WHERE id=".qw($this->id);
    $g = db_get($q);
    #echo "<pre>";print_r($g);echo "</pre>";
    foreach ($this->fields as $k => $v) {
      #echo "Filling $k = ".$g[$k];
      $this->fields[$k]->set($g[$k]);
      #echo $this->fields[$k]->text_dump();
    }
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

function db_quiet($q) {
  #returns false on success, true (error) on failure
  $sql = mysql_query($q);
  echoSQL($q);
  if (! $sql) {
    return mysql_error();
  } else {
    return 0;
  }
}

function db_get($q) {
  #returns false on success, true (error) on failure
  $sql = mysql_query($q);
  echoSQL($q);
  if (! $sql) {
    return mysql_error();
  } else {
    return mysql_fetch_array($sql);
  }
}


?> 
