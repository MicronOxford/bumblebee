<?php
/**
* a textfield object designed to hold the database key (or id) field
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage FormsLibrary
*/

/** parent object */
include_once 'textfield.php';
/** type checking and data manipulation */
include_once 'inc/typeinfo.php';

/**
* a textfield object designed to hold the database key (or id) field
*
* @package    Bumblebee
* @subpackage FormsLibrary
*/
class IdField extends TextField {

  /**
  *  Create a new field object, designed to be superclasses
  *
  * @param string $name   the name of the field (db name, and html field name
  * @param string $longname  long name to be used in the label of the field in display
  * @param string $description  used in the html title or longdesc for the field
  */
  function IdField($name, $longname='', $description='') {
    parent::TextField($name, $longname, $description);
  }

  function displayInTable($cols) {
    if ($this->value != -1) {
      $this->editable = 0;
      $t = parent::displayInTable($cols);
      $this->editable = 1;
      return $t;
    } else {
      return $this->hidden();
    }
  }

} // class IdField


?> 
