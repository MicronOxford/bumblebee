<?php
/**
* User object (extends dbo), with extra customisations for other links
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
require_once 'inc/formslib/radiolist.php';
require_once 'inc/formslib/checkbox.php';
require_once 'inc/formslib/bitmask.php';
require_once 'inc/formslib/passwdfield.php';
require_once 'inc/formslib/droplist.php';
require_once 'inc/formslib/joindata.php';

/**
* User object (extends dbo), with extra customisations for other links
*
* @package    Bumblebee
* @subpackage DBObjects
* @todo //TODO:       Editing method for new permissions model
*/
class User extends DBRow {

  var $_localAuthPermitted;
  var $_authList;
  var $_magicPassList;
  var $_authMethod;
  var $_auth;

  function User($auth, $id, $passwdOnly=false) {
    $conf = ConfigReader::getInstance();

    $this->DBRow('users', $id);
    #$this->DEBUG=10;
    $this->_auth = $auth;
    $this->editable = ! $passwdOnly;
    $this->use2StepSync = 1;
    $this->deleteFromTable = 0;
    $f = new IdField('id', T_('User ID'));
    $f->editable = 0;
    $this->addElement($f);
    $f = new TextField('username', T_('Username'));
    $attrs = array('size' => '48');
    $f->required = 1;
    $f->requiredTwoStage = 1;
    $f->isValidTest = 'is_valid_username';
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('name', T_('Name'));
    $f->isValidTest = 'is_empty_string';
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('email', T_('Email'));
    $f->required = ! $passwdOnly;
    $f->isValidTest = 'is_empty_string';
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('phone', T_('Phone'));
    $f->required = ! $passwdOnly;
    $f->isValidTest = 'is_empty_string';
    $f->setAttr($attrs);
    $this->addElement($f);

    if (! $passwdOnly) {
      $f = new CheckBox('suspended', T_('Suspended'));
      $this->addElement($f);

      if (! $conf->value('auth', 'permissionsModel')) {
        $f = new CheckBox('isadmin', T_('System Administrator'));
        $this->addElement($f);
      } else {
        $f = new Bitmask('permissions',  T_('System permissions'), T_('Grant these system-wide permissions to the user'), T_('Grant'));
        $f->setValuesArray($this->SystemPermissions(), 'id', 'iv');
        $f->showHideButtons = true;
        if ($id == -1) {
          $f->set(BBPERM_USER_BASIC);
        }
        $this->addElement($f);
      }
    }

    // association of user with an authentication method
    $this->_findAuthMethods();
    $f = new RadioList('auth_method', T_('User authentication method'));
    $f->sqlHidden = 1;
    $f->setValuesArray($this->_authList, 'id', 'iv');
    $f->setFormat('id', '%s', array('iv'));
    $f->setAttr($attrs);
    $f->required = 1;
    $f->hidden = $passwdOnly;
    $this->addElement($f);
    if ($this->_localAuthPermitted) {
      $password = new PasswdField('passwd', T_('Password (for local login)'));
      $password->setAttr(array('size' => 24));
      //$password->isValidTest = 'is_nonempty_string';
      $password->suppressValidation = 0;
      $password->editable = 1;
      //$f->list->append(array('local','Local login: '), $password);
      $this->addElement($password);

      //repeat the password field so that we can check that the user entered
      //what they thought they did
      $password_veri = new PasswdField('passwd_veri', T_('Please re-enter password'));
      $password_veri->setAttr(array('size' => 24));
      $password_veri->suppressValidation = 0;
      $password_veri->editable = 1;
      $password_veri->sqlHidden = true;

      $this->addElement($password_veri);
    }

    if (! $passwdOnly) {
      // association of users to projects
      $f = new JoinData('userprojects',
                        'userid', $this->id,
                        'projects', T_('Project membership'));
      $projectfield = new DropList('projectid', T_('Project'));
      $projectfield->connectDB('projects', array('id', 'name', 'longname'));
      $projectfield->prepend(array('0',T_('(none)'), T_('no selection')));
      $projectfield->setDefault(0);
      $projectfield->setFormat('id', '%s', array('name'), ' (%25.25s)', array('longname'));
      $f->addElement($projectfield);
      $f->joinSetup('projectid', array('minspare' => 2));
      $f->colspan = 2;
      $this->addElement($f);
      //preDump($f);
      // association of users with instrumental permissions
      $f = new JoinData('permissions',
                        'userid', $this->id,
                        'instruments', T_('Instrument permissions'));
      $instrfield = new DropList('instrid', T_('Instrument'));
      $instrfield->connectDB('instruments', array('id', 'name', 'longname'));
      $instrfield->prepend(array('0', T_('(none)'), T_('no selection')));
      $instrfield->setDefault(0);
      $instrfield->setFormat('id', '%s', array('name'), ' (%25.25s)', array('longname'));
      $f->addElement($instrfield);
      $subscribeAnnounce = new CheckBox('announce', T_('Subscribe: announce'));
      $subscribeAnnounce->defaultValue = 1;
      $f->addElement($subscribeAnnounce);
      $unbookAnnounce = new CheckBox('unbook', T_('Subscribe: unbook'));
      $f->addElement($unbookAnnounce);

      if (! $conf->value('auth', 'permissionsModel')) {
        $instradmin = new CheckBox('isadmin', T_('Instrument admin'));
        $f->addElement($instradmin);
      } else {
        $bm = new Bitmask('permissions',  T_('Instrument permissions'), T_('Grant these instrument permissions to the user'), T_('Grant'));
        $bm->setValuesArray($this->InstrumentPermissions(), 'id', 'iv');
        $bm->showHideButtons = true;
        $bm->defaultValue = BBPERM_INSTR_BASIC;
        $f->addElement($bm);
      }

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
      $f->colspan = 2;
      $this->addElement($f);
    }

    $this->fill($id);
    $this->dumpheader = 'User object';
  }

