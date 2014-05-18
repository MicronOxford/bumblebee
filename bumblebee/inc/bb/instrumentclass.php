<?php
/**
* Instrument class name
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
require_once 'inc/formslib/textfield.php';
require_once 'inc/formslib/idfield.php';

/**
* Instrument class name
*
* @package    Bumblebee
* @subpackage DBObjects
*/
class InstrumentClass extends DBRow {
  
  function InstrumentClass($id) {
    //$this->DEBUG=10;
    $this->DBRow('instrumentclass', $id);
    $this->deleteFromTable = 0;
    $this->editable = 1;
    $f = new IdField('id', T_('Class ID'));
    $f->editable = 0;
    $this->addElement($f);
    $f = new TextField('name', T_('Instrument Class name'));
    $attrs = array('size' => '24');
    $f->setAttr($attrs);
    $f->required = 1;
    $f->isValidTest = 'is_nonempty_string';
    $this->addElement($f);
    $this->fill($id);
    $this->dumpheader = 'InstrumentClass object';
  }

  function display() {
    return $this->displayAsTable();
  }

} //class Group
