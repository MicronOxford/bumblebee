<?php
/**
* User Authentication and Authorisation (Login and Permissions object)
*
* @author     Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage DBObjects
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

/** inherit from basic auth module */
require_once 'inc/bb/basicauth.php';
/** sql manipulation routines */
require_once 'inc/formslib/sql.php';
/** type checking and data manipulation */
require_once 'inc/typeinfo.php';
/** permissions codes */
require_once 'inc/permissions.php';
/** logging functions */
require_once 'inc/logging.php';

/**
* User *authorisation* and *authentication* object
*
* @package    Bumblebee
* @subpackage DBObjects
* @todo //TODO: update permissions system
* @todo //TODO: documentation
*/
class BumblebeeAuth extends BasicAuth {
  var $anonymous = false;
  var $euid;            //permit user masquerading like su. Effective UID
  var $ename;           //effective name
  var $eusername;       //effective username
  var $permissions = array();
  var $system_permissions = 0;

  /**
  *  Create the auth object
  *
  * @param array   $data    array containing keys 'username' and 'pass'
  * @param boolean $recheck (optional) ignore session data and check anyway
  * @param string  $table  (optional) db table from which login data should be taken
  */
  function BumblebeeAuth($data, $recheck = false, $table='users') {
    $this->_checkAnonymous($data);

    parent::BasicAuth($data, $recheck || $this->_changeUser(), $table);

    if ($this->_loggedin) {
      // set up Authorisation parts
      $this->_loadPermissions();
      $this->_checkMasq();
    }
  }

  function _changeUser() {
    if (isset($_POST['changeuser']) || isset($_GET['changeuser'])) {
      return true;
    }
  }

  function _checkAnonymous(&$data) {
    global $CONFIG;
    if ((isset($_POST['anonymous']) || isset($_GET['anonymous']))
        && issetSet($CONFIG['display'], 'AnonymousAllowed', false)) {
      $data['username'] = $CONFIG['display']['AnonymousUsername'];
      $data['pass'] = $CONFIG['display']['AnonymousPassword'];
    }
  }

  /**
   * Permit user masquerading -- the admin user can become another user for a period
   * of time to make a bookings etc
  **/
  function _checkMasq() {
    if ($this->masqPermitted() && $this->_var_get('euid') != NULL) {
      $this->euid      = $this->_var_get('euid');
      $this->ename     = $this->_var_get('ename');
      $this->eusername = $this->_var_get('eusername');
    }
  }

  function isSystemAdmin() {
    trigger_error("using deprecated isSystemAdmin", E_USER_NOTICE);
    $l = debug_backtrace();
    print "file= ". $l[0]['file'] .", line=".$l[0]['line']."\n<br />";
    return $this->system_permissions & BBPERM_ADMIN;
  }

  function isInstrumentAdmin($instr) {
    trigger_error("using deprecated isInstrumentAdmin", E_USER_NOTICE);
    $l = debug_backtrace();
    print "file= ". $l[0]['file'] .", line=".$l[0]['line']."\n<br />";
    return $this->isSystemAdmin() ||
          ($this->instrument_permissions($instr) & BBPERM_INSTR_BOOK_FUTURE);
  }


  function instrument_permissions($instrument) {
    global $CONFIG;

    if (isset($this->permissions[$instrument])) {
      return $this->permissions[$instrument];
    }
    $permission = 0;
    if ($instrument==0) {
      // look for permissions across all instruments
      $total = 0;
      global $TABLEPREFIX;
      $q = 'SELECT * '
          .' FROM '.$TABLEPREFIX.'permissions'
          .' WHERE userid=' . qw($this->uid);
      $sql = db_get($q, false);
      while ($row = db_fetch_array($sql)) {
        if (isset($row['permissions']) && $CONFIG['auth']['permissionsModel']) {
          $permission = $row['permissions'];
        } else {
          $permission = $this->_constructInstrumentPermission($row);
        }
        $this->permissions[$instrument] = $permission;
        $total = ((int)$total) | ((int)$permission);
      }
      $permission = $total;
    } else {
      $row = quickSQLSelect('permissions',
                              array('userid',   'instrid'),
                              array($this->uid, $instrument)
                           );
      if (is_array($row)) {
        if (isset($row['permissions']) && $CONFIG['auth']['permissionsModel']) {
          $permission = $row['permissions'];
        } else {
          $permission = $this->_constructInstrumentPermission($row);
        }
      }
    }
    //save the permissions to speed this up later
    $this->permissions[$instrument] = (int)$permission;
    return $this->permissions[$instrument];
  }

