<?php
/**
* Group editing object
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
require_once 'inc/formslib/idfield.php';
require_once 'inc/formslib/textfield.php';

/**
* Group editing object
*
* @package    Bumblebee
* @subpackage DBObjects
*/
class Group extends DBRow {
  
  function Group($id) {
    //$this->DEBUG=10;
    $this->DBRow('groups', $id);
    $this->editable = 1;
    //$this->use2StepSync = 1;
    $this->deleteFromTable = 0;
    $f = new IdField('id', T_('Group ID'));
    $f->editable = 0;
    $this->addElement($f);
    $f = new TextField('name', T_('Addressee name'));
    $attrs = array('size' => '48');
    $f->setAttr($attrs);
    $f->required = 1;
    $f->isValidTest = 'is_nonempty_string';
    $this->addElement($f);
    $f = new TextField('longname', T_('Group name'));
    $f->setAttr($attrs);
    $f->required = 1;
    $f->isValidTest = 'is_nonempty_string';
    $this->addElement($f);
    $f = new TextField('addr1', T_('Address 1'));
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('addr2', T_('Address 2'));
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('suburb', T_('Suburb'));
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('state', T_('State'));
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('code', T_('Postcode'));
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('country', T_('Country'));
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('email', T_('Email'));
    $f->setAttr($attrs);
    $f->required = 1;
    $f->isValidTest = 'is_email_format';
    $this->addElement($f);
    $f = new TextField('fax', T_('Fax'));
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('account', T_('Account code'));
    $f->setAttr($attrs);
    $this->addElement($f);
    $this->fill($id);
    $this->dumpheader = 'Group object';
  }

  function display() {
    return $this->displayAsTable();
  }

} //class Group
