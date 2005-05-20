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
    $this->_addType($this->_createInstrument());
    $this->_addType($this->_createProjects());
    $this->_addType($this->_createGroups());
    $this->_addType($this->_createLogbook());
    $this->_addType($this->_createUsers());
    $this->_addType($this->_createConsumable());
    $this->_addType($this->_createBilling());
  }
  
  function _addType($type) {
    $this->types[$type->name] = $type;
  }
  
  function _createInstrument() {
    $type = new ExportType('instrument', 'bookings', 'Extended instrument usage log book', 'instruments');
    $type->join[] = array('table' => 'users', 'condition' =>  'users.id=bookings.userid');
    $type->join[] = array('table' => 'instruments', 'condition' =>  'instruments.id=bookings.instrument');
    $type->join[] = array('table' => 'projects', 'condition' =>  'bookings.projectid=projects.id');
    $type->join[] = array('table' => 'projectgroups', 'condition' =>  'projectgroups.projectid=bookings.projectid');
    $type->join[] = array('table' => 'groups', 'condition' =>  'groups.id=projectgroups.groupid');
    $type->fields = array('username', 'users.name AS uname', 'instruments.name AS iname', 
                    'projects.name as pname', 
                    'groups.name AS gname', 'groups.longname AS glongname', 
                    'bookwhen', 'duration');
    $type->where[] = 'deleted <> 1';
    $type->where[] = 'bookings.userid <> 0';
    return $type;
  }
  
  function _createProjects() {
    $type = new ExportType('project', 'bookings', 'Projects using instruments', 'instruments');
    $type->join[] = array('table' => 'users', 'condition' =>  'users.id=bookings.userid');
    $type->join[] = array('table' => 'instruments', 'condition' =>  'instruments.id=bookings.instrument');
    $type->join[] = array('table' => 'projects', 'condition' =>  'bookings.projectid=projects.id');
    $type->join[] = array('table' => 'projectgroups', 'condition' =>  'projectgroups.projectid=bookings.projectid');
    $type->join[] = array('table' => 'groups', 'condition' =>  'groups.id=projectgroups.groupid');
    $type->fields = array('username', 'users.name AS uname', 'instruments.name AS iname', 
                    'projects.name as pname', 
                    'groups.name AS gname', 'groups.longname AS glongname', 
                    'bookwhen', 'duration');
    $type->where[] = 'deleted <> 1';
    $type->where[] = 'bookings.userid <> 0';
    return $type;
  }

  function _createGroups() {
    $type = new ExportType('group', 'bookings', 'Groups using instruments', 'instruments');
    $type->join[] = array('table' => 'users', 'condition' =>  'users.id=bookings.userid');
    $type->join[] = array('table' => 'instruments', 'condition' =>  'instruments.id=bookings.instrument');
    $type->join[] = array('table' => 'projects', 'condition' =>  'bookings.projectid=projects.id');
    $type->join[] = array('table' => 'projectgroups', 'condition' =>  'projectgroups.projectid=bookings.projectid');
    $type->join[] = array('table' => 'groups', 'condition' =>  'groups.id=projectgroups.groupid');
    $type->fields = array('username', 'users.name AS uname', 'instruments.name AS iname', 
                    'projects.name as pname', 
                    'groups.name AS gname', 'groups.longname AS glongname', 
                    'bookwhen', 'duration');
    $type->where[] = 'deleted <> 1';
    $type->where[] = 'bookings.userid <> 0';
    return $type;
  }

  function _createLogbook() {
    $type = new ExportType('logbook', 'bookings', 'Instrument log book', 'instruments');
    $type->join[] = array('table' => 'users', 'condition' =>  'users.id=bookings.userid');
    $type->join[] = array('table' => 'instruments', 'condition' =>  'instruments.id=bookings.instrument');
    $type->join[] = array('table' => 'projects', 'condition' =>  'bookings.projectid=projects.id');
    $type->join[] = array('table' => 'projectgroups', 'condition' =>  'projectgroups.projectid=bookings.projectid');
    $type->join[] = array('table' => 'groups', 'condition' =>  'groups.id=projectgroups.groupid');
    $type->fields = array('username', 'users.name AS uname', 'instruments.name AS iname', 
                    'projects.name as pname', 
                    'groups.name AS gname', 'groups.longname AS glongname', 
                    'bookwhen', 'duration');
    $type->where[] = 'deleted <> 1';
    $type->where[] = 'bookings.userid <> 0';
    return $type;
  }

  function _createUsers() {
    $type = new ExportType('user', 'bookings', 'Users using instruments', 'instruments');
    $type->join[] = array('table' => 'users', 'condition' =>  'users.id=bookings.userid');
    $type->join[] = array('table' => 'instruments', 'condition' =>  'instruments.id=bookings.instrument');
    $type->join[] = array('table' => 'projects', 'condition' =>  'bookings.projectid=projects.id');
    $type->join[] = array('table' => 'projectgroups', 'condition' =>  'projectgroups.projectid=bookings.projectid');
    $type->join[] = array('table' => 'groups', 'condition' =>  'groups.id=projectgroups.groupid');
    $type->fields = array('username', 'users.name AS uname', 'instruments.name AS iname', 
                    'projects.name as pname', 
                    'groups.name AS gname', 'groups.longname AS glongname', 
                    'bookwhen', 'duration');
    $type->where[] = 'deleted <> 1';
    $type->where[] = 'bookings.userid <> 0';
    return $type;
  }

  function _createConsumable() {
    $type = new ExportType('consumable', 'consumables_use', 'Consumable usage', 'consumables');
    $type->join[] = array('table' => 'users', 'condition' =>  'users.id=consumables_use.userid');
    $type->join[] = array('table' => 'consumables', 'condition' =>  'consumables.id=consumables_use.consumable');
    $type->join[] = array('table' => 'projects', 'condition' =>  'consumables_use.projectid=projects.id');
    $type->join[] = array('table' => 'projectgroups', 'condition' =>  'projectgroups.projectid=consumables_use.projectid');
    $type->join[] = array('table' => 'groups', 'condition' =>  'groups.id=projectgroups.groupid');
    $type->fields = array('username', 'users.name AS uname', 'consumables.name AS iname', 
                    'projects.name as pname', 
                    'groups.name AS gname', 'groups.longname AS glongname', 
                    'usewhen');
    $type->timewhere = array('usewhen >= ', 'usewhen < ');
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
                    'groups.name AS gname', 'groups.longname AS glongname', 
                    'bookwhen', 'duration');
    $type->where[] = 'deleted <> 1';
    $type->where[] = 'bookings.userid <> 0';
    return $type;
  }
        
    
} //ExportTypeList