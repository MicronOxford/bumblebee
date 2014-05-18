<?php
/**
* Project object (extends dbo), with extra customisations for other links
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
require_once 'inc/formslib/radiolist.php';
require_once 'inc/formslib/droplist.php';
require_once 'inc/formslib/joindata.php';

/**
* Project object (extends dbo), with extra customisations for other links
*
* @package    Bumblebee
* @subpackage DBObjects
*/
class Project extends DBRow {

  function Project($id) {
    $this->DBRow('projects', $id);
    //$this->DEBUG=10;
    $this->editable = 1;
    $this->use2StepSync = 1;
    $this->deleteFromTable = 0;
    $f = new IdField('id', T_('Project ID'));
    $f->editable = 0;
    $this->addElement($f);
    $f = new TextField('name', T_('Name'));
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
    $f = new RadioList('defaultclass', T_('Default charging band'));
    $f->connectDB('userclass', array('id', 'name'));
    $f->setFormat('id', '%s', array('name'));
    $newchargename = new TextField('name','');
    $newchargename->namebase = 'newcharge-';
    $newchargename->setAttr(array('size' => 24));
    $newchargename->isValidTest = 'is_nonempty_string';
    $newchargename->suppressValidation = 0;
    $f->list->append(array('-1', T_('Create new:').' '), $newchargename);
    $f->setAttr($attrs);
    $f->required = 1;
    $f->extendable = 1;
    $f->editable = 1;
    $f->isValidTest = 'is_valid_radiochoice';
    $this->addElement($f);
    $f = new JoinData('projectgroups',
                       'projectid', $this->id,
                       'groups', T_('Group membership (%)'));
    $groupfield = new DropList('groupid', 'Group');
    $groupfield->connectDB('groups', array('id', 'name', 'longname'));
    $groupfield->prepend(array('0', T_('(none)'), T_('no selection')));
    $groupfield->setDefault(0);
    $groupfield->setFormat('id', '%s', array('name'), ' (%30.30s)', array('longname'));
    $f->addElement($groupfield);
    $percentfield = new TextField('grouppc', '');
    $percentfield->isValidTest = 'is_number';
    $percentfield->setAttr(array('size' => '16', 'float' => true, 'precision' => 2));
    $f->addElement($percentfield, 'sum_is_100');
    $f->joinSetup('groupid', array('total' => 3));
    $f->colspan = 2;
    $this->addElement($f);
    $this->fill();
    $this->dumpheader = 'Project object';
  }

  function display() {
    return $this->displayAsTable();
  }


}  //class Project
