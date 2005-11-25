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

/** parent object */
include_once 'field.php';
/** type checking and data manipulation */
include_once 'inc/typeinfo.php';

class DummyField extends Field {

  function DummyField($name, $longname="", $description="") {
    parent::Field($name, $longname, $description);
  }

  function displayInTable($cols) {
    $t = "<input type='hidden' name='$this->name' "
             ."value='".xssqw($this->value)."' />";
    return $t;
  }

  function update() {
    return 0;
  }
  
  function isValid() {
    return 1;
  }
  
  function set() {
  }
  
  function sqlSetStr($name='') {
    return '';
  }
  
} // class DummyField


?> 
