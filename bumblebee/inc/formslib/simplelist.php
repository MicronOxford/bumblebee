<?php
# $Id$
# return a simple choice list based on an SQL statement

/* WARNING: this appears to be DEAD CODE: that means that it might be pretty buggy too */


class SimpleList {
  var $table;
  var $restriction;
  var $value;
  var $key;
  var $list;
  var $fatal_sql=1;

  function SimpleList($table, $key='id', $value='name', $longvalue='longname',
                      $restriction='1', $order='name') {
    die(__FILE__.' is not dead code.');
    $this->table = $table;
    $this->key = $key;
    $this->value = $value;
    $this->longvalue = $longvalue;
    $this->restriction = $restriction;
    $this->order = $order;
    $this->_populate();
  }

  function _populate() {
    global $TABLEPREFIX;
    //FIXME: can we do this using quickSQLSelect()?
    $q = "SELECT $this->key AS 'key', "
        ."$this->value AS 'value', "
        ."$this->longvalue AS 'longvalue' "
        ."FROM $TABLEPREFIX$this->table AS $this->table"
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
  
  function prepend($key, $value, $field='') {
    array_unshift($this->list, array('key'=>$key,
                                     'value'=>$value, 
                                     'field'=>$field));
  }

  function append($key, $value, $field='') {
    array_push($this->list, array('key'=>$key,
                                     'value'=>$value, 
                                     'field'=>$field));
  }

  function display() {
    return '<pre>SimpleList:'."\n".print_r($this->list, true).'</pre>';
  }

} // class SimpleList


?> 
