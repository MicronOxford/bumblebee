<?php
# $Id$
# User object (extends dbo), with extra customisations for other links

include_once 'dbforms/dbrow.php';
include_once 'dbforms/textfield.php';
include_once 'dbforms/radiolist.php';
include_once 'dbforms/checkbox.php';

class User extends DBRow {
  
  function User($id) {
    $this->DBRow('users', $id);
    $this->editable = 1;
    $f = new TextField('id', 'UserID');
    $f->editable = 0;
    $this->addElement($f);
    $f = new TextField('username', 'Username');
    $attrs = array('size' => '48');
    $f->required = 1;
    $f->isValidTest = 'is_empty_string';
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('name', 'Name');
    $f->required = 1;
    $f->isValidTest = 'is_empty_string';
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('email', 'Email');
    $f->required = 1;
    $f->isValidTest = 'is_empty_string';
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('phone', 'Phone');
    $f->required = 1;
    $f->isValidTest = 'is_empty_string';
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new CheckBox('suspended', 'Suspended');
    $this->addElement($f);
    // association of users to projects
    $f = new JoinData('userprojects',
                       'userid', $this->id,
                       'projects', 'Project membership');
    $projectfield = new DropList('projectid', 'Project');
    $projectfield->connectDB('projects', array('id', 'name', 'longname'));
    $projectfield->prepend(array('0','(none)'));
    $projectfield->setDefault(0);
    $projectfield->setFormat('id', '%s', array('name'), ' (%s)', array('longname'));
    $f->addElement($projectfield);
    $f->joinSetup('projectid', array('minspare' => 2));
    $this->addElement($f);

    // association of users with instrumental permissions
    $f = new JoinData('permissions',
                       'userid', $this->id,
                       'instruments', 'Instrument permissions');
    $instrfield = new DropList('instrid', 'Instrument');
    $instrfield->connectDB('instruments', array('id', 'name'));
    $instrfield->prepend(array('0','(none)'));
    $instrfield->setDefault(0);
    $instrfield->setFormat('id', '%s', array('name'), ' (%s)', array('longname')
);
    $f->addElement($instrfield);
    $subscribeAnnounce = new CheckBox('announce', 'Subscribe: announce');
    $f->addElement($subscribeAnnounce);
    $unbookAnnounce = new CheckBox('unbook', 'Subscribe: unbook');
    $f->addElement($unbookAnnounce);
    /*  
    //Add these fields in once we need this functinality
    $hasPriority = new CheckBox('haspriority', 'Booking priority');
    $f->addElement($hasPriority);
    $bookPoints = new TextField('points', 'Booking points');
    $f->addElement($bookPoints);
    $bookPointsRecharge = new TextField('pointsrecharge', 'Booking points recharge');
    $f->addElement($bookPointsRecharge);
    */
    $f->joinSetup('instrid', array('minspare' => 2));
    $this->addElement($f);

    $this->fill($id);
    $this->dumpheader = 'User object';
  }

  function display() {
    return $this->displayAsTable();
  }

  function displayAsTable() {
    $t = '<table class="tabularobject">';
    foreach ($this->fields as $k => $v) {
      $t .= $v->displayInTable(2);
    }
    $t .= '</table>';
    return $t;
  }

} //class User