  function _findAuthMethods() {
    $conf = ConfigReader::getInstance();

    $this->_localAuthPermitted = ($conf->value('auth', 'useLocal') !== null)
                                        && $conf->value('auth', 'useLocal')?true:false;
    $this->_authList = array();
    foreach ($conf->getSection('auth') as $key => $val) {
      if (strpos($key, 'use') === 0 && $val) {
        $method = substr($key,3);
        $this->_authList[$method] = $method;
        $this->_magicPassList[$method] = $conf->value('auth', $method.'PassToken');
      }
    }
  }

  function fill() {
    parent::fill();
    //now edit the passwd/auth fields
    $this->_authMethod = 'Local';
    foreach($this->_magicPassList as $meth => $passtok) {
      if ($this->fields['passwd']->value == $passtok) {
        $this->_authMethod = $meth;
      }
    }
    if ($this->_authMethod != 'Local') {
     $this->fields['passwd']->crypt_method = '';
    } else {
     $this->fields['passwd']->crypt_method = $this->_magicPassList['Local'];
    }
    $this->fields['auth_method']->set($this->_authMethod);
    //echo $this->fields['passwd']->value;
  }

  function sync() {
    //$this->DEBUG = 10;
    //monkey the passwd/auth fields
    //echo $this->_authMethod. '-';
    //preDump($this->fields['passwd']);
    //echo $this->fields['passwd']->value;
    //echo $this->fields['auth_method']->changed.'/'.$this->fields['passwd']->value;
    $this->_authMethod = $this->fields['auth_method']->getValue();
    if ($this->_authMethod == 'Local') {
      $this->fields['passwd']->crypt_method = $this->_magicPassList['Local'];
      if (in_array($this->fields['passwd']->value, $this->_magicPassList)) {
        $this->fields['passwd']->value = '';
      }
    }
    if ($this->fields['auth_method']->changed || $this->fields['passwd']->changed) {

      if($this->_authMethod == 'Local' &&
	 $this->fields['passwd']->value != $this->fields['passwd_veri']->value) {

	 $this->fields['passwd']->isValid = 0;
	 $this->fields['passwd']->changed = 1;

	 $this->fields['passwd_veri']->isValid = 0;
	 $this->fields['passwd_veri']->changed = 0;


	 $this->errorMessage .= T_('The supplied passwords did not match. Please retry.').'<br/>';
	 $this->isValid = 0;
	 $this->changed = 1;

      } elseif ($this->_authMethod != 'Local'
            /*&& $this->fields['passwd']->value != ''*/
            && $this->fields['passwd']->value != $this->_magicPassList[$this->_authMethod]) {
        $this->log('User::sync(): indulging in password munging, '. $this->_authMethod);
        $this->fields['passwd']->set($this->_magicPassList[$this->_authMethod]);
        $this->fields['passwd']->crypt_method = '';
        $this->fields['passwd']->changed = 1;
        $this->changed = 1;

      } elseif ($this->_authMethod == 'Local' && $this->fields['passwd']->value == ''
                        && $this->fields['username']->value != '')  {
        $this->fields['passwd']->changed = 1;
        $this->fields['passwd']->isValid = 0;
        $this->errorMessage .= T_('password must be set for local login.').'<br/>';
        $this->isValid = 0;
      } else {
      }
    }
    return parent::sync();
  }

