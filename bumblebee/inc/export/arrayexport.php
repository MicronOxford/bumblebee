<?php
# $Id$
# construct an array for exporting the data

include_once 'inc/exportcodes.php';

/**
 *  Create a monolithic array with all the data for export
 *
 */

class ArrayExport {
  var $dblist;
  var $exporter;
  var $export;
  var $header;
  var $author = 'BumbleBee';
  var $creator = 'BumbleBee Instrument Management System : bumblebeeman.sf.net';
  var $subject = 'Instrument and consumable usage report';
  var $keywords = 'instruments, consumables, usage, report, billing, invoicing';
  var $_totals;
  var $_doingTotalCalcs = false;

  function ArrayExport(&$dblist, $breakfield) {
    $this->dblist   =& $dblist;
    $this->breakfield = $breakfield;
  }

  function makeExportArray() {
    $ea = array();   //export array
    $ea[] = array('type' => EXPORT_REPORT_START,  
                  'data' => '');
    $ea[] = array('type' => EXPORT_REPORT_HEADER, 
                  'data' => $this->header);
    $entry = 0;
    $numcols = count($this->dblist->formatdata[0]);
    $breakfield = $this->breakfield;
    $breakReport = (!empty($breakfield) && isset($this->dblist->data[$entry][$breakfield]));
    //echo $breakReport ? 'Breaking' : 'Not breaking';
    while ($entry < count($this->dblist->formatdata)) {
      //$this->log('Row: '.$entry);
      $this->_resetTotals();
      $ea[] = array('type' => EXPORT_REPORT_SECTION_HEADER, 
                    'data' => $this->_sectionHeader($this->dblist->data[$entry]),
                    'metadata' => $this->_getColWidths($numcols, $entry));
      if ($breakReport) {
        $initial = $this->dblist->data[$entry][$breakfield];
      }
      $ea[] = array('type' => EXPORT_REPORT_TABLE_START,  
                    'data' => '');
      $ea[] = array('type' => EXPORT_REPORT_TABLE_HEADER, 
                    'data' => $this->dblist->outputHeader());
      while ($entry < count($this->dblist->formatdata) 
                && (! $breakReport
                    || $initial == $this->dblist->data[$entry][$breakfield]) ) {
        $ea[] = array('type' => EXPORT_REPORT_TABLE_ROW, 
                      'data' => $this->_formatRowData($this->dblist->formatdata[$entry]));
        $this->_incrementTotals($this->dblist->formatdata[$entry]);
        $entry++;
      }
      if ($this->_doingTotalCalcs) {
        $ea[] = array('type' => EXPORT_REPORT_TABLE_TOTAL, 
                      'data' => $this->_getTotals());
      }
      $ea[] = array('type' => EXPORT_REPORT_TABLE_END,   
                    'data' => '');
    }  
    $ea[] = array('type' => EXPORT_REPORT_END,  
                  'data' => '');
    $ea['metadata'] = $this->_getMetaData();
    //preDump($ea);
    $this->export =& $ea;
  }
  
  function _sectionHeader($row) {
    $s = '';
    if (empty($this->breakfield)) {
      $s .= $this->header;
    } else {
      $s .= $row[$this->breakfield];
    }
    return $s;
  }  
  
  function _getColWidths($numcols, $entry) {
    $columns = array();
    foreach ($this->dblist->formatdata[$entry] as $f) {
      $columns[] = $f['width'];
    }
    return array(
                  'numcols' => $numcols,
                  'colwidths' => $columns
                );
  }

  function _getMetaData() {
    return array(
                  'author'  => $this->author,
                  'creator' => $this->creator,
                  'title'   => $this->header,
                  'keywords' => $this->keywords,
                  'subject' => $this->subject
                );
  }

  function _resetTotals() {
    foreach ($this->dblist->formatdata[0] as $key => $val) {
      $this->_totals[$key] = $val;
      if ($val['format'] & EXPORT_CALC_TOTAL) {
        $this->_totals[$key]['value'] = 0;
        $this->_doingTotalCalcs = true;
      } else {
        $this->_totals[$key]['value'] = '';
      }
    }
  }
  
  function _incrementTotals($row) {
    if (! $this->_doingTotalCalcs) return;
    foreach ($row as $key => $val) {
      if ($val['format'] & EXPORT_CALC_TOTAL) {
        $this->_totals[$key]['value'] += $val['value'];
      }
    }
  }
  
  function _getTotals() {
    $total = $this->_totals;
    foreach ($total as $key => $val) {
      if ($val['format'] & EXPORT_CALC_TOTAL) {
        $total[$key]['value'] = $this->_formatVal($val['value'],$val['format']);
      }
    }
    return $total;
  }

  function _formatRowData(&$row) {
    $newrow = array();
    foreach ($row as $key => $val) {
      $newrow[$key] = $val;
      $newrow[$key]['value'] = $this->_formatVal($val['value'], $val['format']);
    }
    return $newrow;
  }
  
  function _formatVal($val, $format) {
    global $CONFIG;
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
  }
  
  function appendEA(&$ea) {
    $this->export = array_merge($this->export, $ea->export);
  }
  
      
} // class ArrayExport

?> 
