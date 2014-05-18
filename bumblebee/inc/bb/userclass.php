<?php
/**
* User class name
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
* User class name
*
* @package    Bumblebee
* @subpackage DBObjects
*/
class UserClass extends DBRow {
  
  function UserClass($id) {
    //$this->DEBUG=10;
    $this->DBRow('userclass', $id);
    $this->deleteFromTable = 0;
    $this->editable = 1;
    $f = new IdField('id', T_('Class ID'));
    $f->editable = 0;
    $this->addElement($f);
    $f = new TextField('name', T_('User Class name'));
    $attrs = array('size' => '24');
    $f->setAttr($attrs);
    $f->required = 1;
    $f->isValidTest = 'is_nonempty_string';
    $this->addElement($f);
    $this->fill($id);
    $this->dumpheader = 'UserClass object';
  }

  function display() {
    return $this->displayAsTable();
  }

} //class Group
