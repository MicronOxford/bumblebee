<?php
# $Id$
# reflect all submitted data back to the user


/**
 * Reflects all inputted data through hidden fields
 * Can optionally exclude some fields
 *
 * Typical usage:
 *     $ref = new DataReflector();
 *     $ref->exclude(array('id', 'name'));
 *     $ref->excludeRegEx(array('/^setting-.+/'));
 *     echo $ref->display($_POST);
 */
class DataReflector {
  var $excludes = array();
  var $excludesRegEx = array();
  var $DEBUG = 0;

  function DataReflector() {
  }
  
  function display($PD) {
    $t = '';
    foreach ($PD as $key => $val) {
      if (in_array($key, $this->excludes)) {
        break;
      }
      foreach ($this->excludesRegEx as $re) {
        if (preg_match($re, $key)) {
          break(2);
        }
      }
      // if we got this far then we should be included.
      $t .= '<input type="hidden" name="'.$key.'" value="'.xssqw($val).'" />';
    }
    return $t;
  }

  function exclude($arr) {
    $this->excludes = $arr;
  }
  
  function excludeRegEx($arr) {
    $this->excludesRegEx = $arr;
  }
  
} // class DataReflector

?> 
