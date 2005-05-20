<?php
# $Id$
# generic database list/export class

include_once 'choicelist.php';
include_once 'inc/exportcodes.php';

class DBList {
  var $restriction;
  var $join = array();
  var $returnFields;
  var $realFields;
  var $formatter;
  var $distinct = 0;
  var $table;
  var $data;
  var $formatdata;
  var $outputFormat = EXPORT_FORMAT_CUSTOM;
  var $fatal_sql = 1;
  
  function DBList($table, $returnFields, $restriction, $distinct=0) {
    $this->table = $table;
    $this->distinct = $distinct;
    if (is_array($restriction)) {
      $this->restriction = $restriction;
    } else {
      $this->restriction = array($restriction);
    }
    if (is_array($returnFields)) {
      $this->returnFields = $returnFields;
    } else {
      $this->returnFields = array($returnFields);
    }
    foreach ($returnFields as $f) {
      if (preg_match('/^(.+)\s+AS\s+(.+)$/i', $f, $names)) {
        $this->realFields[$names[1]] = $names[2];
      } else {
        $this->realFields[$f] = $f;
      }
    }
  }

  function fill() {
    global $TABLEPREFIX;
    // start constructing the query, the 'WHERE 0 OR ...' is a pretty ugly hack
    // to ensure that we always have a valid conditional
    $q = 'SELECT '.($this->distinct ? 'DISTINCT ' : ' ')
          .join($this->returnFields, ', ')
        .' FROM '.$TABLEPREFIX.$this->table.' AS '.$this->table.' ';
    foreach ($this->join as $t) {
      $q .= ' LEFT JOIN '.$TABLEPREFIX.$t['table'].' AS '.$t['table']
           .' ON '.$t['condition'];
    }
    $q .= ' WHERE '. join($this->restriction, ' AND ');
    $sql = db_get($q, $this->fatal_sql);
    $this->data = array();
    // FIXME: mysql specific functions
    while ($g = mysql_fetch_array($sql)) {
      $this->data[] = $g;
    }
  }

  function formatList() {
    $this->formatdata = array();
    for ($i=0; $i<count($this->data); $i++) {
      $this->formatdata[$i] = $this->format($this->data[$i]);
    }
  }
    
  function format($data) {
    $d = array();
    foreach ($this->realFields as $f) {
      $d[] = $data[$f];
    }
    switch ($this->outputFormat) {
      case EXPORT_FORMAT_CSV:
      // FIXME we will return twice as many elements here due to [1] and [id] from mysql
        return join(preg_replace(array('/"/',     '/^(.*,.*)$/'), 
                               array('\\"',   '"$1"'       ), $d), ',');
      case EXPORT_FORMAT_TAB:
        return join(preg_replace("/^(.*\t.*)$/", '"$1"', $d), "\t");
      case EXPORT_FORMAT_HTML:
        return '<td>'.join(array_xssqw($d), '</td><td>').'</td>';
      case EXPORT_FORMAT_CUSTOM:
      default:
        return $this->formatter->format($d);
    }
  }
  
  function outputHeader() {
    $d = array();
    foreach ($this->realFields as $label => $f) {
      $d[$f] = $label;
    }
    return $this->format($d);
  }

 /**
   * Create a set of OutputFormatter objects to handle the display of this
   * object. 
   *
   *  called as: setFormat($f1, $v1) {
   *    - f1 is an sprintf format (see PHP manual)
   *    - v1 is an array of array indices that will be used to fill the
   *      fields in the sprintf format from a $data array passed to the
   *      formatter when asked to display itself
   */
  function setFormat($f, $v) {
    $this->formatter = new OutputFormatter($f, $v);
  }

} // class DBList


?> 
