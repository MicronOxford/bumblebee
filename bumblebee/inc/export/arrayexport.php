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

  function ArrayExport(&$dblist, $breakfield) {
    $this->dblist   =& $dblist;
    $this->breakfield = $breakfield;
  }

  function makeExportArray() {
    $ea = array();   //export array
    $ea[] = array('type' => EXPORT_REPORT_START,  'data' => '');
    $ea[] = array('type' => EXPORT_REPORT_HEADER, 'data' => $this->header);
    $entry = 0;
    $numcols = count($this->dblist->formatdata[0]);
    $breakfield = $this->breakfield;
    $breakReport = (!empty($breakfield) && isset($this->dblist->data[$entry][$breakfield]));
    while ($entry < count($this->dblist->formatdata)) {
      //$this->log('Row: '.$entry);
      if ($breakReport) {
        $ea[] = array('type' => EXPORT_REPORT_SECTION_HEADER, 'data' => $this->_sectionHeader($this->dblist->data[$entry]));;
        $initial = $this->dblist->data[$entry][$breakfield];
      }
      $ea[] = array('type' => EXPORT_REPORT_TABLE_START,  'data' => '');
      $ea[] = array('type' => EXPORT_REPORT_TABLE_HEADER, 'data' => $this->dblist->outputHeader());
      while ($entry < count($this->dblist->formatdata) 
                && (! $breakReport
                    || $initial == $this->dblist->data[$entry][$breakfield]) ) {
        $ea[] = array('type' => EXPORT_REPORT_TABLE_ROW, 'data' => $this->dblist->formatdata[$entry]);
        $entry++;
      }
      $ea[] = array('type' => EXPORT_REPORT_TABLE_END,   'data' => '');
    }  
    $ea[] = array('type' => EXPORT_REPORT_END,  'data' => '');
    $ea['metadata'] = array('numcols'=>$numcols);
    //preDump($ea);
    $this->export =& $ea;
  }
  
  function _sectionHeader($row) {
    $s = $row[$this->breakfield];
    return $s;
  }  
  
  


} // class ArrayExport

?> 
