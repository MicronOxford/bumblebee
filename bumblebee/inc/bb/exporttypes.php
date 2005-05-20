<?php
# $Id$
# Data export object

class ExportType {
  var $name;
  var $basetable;
  var $description;
  var $limitation;
  var $fields = array();
  var $where  = array();
  var $join   = array();
  var $timewhere = array('bookwhen >= ', 'bookwhen < ');
  var $order;
  var $group;
  var $distinct = 0;
  
  function ExportType($name, $basetable, $description, $limitation) {
    $this->name        = $name;
    $this->basetable   = $basetable;
    $this->description = $description;
    $this->limitation  = $limitation;
  }
     
} //class ExportType



class ExportTypeList {
  var $types = array();
  
  function ExportTypeList() {
    $this->_addType($this->_createLogbook());
    //$this->_addType($this->_createInstrument());
    $this->_addType($this->_createUsers());
    $this->_addType($this->_createProjects());
    $this->_addType($this->_createGroups());
    $this->_addType($this->_createConsumable());
    $this->_addType($this->_createBillingConsumable());
    $this->_addType($this->_createBillingGroups());
    $this->_addType($this->_createBilling());
  }
  
  function _addType($type) {
    $this->types[$type->name] = $type;
  }
  
  function _createLogbook() {
    $type = new ExportType('instrument', 'bookings', 'Instrument usage log book', 'instruments');
    $type->join[] = array('table' => 'users', 'condition' =>  'users.id=bookings.userid');
    $type->join[] = array('table' => 'instruments', 'condition' =>  'instruments.id=bookings.instrument');
    $type->join[] = array('table' => 'projects', 'condition' =>  'bookings.projectid=projects.id');
    $type->fields = array('bookwhen', 'duration', 
                    'username', 'users.name AS user_name', 
                    'instruments.name AS instrument_name', 
                    'projects.name as project_name', 
                    'comments', 'log');
    $type->where[] = 'deleted <> 1';
    $type->where[] = 'bookings.userid <> 0';
    $type->group = array('instrument_name', 'bookwhen', 'user_name', 'project_name');
    return $type;
  }
  
  function _createProjects() {
    $type = new ExportType('project', 'bookings', 'Projects using instruments', 'instruments');
    $type->join[] = array('table' => 'instruments', 'condition' =>  'instruments.id=bookings.instrument');
    $type->join[] = array('table' => 'projects', 'condition' =>  'bookings.projectid=projects.id');
    $type->fields = array('instruments.name AS instrument_name', 
                    'projects.name AS project_name', 'projects.longname AS project_longname',
                    'SUM(TIME_TO_SEC(duration))/60/60 AS hours_used');
    $type->where[] = 'deleted <> 1';
    $type->where[] = 'bookings.userid <> 0';
    $type->group = array('instrument_name', 'project_name');
    return $type;
  }

  function _createGroups() {
    $type = new ExportType('group', 'bookings', 'Groups using instruments', 'instruments');
    $type->join[] = array('table' => 'instruments', 'condition' =>  'instruments.id=bookings.instrument');
    $type->join[] = array('table' => 'projects', 'condition' =>  'bookings.projectid=projects.id');
    $type->join[] = array('table' => 'projectgroups', 'condition' =>  'projectgroups.projectid=bookings.projectid');
    $type->join[] = array('table' => 'groups', 'condition' =>  'groups.id=projectgroups.groupid');
    $type->fields = array('instruments.name AS instrument_name', 
                    'groups.name AS group_name', 'groups.longname AS group_longname', 
                    'SUM(TIME_TO_SEC(duration)*grouppc)/60/60/100 AS weighted_hours_used');
    $type->where[] = 'deleted <> 1';
    $type->where[] = 'bookings.userid <> 0';
    $type->group = array('instrument_name', 'group_name');
    return $type;
  }

  function _createUsers() {
    $type = new ExportType('user', 'bookings', 'Users using instruments', 'instruments');
    $type->join[] = array('table' => 'users', 'condition' =>  'users.id=bookings.userid');
    $type->join[] = array('table' => 'instruments', 'condition' =>  'instruments.id=bookings.instrument');
    $type->fields = array('username', 'users.name AS user_name',
                    'instruments.name AS instrument_name', 
                    'SUM(TIME_TO_SEC(duration))/60/60 AS hours_used');
    $type->where[] = 'deleted <> 1';
    $type->where[] = 'bookings.userid <> 0';
    $type->group = array('instrument_name', 'user_name');
    return $type;
  }

