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
  
  // rendering options
  var $omitFields = array();
  var $breakField;
  var $header = -1;        // -1 triggers "auto" header generation
  
  function ExportType($name, $basetable, $description, $limitation) {
    $this->name        = $name;
    $this->basetable   = $basetable;
    $this->description = $description;
    $this->limitation  = $limitation;
  }
     
} //class ExportType

class sqlFieldName {
  var $name;
  var $alias;
  var $heading;
  var $format;
  
  function sqlFieldName($name, $heading=NULL, $alias=NULL, $format=NULL) {
    $this->name = $name;
    $this->heading = (isset($heading) ? $heading : $name);
    if ($alias===NULL && strpos($name, '.')!== NULL) {
      $alias = strtr($name, '.', '_');
    }
    $this->alias = (isset($alias) ? $alias : $name);
    $this->format = $format;
  }
} //sqlFieldName

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
    $type->fields = array(
                      new sqlFieldName('bookwhen', 'Date/Time'), 
                      new sqlFieldName('duration', 'Length'),
                      new sqlFieldName('username', 'Username'), 
                      new sqlFieldName('users.name', 'Name', 'user_name'), 
                      new sqlFieldName('instruments.name', 'Instrument', 'instrument_name'), 
                      new sqlFieldName('projects.name', 'Project name', 'project_name'), 
                      new sqlFieldName('comments', 'User comments'),
                      new sqlFieldName('log', 'Log entry')
                    );
    $type->where[] = 'deleted <> 1';
    $type->where[] = 'bookings.userid <> 0';
    $type->group = array('instrument_name', 'bookwhen', 'user_name', 'project_name');
    $type->breakField = 'instrument_name';
    $type->omitFields['instrument_name'] = 1;
    $type->omitFields['username'] = 1;
    return $type;
  }
  
  function _createProjects() {
    $type = new ExportType('project', 'bookings', 'Projects using instruments', 'instruments');
    $type->join[] = array('table' => 'instruments', 'condition' =>  'instruments.id=bookings.instrument');
    $type->join[] = array('table' => 'projects', 'condition' =>  'bookings.projectid=projects.id');
    $type->fields = array(
                      new sqlFieldName('instruments.name', 'Instrument', 'instrument_name'),
                      new sqlFieldName('projects.name', 'Project', 'project_name'),
                      new sqlFieldName('projects.longname', 'Description'),
                      new sqlFieldName('SUM(TIME_TO_SEC(duration))/60/60', 'Hours used',
                                                                    'hours_used')
                    );
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
    $type->fields = array(
                      new sqlFieldName('instruments.name', 'Instrument', 'instrument_name'),
                      new sqlFieldName('groups.name', 'Supervisor', 'group_name'),
                      new sqlFieldName('groups.longname', 'Group'),
                      new sqlFieldName('ROUND('
                                          .'SUM(TIME_TO_SEC(duration)*grouppc)/60/60/100,'
                                        .'2) ', 
                                      'Weighted hours used',
                                      'weighted_hours_used')
                   );
    $type->where[] = 'deleted <> 1';
    $type->where[] = 'bookings.userid <> 0';
    $type->group = array('instrument_name', 'group_name');
    return $type;
  }

  function _createUsers() {
    $type = new ExportType('user', 'bookings', 'Users using instruments', 'instruments');
    $type->join[] = array('table' => 'users', 'condition' =>  'users.id=bookings.userid');
    $type->join[] = array('table' => 'instruments', 'condition' =>  'instruments.id=bookings.instrument');
    $type->fields = array(
                      new sqlFieldName('username', 'Username'), 
                      new sqlFieldName('users.name', 'Name', 'user_name'), 
                      new sqlFieldName('instruments.name', 'Instrument', 'instrument_name'), 
                      new sqlFieldName('ROUND('
                                          .'SUM(TIME_TO_SEC(duration))/60/60,'
                                        .'2) ', 
                                      'Hours used',
                                      'hours_used')
                    );
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
                      new sqlFieldName('consumables.name', 'Item', 'consumable_name'),
                      new sqlFieldName('quantity', 'Quantity', 'quantity'),
                      new sqlFieldName('usewhen', 'Date'),
                      new sqlFieldName('username', 'Username'), 
                      new sqlFieldName('users.name', 'Name', 'user_name'), 
                      new sqlFieldName('projects.name', 'Project', 'project_name')
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
    $type->fields = array(
                      new sqlFieldName('username', 'Username'), 
                      new sqlFieldName('users.name', 'Name', 'user_name'), 
                      new sqlFieldName('instruments.name', 'Instrument', 'instrument_name'), 
                      new sqlFieldName('projects.name', 'Project name', 'project_name'), 
                      new sqlFieldName('groups.name', 'Supervisor', 'group_name'),
                      new sqlFieldName('groups.longname', 'Group'),
                      new sqlFieldName('bookwhen', 'Date/Time'), 
                      new sqlFieldName('duration', 'Length')
                    );
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
                      new sqlFieldName('groups.name', 'Supervisor', 'group_name'),
                      new sqlFieldName('groups.longname', 'Group'),
                      new sqlFieldName('consumables.name', 'Item', 'consumable_name'),
                      new sqlFieldName('consumables.cost', 'Unit cost'),
                      new sqlFieldName('SUM(consumables_use.quantity)', 'Quantity', 'totquantity'),
                      new sqlFieldName('(consumables.cost * grouppc/100)*SUM(consumables_use.quantity)',
                                          'Cost to group', 'cost_to_group')
                    );
    $type->group = array('group_name', 'consumable_name');
    return $type;
  }

  function _createBillingGroups() {
    $type = $this->_createGroups();
    $type->name = 'bookingbilling';
    $type->description = 'Billing data for instrument usage';
    $type->join[] = array('table' => 'costs', 'condition' =>  'costs.userclass=projects.defaultclass AND costs.instrumentclass=instruments.class');
    $type->fields = array(
                      new sqlFieldName('instruments.name', 'Instrument', 'instrument_name'), 
                      new sqlFieldName('groups.name', 'Supervisor', 'group_name'),
                      new sqlFieldName('SUM(ROUND(TIME_TO_SEC(duration)/60/60,2))', 'Total hours used', 'total_hours', EXPORT_HTML_DECIMAL),
/*                    'instruments.fulldaylength AS fulldaylength', 'instruments.halfdaylength AS halfdaylength', 'costs.hourfactor AS hourfactor','costs.halfdayfactor AS halfdayfactor',*/
                      new sqlFieldName(
                        'ROUND('
                        .'SUM('
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
                          .'END)'
                        .'*grouppc/100), '
                        .'2) ',             //END OF ROUND()
                        'Weighted days used', 'weighted_days_used', EXPORT_HTML_DECIMAL)
                   );
    $type->where[] = 'deleted <> 1';
    $type->where[] = 'bookings.userid <> 0';
    //$type->group = '';
    $type->group = array('instrument_name', 'group_name');
    return $type;
  }


  
  
      
} //ExportTypeList