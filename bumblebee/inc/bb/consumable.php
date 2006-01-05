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

/** parent object */
include_once 'inc/formslib/dbrow.php';
/** uses fields */
include_once 'inc/formslib/textfield.php';

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
    $f = new IdField('id', 'Consumable ID');
    $f->editable = 0;
    $this->addElement($f);
    $f = new TextField('name', 'Item Code');
    $attrs = array('size' => '48');
    $f->required = 1;
    $f->isValidTest = 'is_nonempty_string';
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('longname', 'Description');
    $f->required = 1;
    $f->isValidTest = 'is_nonempty_string';
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('cost', 'Unit cost');
    $f->required = 1;
    $f->isValidTest = 'is_cost_amount';
    $f->setAttr($attrs);
    $this->addElement($f);
    $this->fill();
    $this->dumpheader = 'Consumables object';
  }

  function display() {
    return $this->displayAsTable();
  }

  function displayAsTable() {
    $t = '<table class="tabularobject">';
    foreach ($this->fields as $k => $v) {
      $t .= $v->displayInTable(2);
    }
    $t .= '</table>';
    return $t;
  }

} //class Consumable
