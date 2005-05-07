<?php
# $Id$
# provide some example entries for existing values next to the choices in a
# radiolist

include_once 'dbchoicelist.php';
include_once 'sql.php';

class ExampleEntries {
  var $source;
  var $table,
      $columnmatch,
      $columnreturn;
  var $limit,
      $order;
  var $separator = ', ';
  var $list;

  function ExampleEntries($source, $table, $columnmatch, $columnreturn,
                          $maxentries=3, $order='') {
    $this->source = $source;
    $this->table = $table;
    $this->columnmatch = $columnmatch;
    $this->columnreturn = $columnreturn;
    $this->limit = $maxentries;
    $this->order = ($order != '' ? $order : $columnreturn);
  }

  function fill($id) {
    #echo "Filling for $id";
    $safeid = qw($id);
    $this->list = new DBChoiceList($this->table, $this->columnreturn,
                             "$this->columnmatch=$safeid",
                             $this->order,
                             $this->columnmatch, $this->limit);
  }
    
  function format(&$data) {
    #var_dump($data);
    $this->fill($data[$this->source]);
    $entries = array();
    foreach ($this->list->choicelist as $k => $v) {
      $entries[] = $v[$this->columnreturn];
    }
    $t = implode($this->separator, $entries);
    return $t;
  }

} // class ExampleEntries


?> 
