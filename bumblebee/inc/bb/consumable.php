<?php
/**
* Consumables object
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage DBObjects
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

/** parent object */
require_once 'inc/formslib/dbrow.php';
/** uses fields */
require_once 'inc/formslib/idfield.php';
require_once 'inc/formslib/textfield.php';

/**
* Consumables object
*
* @package    Bumblebee
* @subpackage DBObjects
*/
class Consumable extends DBRow {

  function Consumable($id) {
    $this->DBRow('consumables', $id);
    $this->editable = 1;
    $this->deleteFromTable = 0;
    $f = new IdField('id', T_('Consumable ID'));
    $f->editable = 0;
    $this->addElement($f);
    $f = new TextField('name', T_('Item Code'));
    $attrs = array('size' => '48');
    $f->required = 1;
    $f->isValidTest = 'is_nonempty_string';
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('longname', T_('Description'));
    $f->required = 1;
    $f->isValidTest = 'is_nonempty_string';
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new CurrencyField('cost', T_('Unit cost'), T_('Cost per item'));
    $f->required = 1;
    $f->setAttr($attrs);
    $this->addElement($f);
    $this->fill();
    $this->dumpheader = 'Consumables object';
  }

  function display() {
    return $this->displayAsTable();
  }

  function displayAsTable($cols=2) {
    $t = '<table class="tabularobject">';
    foreach ($this->fields as $k => $v) {
      $t .= $v->displayInTable($cols);
    }
    $t .= '</table>';
    return $t;
  }

} //class Consumable
