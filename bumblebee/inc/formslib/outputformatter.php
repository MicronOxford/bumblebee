<?php
# $Id$
# output formatter

class OutputFormatter {
  var $format, $formatfields;

  function OutputFormatter($format, $fields) {
    $this->format = $format;
    $this->formatfields = $fields;
  }

  function format($data) {
    $t = "";
    if (is_array($this->formatfields)) {
      foreach ($this->formatfields as $k => $v) {
        if (isset($data[$v]) && $data[$v]) {
          $s = $data[$v];
          $t .= sprintf($this->format, $s);
        }
      }
    } else {
     $s = $this->formatfields->format($data);
      if ($s != "") {
        $t .= sprintf($this->format, $s);
      }
    }
    return $t;
  }
  
} // class OutputFormatter

?> 
