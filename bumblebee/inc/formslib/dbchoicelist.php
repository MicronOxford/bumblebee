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
      $this->list = array();
      while ($g = mysql_fetch_array($sql)) {
        $this->list[] = $g; #['key']] = $g['value'];
      }
      return 1;
    }
  }
  
  function _mkaddedarray($values, $field='') {
    $a = array();
    for ($i=0; $i < count($values); $i++) {
      $a[$this->fields[$i]] = $values[$i];
    }
    $a['_field'] = $field;
    return $a;
  }

  function append($values, $field='') {
    array_push($this->list, $this->_mkaddedarray($values, $field));
  }

  function prepend($values, $field='') {
    array_unshift($this->list, $this->_mkaddedarray($values, $field));
  }

  function display() {
    return $this->text_dump();
  }

  function text_dump() {
    return "<pre>SimpleList:\n".print_r($this->list, true)."</pre>";
  }

  function update($newval, $data) {
    #echo "List update: ";
    if (isset($newval)) {
      #echo "set '$newval'";
      if ($value != $newval) {
        $this->changed += 1;
        //FIXME check validity of input here
        $this->id = $newval;
      }
    }
    #echo "<br />";
    #because we are a selection list, if we have changed, then we
    #may need to sync() and then fill() to make sure we are all there for the
    #next viewing and for sync() of the main object
    if ($this->changed && $this->id == -1 && ! $this->invalid) {
      #find out the new name
      //FIXME surely there's a better way of doing this?
      foreach ($this->list as $k => $v) {
        if (isset($v['_field'])) {
          $this->list[$k]['_field']->update($data);
        }
      }
      #echo "Syncing<br />";
      $this->sync();
      //FIXME this means that the "Create new:" or whatever field is lost
      $this->fill();
    }
    return $this->invalid;
  }

  function sync() {
    #returns false on success
    if ($this->changed && ! $this->invalid) {
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
      foreach ($this->list as $k => $v) {
        if (isset($v['_field'])) {
          $vals[] = $v['_field']->name ."=". qw($v['_field']->value);
        }
      }
    }
    #echo "<pre>"; print_r($this->list); echo "</pre>";
    #echo "<pre>"; print_r($this->fields); echo "</pre>";
    #echo "<pre>"; print_r($vals); echo "</pre>";
    return join(",",$vals);
  }

} // class DBList

?> 