  /**
  * make up the permissions for the instrument
  *
  * @param    array   $row   from the database
  * @returns  integer        permissions
  */
  function _constructInstrumentPermission($row) {
    logmsg(2, "Making up some permissions for instrument. Upgrade database format to get rid of this message.");
    $permission = 0;
    if (isset($row['isadmin']) && $row['isadmin']) {
      $permission = BBPERM_INSTR_ALL;
    } else {
      $permission = BBPERM_INSTR_BASIC;
    }
    return $permission;
  }

  function getEUID() {
    return (isset($this->euid) ? $this->euid : $this->uid);
  }

  function masqPermitted($instr=0) {
    return $this->permitted(BBROLE_ADMIN_MASQ, $instr);
  }

  function amMasqed() {
    return (isset($this->euid) && $this->euid != $this->uid);
  }

  /**
  * start masquerading as another user
  */
  function assumeMasq($id) {
    if ($this->masqPermitted()) {
      //masquerade permitted
      $row = $this->_retrieveUserInfo($id, 0);
      $this->_var_put('euid',       $this->euid      = $row['id']);
      $this->_var_put('eusername',  $this->eusername = $row['username']);
      $this->_var_put('ename',      $this->ename     = $row['name']);
      return $row;
    } else {
      // masquerade not permitted
      echo "Couldn't assume masq";
      return 0;
    }
  }

  /**
  * stop masquerading as another user
  */
  function removeMasq() {
    $this->_var_put('euid',        $this->euid      = null);
    $this->_var_put('eusername',   $this->eusername = null);
    $this->_var_put('ename',       $this->ename     = null);
  }

  function permitted($operation, $instrument=NULL) {
    //print "Requested: $operation and have permissions $this->system_permissions<br/>";
    // NOTE: Must cast to int before using PHP's bitwise operators else you will get stupid results
    // due to the loose typing mechanism switching the variables to float or string on you.
    if ($operation == BBROLE_NONE) return true;
    if ($instrument === NULL) {
      // looking for system permissions
      return ((int) $operation & (int) $this->system_permissions) == $operation;
    } else {
      #echo "op = ". $operation;
      #echo "instr = " .(int) $this->instrument_permissions($instrument);
      #echo "ok=". ((int) $this->instrument_permissions($instrument) & (int) $operation);
      #echo "<br />";
      return (((int) $operation)
            & ( (int) $this->system_permissions | (int) $this->instrument_permissions($instrument) ))
           == $operation;
    }
  }

  function _loadPermissions() {
    global $CONFIG;

    if (! $this->isLoggedIn()) return;

    /// FIXME
    if (isset($this->user_row['permissions']) && $CONFIG['auth']['permissionsModel']) {
      $this->system_permissions = (int) $this->user_row['permissions'];
    } else {
      logmsg(2, "Making up some permissions for user. Upgrade database format to get rid of this message.");
      $this->system_permissions = 0;
      if (isset($this->user_row['isadmin']) && $this->user_row['isadmin']) {
        $this->system_permissions |= BBPERM_ADMIN_ALL;
      } else {
        if ($this->localLogin) {
          $this->system_permissions |= BBPERM_USER_BASIC | BBPERM_USER_PASSWD;
        } else {
          $this->system_permissions |= BBPERM_USER_BASIC;
        }
      }
      if ($this->masqPermitted()) {
        $this->system_permissions |= BBPERM_ADMIN_MASQ;
      }
    }
    if (! $this->localLogin) {
      $this->system_permissions = $this->system_permissions & (~ BBPERM_USER_PASSWD);
    }
  }

} //BumblebeeAuth

?>
