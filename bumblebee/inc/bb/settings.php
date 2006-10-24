<?php
/**
* Settings editing object
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
class Settings extends NonDBRow {

  var $sections = array();

  function Settings() {
    //$this->DEBUG=10;
    $this->nonDBRow('settings', T_('System configuration'), T_('Edit system settings'));
    $this->editable = 1;

    $this->headings = array(T_('Parameter'), T_('Value')); //, T_('Notes'));

    $config = ConfigReader::getInstance();
    $attrs = array('size' => '48');

    $sectionList = $this->getSections();
    foreach (array_keys($sectionList) as $section) {
      $s = new nonDBRow($section, $sectionList[$section], '');
      $s->headings = $this->headings;
      $s->namebase = $section;
      foreach (array_keys($config->getSection($section)) as $parameter) {
        $f = new TextField($parameter, T_($parameter));
        $f->value = $config->value($section, $parameter);
        $f->setAttr($attrs);
        $s->addElement($f);
      }
      $this->sections[] = $s;
    }

    $this->dumpheader = 'Settings object';
  }

  function display() {
    $t = '';
    foreach($this->sections as $s) {
      $t .= $s->displayInTable();
    }
    return $t;
  }

  function update($data) {
    $t = false;
    foreach($this->sections as $s) {
      $t = $s->update($data) || $t;
    }
    $this->changed = $t;
    return $t;
  }

  function sync() {
    if ($this->changed) {
      $list = array();
      foreach($this->sections as $s) {
        foreach($s->fields as $f) {
          $value = $f->value === "" ? null : $f->value;
          $list[] = new SettingsEntry($s->namebase, $f->name, $value);
        }
      }
      $model = new SettingsModel();
      $model->UpdateConfig($list);
      $model->WriteConfig();
    }
  }


  function getSections() {
    $list = array();
    $list['main']           = T_('General settings');
    $list['error_handling'] = T_('Error handling');
    $list['language']       = T_('Regional');
    $list['display']        = T_('Display');
    $list['auth']           = T_('Authentication');
    $list['instruments']    = T_('Instrument defaults');
    $list['calendar']       = T_('Calendar');
    $list['billing']        = T_('Billing');
    $list['export']         = T_('Data export');
    $list['sqldump']        = T_('Backups');
    $list['email']          = T_('Mail servers');
    $list['pdfexport']      = T_('PDF');
    return $list;
  }



} //class Settings

class SettingsModel {

  var $config;
  var $changedConfig;

  function SettingsModel() {
    $this->config = & ConfigReader::getInstance();
  }

  function WriteConfig() {
    foreach ($this->changedConfig as $entry) {
      if ($entry->value === null) {
        $entry->delete();
      } else {
        $entry->update();
      }
    }
  }

  function UpdateConfig($newConfig) {
    $this->changedConfig = array();
    foreach ($newConfig as $entry) {
      if ($entry->value !== $this->config->value($entry->section, $entry->parameter)) {
        $this->changedConfig[] = $entry;
      }
    }
  }

} //class SettingsModel

class SettingsEntry {

  var $section;
  var $parameter;
  var $value;

  var $table           = 'settings';
  var $sectionColumn   = 'section';
  var $parameterColumn = 'parameter';
  var $valueColumn     = 'value';
  var $fatal_sql = false;

  function SettingsEntry($section, $parameter, $value) {
    $this->section   = $section;
    $this->parameter = $parameter;
    $this->value     = $value;
  }

  function insert() {
    $q = "INSERT INTO $TABLEPREFIX{$this->table} "
        ."SET {$this->sectionColumn} = %s, {$this->parameterColumn} = %s, {$this->valueColumn} = %s ";
    $q = sprintf($q, qw($this->section), qw($this->parameter), qw($this->value));
    $sql_result = db_quiet($q, $this->fatal_sql);
    return $sql_result;
  }

  function delete() {
    $q = "DELETE FROM $TABLEPREFIX{$this->table} "
        ."WHERE {$this->sectionColumn} = %s AND {$this->parameterColumn} = %s "
        ."LIMIT 1";
    $q = sprintf($q, qw($this->section), qw($this->parameter));
    $sql_result = db_quiet($q, $this->fatal_sql);
    return $sql_result;
  }

  function update() {
    $this->delete();
    $this->insert();
  }


}