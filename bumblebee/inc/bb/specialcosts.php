<?php
/**
* Special costs for project/instrument usage editing
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

require_once 'inc/bb/configreader.php';

/** parent object */
require_once 'inc/formslib/dbrow.php';
require_once 'inc/formslib/idfield.php';
require_once 'inc/formslib/textfield.php';
require_once 'inc/formslib/referencefield.php';
require_once 'inc/formslib/joindata.php';

/**
* Special costs for project/instrument usage editing
*
* @package    Bumblebee
* @subpackage DBObjects
*/
class SpecialCost extends DBRow {

  function SpecialCost($id, $project, $instrument) {
    //$this->DEBUG=10;
    $this->DBRow('projectrates', $project, 'projectid');
    $this->editable = 1;
    $this->ignoreId = 1;
    $this->autonumbering = 0;
    $this->restriction = 'instrid='.qw($instrument).' AND projectid='.qw($project);
    //$this->use2StepSync = 1;

    $conf = ConfigReader::getInstance();

    $f = new ReferenceField('projectid', T_('Project'));
    $f->extraInfo('projects', 'id', 'name');
    $f->value = $project;
    $f->editable = 0;
    $f->duplicateName = 'project';
    $this->addElement($f);
    $f = new ReferenceField('instrid', T_('Instrument'));
    $f->extraInfo('instruments', 'id', 'name');
    $f->value = $instrument;
    $f->editable = 0;
    $f->duplicateName = 'instrument';
    $this->addElement($f);

    $f = new JoinData('costs',
                      'id', $id,
                      'costsettings', T_('Charging settings:'));
    //$f->protoRow->DEBUG = 10;
    $f->protoRow->autonumbering = 1;
    //$f->DEBUG=10;
    $f->reportFields[] = array('id' => 'rate');

    $rate = new IdField('id', T_('Rate ID'), T_('Rate ID'));
    $rate->value = $id;
    $f->addElement($rate);
    $cost = new CurrencyField('costfullday',
                              sprintf(T_('Full day cost (%s)'),
                                      sprintf($conf->value('language', 'money_format', '$%s'), '')),
                              T_('Cost of instrument use for a full day'), false);
    $attrs = array('size' => '6');
    $cost->setAttr(array_merge($attrs,
                   array('float' => true,
                         'precision' => $conf->value('language', 'money_decimal_places', 2))));
    $f->addElement($cost);
    $hours= new TextField('hourfactor', T_('Hourly rate multiplier'),
                          T_('Proportion of daily rate charged per hour'));
    $attrs = array('size' => '6', 'float' => true, 'precision' => 3);
    $hours->setAttr($attrs);
    $f->addElement($hours);
    $halfs= new TextField('halfdayfactor', T_('Half-day rate multiplier'),
                          T_('Proportion of daily rate charged per half-day'));
    $halfs->setAttr($attrs);
    $f->addElement($halfs);
    $discount= new TextField('dailymarkdown', T_('Daily bulk discount %'),
                          T_('Discount for each successive day&#39;s booking'));
    $discount->setAttr($attrs);
    $f->addElement($discount);

    $f->joinSetup('id', array('total' => 1));
    $f->colspan = 2;

    $this->addElement($f);

    $this->fill($id);
    $this->dumpheader = 'Cost object';
    $this->insertRow = ($id == -1);
    #preDump($this);
  }

  function delete($unused=null) {
    //delete our association in the costing table first
    //preDump($this);
    $result = $this->fields['costsettings']->rows[0]->delete();
    $this->errorMessage .= $this->fields['costsettings']->rows[0]->errorMessage;
    if ($result == STATUS_OK) {
      //then gracefully delete ourselves
      $result |= parent::delete();
    }
    return $result;
  }

  function display() {
    return $this->displayAsTable();
  }

} //class SpecialCost