  function display() {
    return $this->displayAsTable();
  }

  function SystemPermissions() {
    $p = array();
    $p[BBPERM_USER_VIEW_LIST_ALL]     = T_('View list of all instruments');
    $p[BBPERM_USER_VIEW_CALENDAR_ALL] = T_('View calendar of all instruments');
    $p[BBPERM_USER_VIEW_BOOKINGS_ALL] = T_('View bookings on all instruments');
    $p[BBPERM_USER_MAKE_BOOKINGS_ALL] = T_('Make bookings on all instruments');
    $p[BBPERM_USER_PASSWD]            = T_('Change own password');
    $p[BBPERM_USER_LOGOUT]            = T_('Logout from system');
    $p[BBPERM_ADMIN_GROUPS]           = T_('Admin: edit groups');
    $p[BBPERM_ADMIN_PROJECTS]         = T_('Admin: edit projects');
    $p[BBPERM_ADMIN_USERS]            = T_('Admin: edit users');
    $p[BBPERM_ADMIN_INSTRUMENTS]      = T_('Admin: edit instruments');
    $p[BBPERM_ADMIN_CONSUMABLES]      = T_('Admin: edit consumables');
    $p[BBPERM_ADMIN_CONSUME]          = T_('Admin: record consumables');
    $p[BBPERM_ADMIN_COSTS]            = T_('Admin: edit costs');
    $p[BBPERM_ADMIN_DELETEDBOOKINGS]  = T_('Admin: view deleted bookings');
    $p[BBPERM_ADMIN_MASQ]             = T_('Admin: masquerade as another user');
    $p[BBPERM_ADMIN_EMAILLIST]        = T_('Admin: export email list');
    $p[BBPERM_ADMIN_EXPORT]           = T_('Admin: export usage data');
    $p[BBPERM_ADMIN_BILLING]          = T_('Admin: send out billing emails');
    $p[BBPERM_ADMIN_BACKUPDB]         = T_('Admin: backup database');
    return $p;
  }


  function InstrumentPermissions() {
    $p = array();
    $p[BBPERM_INSTR_VIEW]          = T_('View booking sheet');
    $p[BBPERM_INSTR_VIEW_BOOKINGS] = T_('View bookings');
    $p[BBPERM_INSTR_BOOK]          = T_('Make bookings');
    $p[BBPERM_INSTR_UNBOOK]        = T_('Delete own bookings');

    $p[BBPERM_INSTR_VIEW_FUTURE]   = T_('View booking sheet into the future');
    $p[BBPERM_INSTR_BOOK_FUTURE]   = T_('Make bookings into the future');
    $p[BBPERM_INSTR_UNBOOK_PAST]   = T_('Delete own past bookings');

    $p[BBPERM_INSTR_MASQ]          = T_('Masquerade as other users');
    $p[BBPERM_INSTR_BOOK_FREE]     = T_('Make bookings at any time');
    $p[BBPERM_INSTR_VIEW_DETAILS]  = T_('Admin: View detailed booking information');
    $p[BBPERM_INSTR_EDIT_ALL]      = T_('Admin: Edit others\' bookings');
    $p[BBPERM_INSTR_UNBOOK_OTHER]  = T_('Admin: Delete others\' bookings');
    $p[BBPERM_INSTR_EDIT_CONFIG]   = T_('Admin: change instrument config');
    return $p;
  }

} //class User
