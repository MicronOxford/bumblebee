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
    //FIXME use func_num_args and fun_get_arg
    if (isset($data[$this->formatfields[0]]) && $data[$this->formatfields[0]]) {
      $s = sprintf($this->format, $data[$this->formatfields[0]],
                                  $data[$this->formatfields[1]]);
    }
    return $s;
  }
  
} // class OutputFormatter

?> 
