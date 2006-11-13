<?php
/**
* Data export rules and objects
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Export
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

/**
* Export type object -- contains all data for constructing SQL and interpretting output
*
* @package    Bumblebee
* @subpackage Export
*/
class ExportType {
  var $name;
  var $basetable;
  var $header;
  var $description;
  var $limitation;
  var $fields = array();
  var $where  = array();
  var $join   = array();
  var $pivot;
  var $fieldOrder = '';
  var $timewhere = array('bookwhen >= ', 'bookwhen < ');
  var $order;
  var $group;
  var $manualGroup;
  var $manualSum;
  var $distinct = 0;
  var $union ='';

  // rendering options
  var $omitFields = array();
  var $breakField;

  function ExportType($name, $basetable, $description, $header, $limitation) {
    $this->name        = $name;
    $this->basetable   = $basetable;
    $this->description = $description;
    $this->header      = $header;
    if (!is_array($limitation)) $limitation = array($limitation);
    $this->limitation  = $limitation;
  }

} //class ExportType

/**
* SQL fieldname: field, alias, column heading, formatting and output column width
*
* @package    Bumblebee
* @subpackage Export
*/
class sqlFieldName {
  var $name;
  var $alias;
  var $heading;
  var $format;
  var $width;

  function sqlFieldName($name, $heading=NULL, $alias=NULL, $format=NULL, $width=NULL) {
    $this->name = $name;
    $this->heading = (isset($heading) ? $heading : $name);
    if (($alias===NULL || $alias==='') && strpos($name, '.')!== NULL) {
      $alias = strtr($name, '.', '_');
    }
    $this->alias = (isset($alias) && $alias != '' ? $alias : $name);
    $this->format = $format;
    $this->width = $width;
  }
} //sqlFieldName

/**
* Export type list -- list of ExportType objects contained in a namespace
*
* @package    Bumblebee
* @subpackage Export
* @todo //TODO: create the ExportType objects from file or database not hard coded
*/
class ExportTypeList {
  var $types = array();
  var $_formula = array();

  function ExportTypeList() {
    $this->_standardFormulae();
    $this->_addType($this->_createLogbook());
    $this->_addType($this->_createUsers());
    $this->_addType($this->_createProjects());
    $this->_addType($this->_createGroups());
    $this->_addType($this->_createConsumable());
    $this->_addType($this->_createConsumableGroup());
    $this->_addType($this->_createBillingConsumable());
    $this->_addType($this->_createBillingGroups());
    $this->_addType($this->_createBilling());
    $this->_addType($this->_createBillingSummary());
  }

  function _addType($type) {
    $this->types[$type->name] = $type;
  }