  function _createConsumable() {
    $type = new ExportType('consumable', 'consumables_use', 'Consumable usage', 'consumables');
    $type->join[] = array('table' => 'users', 'condition' =>  'users.id=consumables_use.userid');
    $type->join[] = array('table' => 'consumables', 'condition' =>  'consumables.id=consumables_use.consumable');
    $type->join[] = array('table' => 'projects', 'condition' =>  'consumables_use.projectid=projects.id');
    $type->fields = array(
                      'consumables.name AS consumable_name', 'quantity',
                      'usewhen',
                      'username', 'users.name AS user_name',
                      'projects.name as project_name', 
                    );
    $type->timewhere = array('usewhen >= ', 'usewhen < ');
    $type->group = array('consumable_name', 'usewhen', 'user_name', 'project_name', 'quantity');
    return $type;
  }

  function _createBilling() {
    $type = new ExportType('billing', 'bookings', 'Billing data', 'instruments');
    $type->join[] = array('table' => 'users', 'condition' =>  'users.id=bookings.userid');
    $type->join[] = array('table' => 'instruments', 'condition' =>  'instruments.id=bookings.instrument');
    $type->join[] = array('table' => 'projects', 'condition' =>  'bookings.projectid=projects.id');
    $type->join[] = array('table' => 'projectgroups', 'condition' =>  'projectgroups.projectid=bookings.projectid');
    $type->join[] = array('table' => 'groups', 'condition' =>  'groups.id=projectgroups.groupid');
    $type->fields = array('username', 'users.name AS uname', 'instruments.name AS iname', 
                    'projects.name as pname', 
                    'groups.name AS group_name', 'groups.longname AS glongname', 
                    'bookwhen', 'duration');
    $type->where[] = 'deleted <> 1';
    $type->where[] = 'bookings.userid <> 0';
    return $type;
  }
        
  function _createBillingConsumable() {
    $type = $this->_createConsumable();
    $type->name = 'consumablebilling';
    $type->description = 'Billing data for consumable usage';
    $type->join[] = array('table' => 'projectgroups', 'condition' =>  'projectgroups.projectid=consumables_use.projectid');
    $type->join[] = array('table' => 'groups', 'condition' =>  'groups.id=projectgroups.groupid');
    $type->fields = array(
                        'groups.name AS group_name', 'groups.longname AS group_longname', 
                        'consumables.name AS consumable_name', 
                        'consumables.cost AS unit_cost',
                        'SUM(consumables_use.quantity) AS quantity',
                        '(consumables.cost * grouppc/100)*quantity AS cost_to_group'
                      );
    $type->group = array('group_name', 'consumable_name');
    return $type;
  }

  function _createBillingGroups() {
    $type = $this->_createGroups();
    $type->name = 'bookingbilling';
    $type->description = 'Billing data for instrument usage';
    $type->join[] = array('table' => 'costs', 'condition' =>  'costs.userclass=projects.defaultclass AND costs.instrumentclass=instruments.class');
    $type->fields = array('instruments.name AS instrument_name', 
                    'groups.name AS group_name', 'groups.longname AS group_longname', 
                    //'(TIME_TO_SEC(duration)/60/60) AS hours',
                    'SUM('
                      .'IF('
                        .'TIME_TO_SEC(duration)/60/60 > instruments.fulldaylength, '
                        .'1, '
                        .'IF('
                          .'TIME_TO_SEC(duration)/60/60 > instruments.halfdaylength, '
                          .'costs.halfdayfactor, '
                          .'TIME_TO_SEC(duration)/60/60/24*costs.hourfactor'
                        .')'
                      .')'
                   .')*grouppc/100 AS weighted_days_used'
                   );
    $type->where[] = 'deleted <> 1';
    $type->where[] = 'bookings.userid <> 0';
    $type->group = array('instrument_name', 'group_name');
    return $type;
  }


  
  
      
} //ExportTypeList