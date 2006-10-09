<?php
/**
* a dummy field does not exist in the database but stores data in the form
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

/** parent object */
require_once 'field.php';
/** type checking and data manipulation */
require_once 'inc/typeinfo.php';

/**
* a dummy field does not exist in the database but stores data in the form
*
* @package    Bumblebee
* @subpackage FormsLibrary
*/
class DummyField extends Field {

  /**
  *  Create a new dummy field
  *
  * @param string $name   the name of the field (db name, and html field name
  * @param string $longname  long name to be used in the label of the field in display
  * @param string $description  used in the html title or longdesc for the field
  */
  function DummyField($name, $longname="", $description="") {
    parent::Field($name, $longname, $description);
  }

  function displayInTable($cols=3) {
    $t = "<input type='hidden' name='$this->name' "
             ."value='".xssqw($this->value)."' />";
    return $t;
  }

  function update($data) {
    return 0;
  }

  function isValid() {
    return 1;
  }

  function set($value) {
  }

  function sqlSetStr($name='', $force=false) {
    return '';
  }

} // class DummyField


?>
