<?php
# $Id$
# a choice list based on an SQL statement, live db object to add extra entries

include_once("dbobject.php");

class DBList extends DBO {
  var $restriction;
  var $editable = 0,
      $changed = 0;
  var $list;

  function DBList($table, $fields="", $restriction="1", $order="name") {
    $this->table = $table;
    $this->fields = (is_array($fields) ? $fields : array($fields));
    $this->restriction = $restriction;
    $this->order = $order;
    $this->fill();
  }

  function fill() {
    $f = implode(", ", $this->fields);
    $q = "SELECT id, $f "
        ."FROM $this->table "
        ."WHERE $this->restriction "
        ."ORDER BY $this->order";
    $sql = db_get($q, $this->fatal_sql);
    if (! $sql) {
      return 0;
    } else {
      while ($g = mysql_fetch_array($sql)) {
        $this->list[] = $g; #['key']] = $g['value'];
      }
      return 1;
    }
  }
  
  function prepend($values, $field='') {
    $a = array();
    for ($i=0; $i < count($values); $i++) {
      $a[$this->fields[$i]] = $values[$i];
    }
    $a['_field'] = $field;
    array_unshift($this->list, $a);
  }

  function append($key, $value, $field='') {
    $a = array();
    for ($i=0; $i < count($values); $i++) {
      $a[$this->fields[$i]] = $values[$i];
    }
    $a['_field'] = $field;
    array_push($this->list, $a);
  }

  function display() {
    return $this->text_dump();
  }

  function text_dump() {
    return "<pre>SimpleList:\n".print_r($this->list, true)."</pre>";
  }

  function update($data) {
    echo "List update: ";
    print_r($data);
    if (isset($data)) {
      echo "set '$data'";
      if ($value != $data) {
        $this->changed += 1;
        $id = $data;
      }
    }
    echo "<br />";
  }

  function sync() {
    #returns false on success
    if ($this->changed) {
      $vals = $this->_sqlvals();
      if ($this->id != -1) {
        #it's an existing record, but we don't make changes to
        #records through this object
      } else {
        #it's a new record, insert it
        $q = "INSERT ".$this->table." SET $vals";
        $sql_result = db_quiet($q, $this->fatal_sql);
        $this->id = db_new_id();
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

} // class DBList

?> 
