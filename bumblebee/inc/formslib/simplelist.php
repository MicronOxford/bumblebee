<?php
# $Id$
# return a simple choice list based on an SQL statement

class SimpleList {
  var $table,
      $restriction;
  var $value,
      $key;
  var $list;
  var $fatal_sql=1;

  function SimpleList($table, $key="id", $value="name", $longvalue="longname",
                      $restriction="1", $order="name") {
    $this->table = $table;
    $this->key = $key;
    $this->value = $value;
    $this->longvalue = $longvalue;
    $this->restriction = $restriction;
    $this->order = $order;
    $this->_populate();
  }

  function _populate() {
    $q = "SELECT $this->key AS 'key', "
        ."$this->value AS 'value', "
        ."$this->longvalue AS 'longvalue' "
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
  
  function prepend($k, $v) {
    array_unshift($this->list, array('key'=>$k,'value'=>$v));
  }

  function append($k, $v) {
    array_push($this->list, array('key'=>$k,'value'=>$v));
  }

  function display() {
    return "<pre>SimpleList:\n".print_r($this->list, true)."</pre>";
  }

} // class SimpleList


?> 
