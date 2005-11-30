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

/**
* Output formatter object that controls output of other objects with sprintf statements
*
* @package    Bumblebee
* @subpackage FormsLibrary
*/
class OutputFormatter {
  /** @var string  sprintf format string for this formatter  */
  var $format;
  /** @var mixed   field name or array of names (keys to $data) to format using this formatter */
  var $formatfields;

 /**
  *  Create a new OutputFormatter object
  *
  * @param string $format  see $this->format
  * @param mixed $fields  string or array, see $this->fields
  */
   function OutputFormatter($format, $fields) {
    $this->format = $format;
    $this->formatfields = $fields;
  }

  /**
  * Format the data
  *
  * @param array $data     data to be formatted, $data[$formatfields[$i]] 
  * @return string formatted data
  */
  function format($data) {
    $fields = is_array($this->formatfields) ? $this->formatfields : array($this->formatfields);
    $s = array();
    foreach ($this->formatfields as $v) {
      $s[] = isset($data[$v]) ? xssqw($data[$v]) : '';
    }
    return vsprintf($this->format, $s);
  }
  
} // class OutputFormatter

?> 
