<?php
/**
* Output formatter object that controls output of other objects with sprintf statements
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage FormsLibrary
*/

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
      foreach ($this->formatfields as $v) {
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
