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
      $invalid = 0;
  var $dumpheader = "DBO object";
  var $fatal_sql = 1;
  var $namebase;

  function DBO($table, $id) {
    $this->table = $table;
    $this->id = $id;
    $this->fields = array();
  }

/*
  function update($data) {
    echo "Looking for updates: ";
    print_r($data);
    foreach ($this->fields as $k => $v) {
      echo "Check $k";
      if (isset($data["$this->namebase$k"])) {
        echo ",set '".$data["$this->namebase$k"]."'";
        $this->changed += $this->fields[$k]->update($data);
      }
      echo "<br />";
    }
  }

  function sync() {
    #returns false on success
    if ($this->changed) {
      $vals = $this->_sqlvals();
      if ($this->id != -1) {
        #it's an existing record, so update
        $q = "UPDATE ".$this->table." SET $vals WHERE id=".qw($this->id);
        $sql_result = db_quiet($q, $this->fatal_sql);
      } else {
        #it's a new record, insert it
        $q = "INSERT ".$this->table." SET $vals";
        $sql_result = db_quiet($q, $this->fatal_sql);
        $this->id = db_new_id();
        $this->fields['id']->set($this->id);
      }
      return $sql_result;
    }
  }

  function _sqlvals() {
    $vals = array();
    if ($this->changed) {
      #echo "This has changed";
      foreach ($this->fields as $k => $v) {
        if ($v->changed) {
          $vals[] = "$k=" . qw($v->value);
        }
      }
    }
    #echo "<pre>"; print_r($vals); echo "</pre>";
    return join(",",$vals);
  }
  */
/*
  function addElement($el) {
    $this->fields[$el->name] = $el;
    if ($this->fields[$el->name]->editable == -1) {
      $this->fields[$el->name]->editable = $this->editable;
    }
    if (! isset($this->fields[$el->name]->namebase)) {
      $this->fields[$el->name]->namebase = $this->namebase;
      #echo "Altered field $el->name to $this->namebase\n";
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
*/
/*
  function fill() {
    $q = "SELECT * FROM $this->table WHERE id=".qw($this->id);
    $g = db_get_single($q);
    #echo "<pre>";print_r($g);echo "</pre>";
    foreach ($this->fields as $k => $v) {
      echo "Filling $k = ".$g[$k];
      $this->fields[$k]->set($g[$k]);
      echo $this->fields[$k]->text_dump();
    }
    #in case we get no rows back from the database, we have to have an id
    #present otherwise we're in trouble next time
    echo "Completed fill, id=$this->id\n";
    $this->fields['id']->set($this->id);
  }
*/
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
