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
    $s = "";
    //FIXME use func_num_args and fun_get_arg
    foreach ($this->formatfields as $k => $v) {
      #if (isset($data[$this->formatfields[0]]) && $data[$this->formatfields[0]]) {
      if (isset($data[$v]) && $data[$v]) {
        $s = sprintf($this->format, $data[$v]);
      }
    }
    return $s;
  }
  
} // class OutputFormatter

?> 