  /** @todo //TODO: i18n: file */
  function _createLogbook() {
    $type = new ExportType('instrument', 'bookings',
                            T_('Instrument usage log book'),
                            T_('Instrument usage log book for %s - %s'),
                            'instruments');
    $type->join[] = array('table' => 'users', 'condition' =>  'users.id=bookings.userid');
    $type->join[] = array('table' => 'instruments', 'condition' =>  'instruments.id=bookings.instrument');
    $type->join[] = array('table' => 'projects', 'condition' =>  'bookings.projectid=projects.id');
    $type->fields = array(
                      new sqlFieldName('bookwhen', T_('Date/Time'), '', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('duration', T_('Length'),    '', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('username', T_('Username'),  '', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('users.name', T_('Name'), 'user_name', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('instruments.name', T_('Instrument'), 'instrument_name'),
                      new sqlFieldName('CONCAT(instruments.name, \': \', instruments.longname)',
                                      T_('Instrument'), 'instrument_title', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('projects.name', T_('Project name'), 'project_name', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('comments', T_('User comments'), '', EXPORT_HTML_LEFT, 10),
                      new sqlFieldName('log', T_('Log entry'), '', EXPORT_HTML_LEFT, 30)
                    );
    $type->where[] = 'bookings.deleted <> 1';
    $type->where[] = 'bookings.userid <> 0';
    $type->where[] = 'bookings.projectid <> 0';
    $type->group = array('instrument_name', 'bookwhen', 'user_name', 'project_name');
    $type->breakField = 'instrument_title';
    $type->omitFields['instrument_name'] = 1;
    $type->omitFields['instrument_title'] = 1;
    $type->omitFields['username'] = 1;
    return $type;
  }

  function _createProjects() {
    $type = new ExportType('project', 'bookings',
                              T_('Instrument usage by projects'),
                              T_('Instrument usage by projects for %s - %s'),
                              array('instruments', 'projects'));
    $type->join[] = array('table' => 'instruments', 'condition' =>  'instruments.id=bookings.instrument');
    $type->join[] = array('table' => 'projects', 'condition' =>  'bookings.projectid=projects.id');
    $type->fields = array(
                      new sqlFieldName('instruments.name', T_('Instrument'), 'instrument_name', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('CONCAT(instruments.name, \': \', instruments.longname)',
                                      T_('Instrument'), 'instrument_title', EXPORT_HTML_LEFT, 30),
                      new sqlFieldName('projects.name', T_('Project'), 'project_name', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('projects.longname', T_('Description'), '', EXPORT_HTML_LEFT, 10),
                      new sqlFieldName('ROUND(SUM(TIME_TO_SEC(duration))/60/60,2)', T_('Hours used'),
                                                           'hours_used', EXPORT_HTML_DECIMAL_2|EXPORT_HTML_RIGHT|EXPORT_CALC_TOTAL, '*')
                    );
    $type->where[] = 'bookings.deleted <> 1';
    $type->where[] = 'bookings.userid <> 0';
    $type->where[] = 'bookings.projectid <> 0';
    $type->pivot = array('instruments' =>
                              array('description'=> T_('Group results by instrument'),
                                    'group' => array('instrument_name', 'project_name'),
                                    'breakField' => 'instrument_title',
                                    'omitFields' => array('instrument_name', 'instrument_title')),
                         'projects' =>
                              array('description'=> T_('Group results by project'),
                                    'group' => array('project_name', 'instrument_name'),
                                    'breakField' => 'project_name',
                                    'omitFields' => array('instrument_name',
                                                          'project_name', 'projects_longname'))
                        );
    return $type;
  }

  function _createGroups() {
    $type = new ExportType('group', 'bookings',
                              T_('Instrument usage by groups'),
                              T_('Instrument usage by groups for %s - %s'),
                              array('instruments', 'groups'));
    $type->join[] = array('table' => 'instruments', 'condition' =>  'instruments.id=bookings.instrument');
    $type->join[] = array('table' => 'projects', 'condition' =>  'bookings.projectid=projects.id');
    $type->join[] = array('table' => 'projectgroups', 'condition' =>  'projectgroups.projectid=bookings.projectid');
    $type->join[] = array('table' => 'groups', 'condition' =>  'groups.id=projectgroups.groupid');
    $type->join[] = array('table' => 'users', 'condition' =>  'users.id=bookings.userid');
    $type->fields = array(
                      new sqlFieldName('instruments.name', T_('Instrument'), 'instrument_name', EXPORT_HTML_LEFT, 10),
                      new sqlFieldName('CONCAT(instruments.name, \': \', instruments.longname)',
                                      T_('Instrument'), 'instrument_title', EXPORT_HTML_LEFT, 20),
                      new sqlFieldName('CONCAT(groups.name, \' (\', groups.longname, \')\')',
                                      'Group', 'group_title', EXPORT_HTML_LEFT, 10),
                      new sqlFieldName('groups.name', T_('Supervisor'), 'group_name', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('groups.longname', T_('Group'), 'group_longname', EXPORT_HTML_LEFT, 20),
                      new sqlFieldName('projects.name', T_('Project'), 'project_name', EXPORT_HTML_LEFT, 20),
                      new sqlFieldName('ROUND('
                                          .'SUM(TIME_TO_SEC(duration))/60/60,'
                                        .'2) ',
                                      T_('Hours used'),
                                      'hours_used', EXPORT_HTML_DECIMAL_2|EXPORT_HTML_RIGHT|EXPORT_CALC_TOTAL, '*'),
                      new sqlFieldName('ROUND('
                                          .'SUM(TIME_TO_SEC(duration)*grouppc)/60/60/100,'
                                        .'2) ',
                                      T_('Share'),
                                      'weighted_hours_used', EXPORT_HTML_DECIMAL_2|EXPORT_HTML_RIGHT|EXPORT_CALC_TOTAL, '*')
                   );
    $type->where[] = 'bookings.deleted <> 1';
    $type->where[] = 'bookings.userid <> 0';
    $type->where[] = 'bookings.projectid <> 0';
    $type->pivot = array('instruments' =>
                              array('description'=> T_('Group results by instrument'),
                                    'group' => array('instrument_name', 'group_name',
                                                      'project_name'),
                                    'breakField' => 'instrument_title',
                                    'omitFields' => array('instrument_name', 'group_longname', 'instrument_title')),
                         'groups' =>
                              array('description'=> T_('Group results by research group'),
                                    'group' => array('group_name', 'project_name', 'instrument_name'),
                                    'breakField' => 'group_title',
                                    'omitFields' => array('instrument_name', 'group_title',
                                                          'group_name', 'group_longname')),
                         'users' =>
                              array('description'=> T_('Group results by research group with per-user breakdown'),
                                    'group' => array('group_name', 'user_name', 'project_name', 'instrument_name'),
                                    'breakField' => 'group_title',
                                    'omitFields' => array('instrument_name', 'group_title',
                                                          'group_name', 'group_longname'),
                                    'extraFields'=> array (new sqlFieldName('users.name', 'Name', 'user_name', EXPORT_HTML_LEFT, '*')),
                                    'fieldOrder' => array('user_name', 'project_name', 'instrument_title', 'hours_used', 'weighted_hours_used') )
                        );
    return $type;
  }

  function _createUsers() {
    $type = new ExportType('user', 'bookings',
                      T_('Instrument usage by users'),
                      T_('Instrument usage by users for %s - %s'),
                      'instruments');
    $type->join[] = array('table' => 'users', 'condition' =>  'users.id=bookings.userid');
    $type->join[] = array('table' => 'instruments', 'condition' =>  'instruments.id=bookings.instrument');
    $type->fields = array(
                      new sqlFieldName('username', T_('Username'), '', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('users.name', T_('Name'), 'user_name', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('instruments.name', T_('Instrument'), 'instrument_name', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('CONCAT(instruments.name, \': \', instruments.longname)',
                                      'Instrument', 'instrument_title', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('ROUND('
                                          .'SUM(TIME_TO_SEC(duration))/60/60,'
                                        .'2) ',
                                      T_('Hours used'),
                                      'hours_used', EXPORT_HTML_DECIMAL_2|EXPORT_HTML_RIGHT|EXPORT_CALC_TOTAL, '*')
                    );
    $type->where[] = 'bookings.deleted <> 1';
    $type->where[] = 'bookings.userid <> 0';
    $type->where[] = 'bookings.projectid <> 0';
    $type->group = array('instrument_name', 'user_name');
    $type->breakField = 'instrument_title';
    $type->omitFields['instrument_title'] = 1;
    $type->omitFields['instrument_name'] = 1;
    return $type;
  }

  function _createConsumable() {
    $type = new ExportType('consumable', 'consumables_use',
                        T_('Consumables usage by users'),
                        T_('Consumables usage by users for %s - %s'),
                        array('consumables', 'users'));
    $type->join[] = array('table' => 'users', 'condition' =>  'users.id=consumables_use.userid');
    $type->join[] = array('table' => 'consumables', 'condition' =>  'consumables.id=consumables_use.consumable');
    $type->join[] = array('table' => 'projects', 'condition' =>  'consumables_use.projectid=projects.id');
    $type->fields = array(
                      new sqlFieldName('consumables.name', T_('Item Code'), 'consumable_name', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('consumables.longname', T_('Item Name'), 'consumable_longname', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('CONCAT(consumables.name, \': \', consumables.longname)',
                                      T_('Item'), 'consumable_title', EXPORT_HTML_LEFT, 20),
                      new sqlFieldName('usewhen', T_('Date'), '', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('username', T_('Username'), '', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('users.name', T_('Name'), 'user_name', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('projects.name', T_('Project'), 'project_name', EXPORT_HTML_LEFT, 10),
                      new sqlFieldName('quantity', T_('Quantity'), 'quantity', EXPORT_HTML_RIGHT, '*')
                    );
    $type->pivot = array('consumables' =>
                              array('description'=> T_('Group results by consumable'),
                                    'group' => array('consumable_name', 'usewhen', 'user_name', 'project_name', 'quantity'),
                                    'breakField' => 'consumable_title',
                                    'omitFields' => array('username','consumable_name',
                                                          'consumable_longname', 'consumable_title',
                                                          'quantity'),
                                    'extraFields'=> array (new sqlFieldName('quantity', 'Quantity', 'quantity_total', EXPORT_HTML_RIGHT|EXPORT_CALC_TOTAL, '*'))),
                         'users' =>
                              array('description'=> T_('Group results by user'),
                                    'group' => array('user_name', 'usewhen', 'consumable_name', 'project_name', 'quantity'),
                                    'breakField' => 'user_name',
                                    'omitFields' => array('username','user_name',
                                                          'consumable_title'))
                        );
    $type->where[] = 'consumables_use.projectid <> 0';
    $type->timewhere = array('usewhen >= ', 'usewhen < ');
    return $type;
  }

  function _createConsumableGroup() {
    $type = new ExportType('consumablegroup', 'consumables_use',
                  T_('Consumables usage by groups'),
                  T_('Consumables usage by groups for %s - %s'),
                  array('consumables', 'users'));
    $type->join[] = array('table' => 'users', 'condition' =>  'users.id=consumables_use.userid');
    $type->join[] = array('table' => 'consumables', 'condition' =>  'consumables.id=consumables_use.consumable');
    $type->join[] = array('table' => 'projects', 'condition' =>  'consumables_use.projectid=projects.id');
    $type->join[] = array('table' => 'projectgroups', 'condition' =>  'projectgroups.projectid=consumables_use.projectid');
    $type->join[] = array('table' => 'groups', 'condition' =>  'groups.id=projectgroups.groupid');
    $type->fields = array(
                      new sqlFieldName('consumables.name', T_('Item Code'), 'consumable_name', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('consumables.longname', T_('Item Name'), 'consumable_longname', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('CONCAT(consumables.name, \': \', consumables.longname)',
                                      T_('Item'), 'consumable_title', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('groups.name', T_('Supervisor'), 'group_name', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('groups.longname', T_('Group'), 'group_longname', EXPORT_HTML_LEFT, 20),
                      new sqlFieldName('CONCAT(groups.name, \' (\', groups.longname, \')\')',
                                      T_('Group'), 'group_title', EXPORT_HTML_LEFT, 10),
                      new sqlFieldName('projects.name', T_('Project'), 'project_name', EXPORT_HTML_LEFT, 10),
                      new sqlFieldName('SUM(quantity)', T_('Quantity'), 'quantity', EXPORT_HTML_RIGHT, '*'),
                      new sqlFieldName('SUM(quantity*grouppc/100)', T_('Share'), 'share', EXPORT_HTML_RIGHT, '*')
                      //new sqlFieldName('grouppc', 'Share (%)', 'share', EXPORT_HTML_RIGHT, '*')
                    );
    $type->pivot = array('consumables' =>
                              array('description'=> T_('Group results by consumables'),
                                    'group' => array('consumable_name', 'group_name', 'project_name'),
                                    'breakField' => 'consumable_title',
                                    'omitFields' => array('consumable_name', 'group_title', 'consumable_longname', 'consumable_title', 'quantity'),
                                    'extraFields'=> array (new sqlFieldName('SUM(quantity*grouppc/100)', 'Quantity', 'quantity_total', EXPORT_HTML_RIGHT|EXPORT_CALC_TOTAL, '*'))),
                         'groups' =>
                              array('description'=> T_('Group results by research group'),
                                    'group' => array('group_name', 'project_name', 'consumable_name'),
                                    'breakField' => 'group_title',
                                    'omitFields' => array('consumable_title', 'group_title',
                                                          'group_name', 'group_longname')),
                         'users' =>
                              array('description'=> T_('Group results by research group with per-user breakdown'),
                                    'group' => array('group_name', 'user_name', 'project_name', 'consumable_name'),
                                    'breakField' => 'group_title',
                                    'omitFields' => array('consumable_name', 'group_title',
                                                          'group_name', 'group_longname'),
                                    'extraFields'=> array (new sqlFieldName('users.name', 'Name', 'user_name', EXPORT_HTML_LEFT, '*')),
                                    'fieldOrder' => array('user_name', 'project_name', 'consumable_title', 'quantity', 'share') )

                        );
    $type->timewhere = array('usewhen >= ', 'usewhen < ');
    $type->where[] = 'consumables_use.projectid <> 0';
    return $type;
  }

  function _createBillingConsumable() {
    $type = new ExportType('consumablebilling', 'consumables_use',
                            T_('Billing data: consumable usage'),
                            T_('Billing data: consumable usage for %s - %s'),
                            array('consumables', 'groups'));
    $type->join[] = array('table' => 'consumables', 'condition' =>  'consumables.id=consumables_use.consumable');
    $type->join[] = array('table' => 'projectgroups', 'condition' =>  'projectgroups.projectid=consumables_use.projectid');
    $type->join[] = array('table' => 'groups', 'condition' =>  'groups.id=projectgroups.groupid');
    $type->fields = array(
                      new sqlFieldName('groups.name', T_('Supervisor'), 'group_name', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('groups.longname', T_('Group'), '', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('CONCAT(groups.name, \' (\', groups.longname, \')\')',
                                      T_('Group'), 'group_title', EXPORT_HTML_LEFT, 10),
                      new sqlFieldName('CONCAT(consumables.name, \': \', consumables.longname)',
                                      T_('Item'), 'consumable_title', EXPORT_HTML_LEFT, 10),
                      new sqlFieldName('consumables.name', T_('Item'), 'consumable_name', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('consumables.longname', T_('Description'), 'consumable_longname', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('consumables.cost', T_('Unit cost'), 'unitcost', EXPORT_HTML_RIGHT|EXPORT_HTML_MONEY, '*'),
                      //new sqlFieldName('grouppc', 'Share (%)', '', EXPORT_HTML_RIGHT, '*'),
                      new sqlFieldName('SUM(consumables_use.quantity)', T_('Quantity'), 'quantity', EXPORT_HTML_RIGHT, '*'),
                      new sqlFieldName('ROUND(SUM(grouppc/100*consumables_use.quantity),2)', T_('Share'), 'totquantity', EXPORT_HTML_RIGHT, '*'),
                      new sqlFieldName('ROUND(SUM(consumables.cost*grouppc/100*consumables_use.quantity),2)',
                                          T_('Cost'), 'cost_to_group', EXPORT_CALC_TOTAL|EXPORT_HTML_RIGHT|EXPORT_HTML_MONEY, '*')
                    );
    $type->pivot = array('groups' =>
                              array('description'=> T_('Group results by research group'),
                                    'group' => array('group_name', 'consumable_name'),
                                    'breakField' => 'group_title',
                                    'omitFields' => array('group_name','groups_longname',
                                                          'group_title',
                                                          'consumable_title')),
                        'consumables' =>
                              array('description'=> T_('Group results by consumable'),
                                    'group' => array('consumable_name', 'group_name'),
                                    'breakField' => 'consumable_title',
                                    'omitFields' => array('groupname', 'group_title',
                                                          'consumable_name',
                                                          'consumable_longname', 'consumable_title'))
                        );
    $type->timewhere = array('usewhen >= ', 'usewhen < ');
    $type->where[] = 'consumables_use.projectid <> 0';
    return $type;
  }

  function _createBillingGroups() {
    $type = new ExportType('bookingbilling', 'bookings',
                        T_('Billing data: instrument usage'),
                        T_('Billing data: instrument usage for %s - %s'),
                        array('instruments', 'groups'));
    $type->join[] = array('table' => 'instruments', 'condition' =>  'instruments.id=bookings.instrument');
    $type->join[] = array('table' => 'projects', 'condition' =>  'bookings.projectid=projects.id');
    $type->join[] = array('table' => 'projectgroups', 'condition' =>  'projectgroups.projectid=bookings.projectid');
    $type->join[] = array('table' => 'groups', 'condition' =>  'groups.id=projectgroups.groupid');
    $type->join[] = array('table' => 'costs', 'condition' =>  'costs.userclass=projects.defaultclass AND costs.instrumentclass=instruments.class');
    $type->join[] = array('table' => 'projectrates', 'condition' =>  'projectrates.projectid=bookings.projectid AND projectrates.instrid=bookings.instrument');
    $type->join[] = array('table' => 'costs', 'alias' => 'speccosts', 'condition' =>  'projectrates.rate=speccosts.id');

    $type->fields = array(
                      new sqlFieldName('CONCAT(instruments.name, \': \', instruments.longname)',
                                      T_('Instrument'), 'instrument_title', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('instruments.name', T_('Instrument'), 'instrument_name', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('CONCAT(groups.name, \' (\', groups.longname, \')\')',
                                      T_('Group'), 'group_title', EXPORT_HTML_LEFT, 10),
                      new sqlFieldName('groups.name', 'Supervisor', 'group_name', EXPORT_HTML_LEFT, '*'),
                      //new sqlFieldName('SUM(ROUND(TIME_TO_SEC(duration)/60/60,2))', 'Total hours', 'total_hours', EXPORT_HTML_DECIMAL_2|EXPORT_HTML_RIGHT|EXPORT_CALC_TOTAL, '*'),
                      new sqlFieldName($this->_formula['weightDays'], T_('Days used'), 'weighted_days_used', EXPORT_HTML_DECIMAL_2|EXPORT_HTML_RIGHT|EXPORT_CALC_TOTAL, '*'),
                      new sqlFieldName('grouppc', T_('Share (%)'), 'share', EXPORT_HTML_RIGHT, '*'),
                      //new sqlFieldName('costs.costfullday', 'Daily rate', 'genrate'),
                      //new sqlFieldName('speccosts.costfullday', 'Daily rate', 'specrate'),
                      new sqlFieldName($this->_formula['rate'], T_('Rate'), 'rate',  EXPORT_HTML_MONEY|EXPORT_HTML_RIGHT, '*'),
                      //new sqlFieldName('('.$this->_formula['fullAmount'].')*grouppc/100', 'Cost', 'fullcost',  EXPORT_HTML_MONEY|EXPORT_HTML_RIGHT),
                      //new sqlFieldName($this->_formula['dailymarkdown'], 'Daily Discount (%)', 'dailydiscount',  EXPORT_HTML_DECIMAL_2|EXPORT_HTML_RIGHT),
                      new sqlFieldName($this->_formula['discount'], T_('Bulk Discount (%)'), 'discount',  EXPORT_HTML_DECIMAL_2|EXPORT_HTML_RIGHT, '*'),
                      new sqlFieldName('FLOOR(('.$this->_formula['finalCost'].')*grouppc/100)', T_('Cost'), 'cost',  EXPORT_HTML_RIGHT|EXPORT_HTML_MONEY|EXPORT_CALC_TOTAL, '*')
                   );
    $type->where[] = 'bookings.deleted <> 1';
    $type->where[] = 'bookings.userid <> 0';
    $type->where[] = 'bookings.projectid <> 0';
    //$type->group = '';
    //$type->group = array('instrument_name', 'group_name');
    $type->pivot = array('groups' =>
                              array('description'=> T_('Group results by research group'),
                                    'group' => array('group_name', 'instrument_name'),
                                    'breakField' => 'group_title',
                                    'omitFields' => array('instrument_name', 'group_title',
                                                          'group_name', 'group_longname')),
                        'instruments' =>
                              array('description'=> T_('Group results by instrument'),
                                    'group' => array('instrument_name', 'group_name'),
                                    'breakField' => 'instrument_title',
                                    'omitFields' => array('instrument_name', 'instrument_title', 'group_title'))
                         );
    return $type;
  }


  function _createBilling() {
    $itype = new ExportType('billing-instruments', 'bookings',
                            T_('Billing data: complete: instruments'),
                            T_('Billing data: complete: instruments for %s - %s'),
                            array('instruments','groups'));
    $itype->join[] = array('table' => 'instruments', 'condition' =>  'instruments.id=bookings.instrument');
    $itype->join[] = array('table' => 'projects', 'condition' =>  'bookings.projectid=projects.id');
    $itype->join[] = array('table' => 'projectgroups', 'condition' =>  'projectgroups.projectid=bookings.projectid');
    $itype->join[] = array('table' => 'groups', 'condition' =>  'groups.id=projectgroups.groupid');
    $itype->join[] = array('table' => 'costs', 'condition' =>  'costs.userclass=projects.defaultclass AND costs.instrumentclass=instruments.class');
    $itype->join[] = array('table' => 'projectrates', 'condition' =>  'projectrates.projectid=bookings.projectid AND projectrates.instrid=bookings.instrument');
    $itype->join[] = array('table' => 'costs', 'alias' => 'speccosts', 'condition' =>  'projectrates.rate=speccosts.id');

    $itype->fields = array(
                      new sqlFieldName('CONCAT(instruments.name, \': \', instruments.longname)',
                                      T_('Instrument'), 'title', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('CONCAT(groups.name, \' (\', groups.longname, \')\')',
                                      T_('Group'), 'group_title', EXPORT_HTML_LEFT, 10),
                      new sqlFieldName($this->_formula['finalCost'].'/'.$this->_formula['rate'].'*grouppc/100', T_('Quantity'), 'quantity', EXPORT_HTML_DECIMAL_2|EXPORT_HTML_RIGHT|EXPORT_CALC_TOTAL, '*'),
                      new sqlFieldName($this->_formula['rate'], T_('Unit cost'), 'unitcost',  EXPORT_HTML_MONEY|EXPORT_HTML_RIGHT, '*'),
                      new sqlFieldName('FLOOR(('.$this->_formula['finalCost'].')*grouppc/100)', T_('Cost'), 'cost',  EXPORT_HTML_RIGHT|EXPORT_HTML_MONEY|EXPORT_CALC_TOTAL, '*')
                   );
    $itype->where[] = 'bookings.deleted <> 1';
    $itype->where[] = 'bookings.userid <> 0';
    $itype->where[] = 'bookings.projectid <> 0';
    $itype->group = array('groups.name', 'instruments.name');


    $ctype = new ExportType('billing-consumable', 'consumables_use',
                              T_('Billing data: complete: consumable usage'),
                              T_('Billing data: complete: consumable usage for %s - %s'),
                              array('consumables','groups'));
    $ctype->join[] = array('table' => 'consumables', 'condition' =>  'consumables.id=consumables_use.consumable');
    $ctype->join[] = array('table' => 'projectgroups', 'condition' =>  'projectgroups.projectid=consumables_use.projectid');
    $ctype->join[] = array('table' => 'groups', 'condition' =>  'groups.id=projectgroups.groupid');
    $ctype->fields = array(
                      new sqlFieldName('CONCAT(consumables.name, \': \', consumables.longname)',
                                      T_('Item'), 'title', EXPORT_HTML_LEFT, 10),
                      new sqlFieldName('CONCAT(groups.name, \' (\', groups.longname, \')\')',
                                      T_('Group'), 'group_title', EXPORT_HTML_LEFT, 10),
                      new sqlFieldName('ROUND(SUM(grouppc/100*consumables_use.quantity),2)', T_('Share'), 'quantity', EXPORT_HTML_RIGHT, '*'),
                      new sqlFieldName('consumables.cost', T_('Unit cost'), 'unitcost', EXPORT_HTML_RIGHT|EXPORT_HTML_MONEY, '*'),
                      //new sqlFieldName('grouppc', 'Share (%)', '', EXPORT_HTML_RIGHT, '*'),
                      new sqlFieldName('ROUND(SUM(consumables.cost*grouppc/100*consumables_use.quantity),2)',
                                          T_('Cost to group'), 'cost', EXPORT_CALC_TOTAL|EXPORT_HTML_RIGHT|EXPORT_HTML_MONEY, '*')
                    );
    $ctype->group = array('groups.name', 'consumables.name');
    $ctype->timewhere = array('usewhen >= ', 'usewhen < ');
    $ctype->where[] = 'consumables_use.projectid <> 0';



    $type = new ExportType('billing', '',
                            T_('Billing data: complete'),
                            T_('Billing data: complete for %s - %s'),
                            array('instruments', 'consumables', 'groups'));
    $type->fields = array(
                      new sqlFieldName('', T_('Item'),   'title', EXPORT_HTML_LEFT, 10),
                      new sqlFieldName('', T_('Group'),  'group_title', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('', T_('Quantity'),  'quantity', EXPORT_HTML_RIGHT|EXPORT_HTML_DECIMAL_2, '*'),
                      new sqlFieldName('', T_('Unit cost'),  'unitcost', EXPORT_HTML_RIGHT|EXPORT_HTML_MONEY, '*'),
                      new sqlFieldName('', T_('Amount'), 'cost', EXPORT_CALC_TOTAL|EXPORT_HTML_RIGHT|EXPORT_HTML_MONEY, '*')
                    );
    $type->order = array('group_title');
    $type->breakField = 'group_title';
    $type->omitFields['group_title'] = 1;

    $type->union = array($itype, $ctype);

    return $type;
  }

  function _createBillingSummary() {
    $itype = new ExportType('billing-instruments', 'bookings',
                    T_('Billing data: summary: instruments'),
                    T_('Billing data: summary: instruments for %s - %s'),
                    array('instruments','groups'));
    $itype->join[] = array('table' => 'instruments', 'condition' =>  'instruments.id=bookings.instrument');
    $itype->join[] = array('table' => 'projects', 'condition' =>  'bookings.projectid=projects.id');
    $itype->join[] = array('table' => 'projectgroups', 'condition' =>  'projectgroups.projectid=bookings.projectid');
    $itype->join[] = array('table' => 'groups', 'condition' =>  'groups.id=projectgroups.groupid');
    $itype->join[] = array('table' => 'costs', 'condition' =>  'costs.userclass=projects.defaultclass AND costs.instrumentclass=instruments.class');
    $itype->join[] = array('table' => 'projectrates', 'condition' =>  'projectrates.projectid=bookings.projectid AND projectrates.instrid=bookings.instrument');
    $itype->join[] = array('table' => 'costs', 'alias' => 'speccosts', 'condition' =>  'projectrates.rate=speccosts.id');

    $itype->fields = array(
                      new sqlFieldName('groups.name', T_('Supervisor'),  'group_name', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('groups.longname', T_('Group'),  'group_longname', EXPORT_HTML_LEFT, 10),
                      new sqlFieldName('groups.addr1', T_('Address 1'),  'addr1', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('groups.addr2', T_('Address 2'),  'addr2', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('groups.suburb', T_('Suburb'),  'suburb', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('groups.state', T_('State'),  'state', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('groups.code', T_('Postal code'),  'code', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('groups.country', T_('Country'),  'country', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('groups.email', T_('Email'),  'email', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('groups.fax', T_('Fax'),  'fax', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('groups.account', T_('Account'),  'account', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('FLOOR(('.$this->_formula['finalCost'].')*grouppc/100)', T_('Cost'), 'cost',  EXPORT_HTML_RIGHT|EXPORT_HTML_MONEY|EXPORT_CALC_TOTAL, '*')
                   );
    $itype->where[] = 'bookings.deleted <> 1';
    $itype->where[] = 'bookings.userid <> 0';
    $itype->where[] = 'bookings.projectid <> 0';
    $itype->group = array('groups.name', 'instruments.name');


    $ctype = new ExportType('billing-consumable', 'consumables_use',
                          T_('Billing data: summary: consumable usage'),
                          T_('Billing data: summary: consumable usage for %s - %s'),
                          array('consumables','groups'));
    $ctype->join[] = array('table' => 'consumables', 'condition' =>  'consumables.id=consumables_use.consumable');
    $ctype->join[] = array('table' => 'projectgroups', 'condition' =>  'projectgroups.projectid=consumables_use.projectid');
    $ctype->join[] = array('table' => 'groups', 'condition' =>  'groups.id=projectgroups.groupid');
    $ctype->fields = array(
                      new sqlFieldName('groups.name', T_('Supervisor'),  'group_name', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('groups.longname', T_('Group'),  'group_longname', EXPORT_HTML_LEFT, 10),
                      new sqlFieldName('groups.addr1', T_('Address 1'),  'addr1', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('groups.addr2', T_('Address 2'),  'addr2', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('groups.suburb', T_('Suburb'),  'suburb', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('groups.state', T_('State'),  'state', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('groups.code', T_('Postal code'),  'code', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('groups.country', T_('Country'),  'country', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('groups.email', T_('Email'),  'email', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('groups.fax', T_('Fax'),  'fax', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('groups.account', T_('Account'),  'account', EXPORT_HTML_LEFT, '*'),
                      new sqlFieldName('ROUND(SUM(consumables.cost*grouppc/100*consumables_use.quantity),2)',
                                          T_('Cost to group'), 'cost', EXPORT_CALC_TOTAL|EXPORT_HTML_RIGHT|EXPORT_HTML_MONEY, '*')
                    );
    $ctype->group = array('groups.name', 'consumables.name');
    $ctype->timewhere = array('usewhen >= ', 'usewhen < ');
    $ctype->where[] = 'consumables_use.projectid <> 0';



    $type = new ExportType('billingsummary', '',
                          T_('Billing data: summary'),
                          T_('Billing data: summary for %s - %s'),
                          array('instruments', 'consumables', 'groups'));
    $type->fields = array(
                      new sqlFieldName('', T_('Supervisor'),  'group_name', EXPORT_HTML_LEFT, 10),
                      new sqlFieldName('', T_('Group'),  'group_longname', EXPORT_HTML_LEFT, 15),
                      new sqlFieldName('', T_('Address'),  'addr1', EXPORT_HTML_LEFT, 15),
                      new sqlFieldName('', '',  'addr2', EXPORT_HTML_LEFT, 10),
                      new sqlFieldName('', '',  'suburb', EXPORT_HTML_LEFT, 5),
                      new sqlFieldName('', '',  'state', EXPORT_HTML_LEFT, 2),
                      new sqlFieldName('', '',  'code', EXPORT_HTML_LEFT, 2),
                      new sqlFieldName('', '',  'country', EXPORT_HTML_LEFT, 5),
                      new sqlFieldName('', T_('Email'),  'email', EXPORT_HTML_LEFT, 10),
                      new sqlFieldName('', T_('Fax'),  'fax', EXPORT_HTML_LEFT, 8),
                      new sqlFieldName('', T_('Account'),  'account', EXPORT_HTML_LEFT, 15),
                      new sqlFieldName('', T_('Amount'), 'cost', EXPORT_HTML_RIGHT|EXPORT_HTML_MONEY, '*')
                    );
    $type->order = array('group_name');

    $type->union = array($itype, $ctype);
    // under MySQL 4.0 we can't GROUP BY with a SUM() over a UNION as a subquery.
    $type->manualGroup = 'group_name';
    $type->manualSum = array('cost');

    return $type;
  }
















  function _standardFormulae() {
  $this->_formula['weightDays'] =
                    'SUM('
                      .'(CASE '
                        .'WHEN TIME_TO_SEC(duration)/60/60 >= instruments.fulldaylength '
                            .'THEN 1 '
                        .'WHEN TIME_TO_SEC(duration)/60/60 '
                              .'BETWEEN instruments.halfdaylength '
                              .'AND instruments.fulldaylength '
                            .'THEN LEAST('
                                    .'1, '
                                    .'(TIME_TO_SEC(duration)/60/60-instruments.halfdaylength)'
                                      .'*costs.hourfactor + costs.halfdayfactor'
                                  .') '
                        .'ELSE '
                            .'LEAST('
                                    .'costs.halfdayfactor, '
                                    .'TIME_TO_SEC(duration)/60/60*costs.hourfactor'
                                  .') '
                      .'END)*(100-bookings.discount)/100'
                    .') ';

    $this->_formula['rate']          = 'COALESCE(speccosts.costfullday,costs.costfullday)';
    $this->_formula['dailymarkdown'] = 'COALESCE(speccosts.dailymarkdown,costs.dailymarkdown)';

    $this->_formula['markdiscount'] =
                'ROUND(GREATEST('
                    .'100*( 1 - '
                        .'('
                            .'(1-POW(1-'.$this->_formula['dailymarkdown'].'/100,('.$this->_formula['weightDays'].')))'
                            .'/('.$this->_formula['dailymarkdown'].'*('.$this->_formula['weightDays'].')/100)'
                        .')'
                    .'),0)'   // CEIL         //prevent negative discounts
                .',4)';      // ROUND(a,n)   //clean up numbers for export

    $this->_formula['discount'] =
                '(CASE WHEN '.$this->_formula['dailymarkdown'].' <= 0 '
                  .'THEN '
                    .'0 '
                  .'ELSE '
                    .$this->_formula['markdiscount'] .' '
                .'END)';

    $this->_formula['fullAmount'] = $this->_formula['weightDays'].'*'.$this->_formula['rate'];
    $this->_formula['finalCost']  = $this->_formula['fullAmount']
                                          .'*(1-('.$this->_formula['discount'].')/100)';

/*    foreach (array_keys($this->_formula) as $k) {
      $this->_formula[$k] = preg_replace('/[\n\r]/', ' ', $this->_formula[$k]);
    }*/
  }


} //ExportTypeList
