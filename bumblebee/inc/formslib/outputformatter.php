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

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

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
    #echo "Formatting string for $this->format with " . join(",", $data) ."<br />\n";
    $fields = is_array($this->formatfields) ? $this->formatfields : array($this->formatfields);
    $s = array();
    foreach ($fields as $v) {
      if (! is_object($v)) {
        if (isset($data[$v])) {
          $val = isset($data[$v]) ? xssqw($data[$v]) : '';
          if ($val !== '' && $val !== NULL) $s[] = $val;
        }
      } else {
        $val = $v->format($data);
        if ($val !== '' && $val !== NULL) $s[] = $val;
      }
    }
    #echo "ended up with ". join(",",$s) . "<br/><br/>\n";
    return count($s) ? vsprintf($this->format, $s) : '';
  }

} // class OutputFormatter

?>
