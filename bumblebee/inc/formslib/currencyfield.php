<?php
/**
* adaptation of the textfield widget primitive to currency input
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
require_once 'textfield.php';

/**
* adaptation of the textfield widget primitive to currency input
*
* Designed for currency numbers to be edited in a text field widget in the HTML form
*
* @package    Bumblebee
* @subpackage FormsLibrary
*/
class CurrencyField extends TextField {

  /**
  *  Create a new field object, designed to be superclasses
  *
  * @param string $name   the name of the field (db name, and html field name
  * @param string $longname  long name to be used in the label of the field in display
  * @param string $description  used in the html title or longdesc for the field
  */
  function CurrencyField($name, $longname='', $description='', $allowBlanks=true) {
    parent::TextField($name, $longname, $description);
    $this->valueCleaner = 'currencyValueCleaner';
    $this->isValidTest = $allowBlanks ? 'is_cost_amount_or_blank' : 'is_cost_amount';
  }

} // class TextField

?>
