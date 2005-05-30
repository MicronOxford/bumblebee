<?php
# $Id$
# generic database list/export class

include_once 'choicelist.php';
include_once 'inc/exportcodes.php';

class DBList {
  var $restriction;
  var $join = array();
  var $order;
  var $group;
  var $union;
  var $returnFields;
  var $omitFields = array();
  var $fieldOrder;
  var $formatter;
  var $distinct = 0;
  var $table;
  var $data;
  var $formatdata;
  var $outputFormat = EXPORT_FORMAT_CUSTOM;
  var $fatal_sql = 1;
  
  function DBList($table, $returnFields, $restriction, $distinct=false) {
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
  }

  function fill() {
    // construct the query
    if (is_array($this->union) && count($this->union)) {
      $union = array();
      foreach ($this->union as $u) {
        $union[] = $u->_getSQLsyntax();
      }
      $q = '('.join($union, ') UNION (').')';
      $q .= (is_array($this->order) ? ' ORDER BY '.join($this->order,', ') : '');
      $q .= (is_array($this->group) ? ' GROUP BY '.join($this->group,', ') : '');
    } else {
      $q = $this->_getSQLsyntax();
    }
    $sql = db_get($q, $this->fatal_sql);
    $this->data = array();
    // FIXME: mysql specific functions
    while ($g = mysql_fetch_array($sql)) {
      $this->data[] = $g;
    }
  }
  
  function _getSQLsyntax() {
    global $TABLEPREFIX;
    $fields = array();
    foreach ($this->returnFields as $v) {
      $fields[] = $v->name .(isset($v->alias) ? ' AS '.$v->alias : '');
    }
    $q = 'SELECT '.($this->distinct ? 'DISTINCT ' : ' ')
          .join($fields, ', ')
        .' FROM '.$TABLEPREFIX.$this->table.' AS '.$this->table.' ';
    foreach ($this->join as $t) {
      $q .= ' LEFT JOIN '.$TABLEPREFIX.$t['table'].' AS '.(isset($t['alias']) ? $t['alias'] : $t['table'])
           .' ON '.$t['condition'];
    }
    $q .= ' WHERE '. join($this->restriction, ' AND ');
    $q .= (is_array($this->order) ? ' ORDER BY '.join($this->order,', ') : '');
    $q .= (is_array($this->group) ? ' GROUP BY '.join($this->group,', ') : '');
    return $q;
  }

  function formatList() {
    //preDump($this->omitFields);
    $this->formatdata = array();
    if (! is_array($this->fieldOrder)) {
      $this->fieldOrder = array();
      foreach ($this->returnFields as $f) {
        $this->fieldOrder[] = $f->alias;
      }
    }
    for ($i=0; $i<count($this->data); $i++) {
      $this->formatdata[$i] = $this->format($this->data[$i]);
    }
  }
    
  function format($data/*, $isHeader=false*/) {
    $d = array();
    foreach ($this->fieldOrder as $f) {
      if (! array_key_exists($f, $this->omitFields)) {
        $d[$f] = $data[$f];
      }
    }
    if (EXPORT_FORMAT_CSV & $this->outputFormat) 
      return join(preg_replace(array('/"/',     '/^(.*,.*)$/'), 
                               array('\\"',   '"$1"'       ), $d), ',');
    if (EXPORT_FORMAT_TAB & $this->outputFormat) 
        return join(preg_replace("/^(.*\t.*)$/", '"$1"', $d), "\t");
    if (EXPORT_FORMAT_USEARRAY & $this->outputFormat) 
        return $this->_makeArray($d/*, $isHeader*/);
        
    return $this->formatter->format($d);
  }
    
  function outputHeader() {
    $d = array();
    foreach ($this->returnFields as $f) {
      $d[$f->alias] = $f->heading;
    }
    return $this->format($d/*, true*/);
  }

  function _makeArray($d/*, $isHeader=false*/) {
    $row = array();
    foreach ($d as $alias => $val) {
      for ($i=0; $i<count($this->returnFields) && $this->returnFields[$i]->alias != $alias; $i++) {
      }
      $f = $this->returnFields[$i];
      $cell = array();
      //$cell['value'] = $this->formatVal($val, $f->format, $isHeader);
      $cell['value'] = $val;
      $cell['format'] = $f->format;
      $cell['width'] =  isset($f->width) ? $f->width : 10;
      $row[] = $cell;
    }
    return $row;
  }

/*  function formatVal($val, $format, $isHeader=false) {
    global $CONFIG;
    if ($isHeader)
      return $val;
    switch ($format & EXPORT_HTML_NUMBER_MASK) {
      case EXPORT_HTML_MONEY:
        $val = sprintf($CONFIG['export']['moneyFormat'], $val);
        break;
      case EXPORT_HTML_DECIMAL_1:
        $val = sprintf('%.1f', $val);
        break;
      case EXPORT_HTML_DECIMAL_2:
        $val = sprintf('%.2f', $val);
        break;
      default:
        //echo ($format& EXPORT_HTML_NUMBER_MASK).'<br/>';
    }
    return $val;
  }*/
    
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
