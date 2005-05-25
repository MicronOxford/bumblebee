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
    global $TABLEPREFIX;
    // start constructing the query
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
    $fields = array();
    $q .= ' WHERE '. join($this->restriction, ' AND ');
    $q .= (is_array($this->order) ? ' ORDER BY '.join($this->order,', ') : '');
    $q .= (is_array($this->group) ? ' GROUP BY '.join($this->group,', ') : '');
    $sql = db_get($q, $this->fatal_sql);
    $this->data = array();
    // FIXME: mysql specific functions
    while ($g = mysql_fetch_array($sql)) {
      $this->data[] = $g;
    }
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
    
  function format($data, $isHeader=false) {
    $d = array();
    foreach ($this->fieldOrder as $f) {
      if (! array_key_exists($f, $this->omitFields)) {
        $d[$f] = $data[$f];
      }
    }
    switch ($this->outputFormat) {
      case EXPORT_FORMAT_CSV:
        return join(preg_replace(array('/"/',     '/^(.*,.*)$/'), 
                               array('\\"',   '"$1"'       ), $d), ',');
      case EXPORT_FORMAT_TAB:
        return join(preg_replace("/^(.*\t.*)$/", '"$1"', $d), "\t");
      case EXPORT_FORMAT_HTML:
        return $this->_formatHTML(array_xssqw($d), $isHeader);
      case EXPORT_FORMAT_CUSTOM:
      default:
        return $this->formatter->format($d);
    }
  }

  function _formatHTML($d, $isHeader=false) {
    global $CONFIG;
    $t = '';
    foreach ($d as $alias => $val) {
      for ($i=0; $i<count($this->returnFields) && $this->returnFields[$i]->alias != $alias; $i++) {
      }
      $f = $this->returnFields[$i];
      if (! $isHeader) {
        switch($f->format & EXPORT_HTML_ALIGN) {
          case EXPORT_HTML_CENTRE:
            $align='center';
            break;
          case EXPORT_HTML_LEFT:
            $align='left';
            break;
          case EXPORT_HTML_RIGHT:
            $align='center';
            break;
          default:
            $align='';
        }
        switch ($f->format & EXPORT_HTML_NUMBER) {
          case EXPORT_HTML_MONEY:
            $val = sprintf($CONFIG['export']['moneyFormat'], $val);
            break;
          case EXPORT_HTML_DECIMAL:
            break;
          case EXPORT_HTML_DECIMAL_1:
            $val = sprintf('%.1f', $val);
            break;
          case EXPORT_HTML_DECIMAL_2:
            $val = sprintf('%.2f', $val);
            break;
        }
        $align = ($align!='' ? 'align='.$align : '');
        $t .= '<td '.$align.'>'.$val.'</td>';
      } else {
        $t .= '<th>'.$val.'</th>';
      }
    }
    return $t;
  }
    
  function outputHeader() {
    $d = array();
    foreach ($this->returnFields as $f) {
      $d[$f->alias] = $f->heading;
    }
    return $this->format($d, true);
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
