<?php
# $Id$
# output formatter

class OutputFormatter {
  var $format;
  var $formatfields;

  function OutputFormatter($format, $fields) {
    $this->format = $format;
    $this->formatfields = $fields;
  }

  function format($data) {
    $t = '';
    #preDump($this);
    #preDump($data);
    if (is_array($this->formatfields)) {
      $s = array();
      foreach ($this->formatfields as $k => $v) {
        $s[] = isset($data[$v]) ? xssqw($data[$v]) : '';
        #if (isset($data[$v]) && $data[$v]) {
          #$s = $data[$v];
          #$t .= sprintf($this->format, $s);
        #}
      }
      $t .= vsprintf($this->format, $s);
    } else {
     $s = $this->formatfields->format($data);
      if ($s != '') {
        $t .= sprintf($this->format, xssqw($s));
      }
    }
    return $t;
  }
  
} // class OutputFormatter

?> 
