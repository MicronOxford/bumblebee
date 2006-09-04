<?php
/**
* User Authentication and Authorisation (Login and Permissions object)
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
* @todo update permissions system
* @todo documentation
*/
class BumblebeeAuth extends BasicAuth {
  var $isadmin;
  var $euid;            //permit user masquerading like su. Effective UID
  var $ename;           //effective name
  var $eusername;       //effective username
  var $permissions = array();
  var $system_permissions;

  /**
  *  Create the auth object
  *
  * @param array   $data    array containing keys 'username' and 'pass'
  * @param boolean $recheck (optional) ignore session data and check anyway
  * @param string $table  (optional) db table from which login data should be taken
  */
  function BumblebeeAuth($data, $recheck = false, $table='users') {
    parent::BasicAuth($data, $recheck, $table);
    
    if ($this->_loggedin) {
      // set up Authorisation parts
      $this->isadmin = $this->user_row['isadmin'];
    }
    
    #FIXME
    if ($this->isadmin) {
      $this->system_permissions = BBPERM_ADMIN_ALL;
    } else {
      if ($this->localLogin) {
        $this->system_permissions = BBPERM_USER_ALL | BBPERM_USER_PASSWD;
      } else {
        $this->system_permissions = BBPERM_USER_ALL;
      }
    }
    if ($this->masqPermitted()) {
      $this->system_permissions |= BBPERM_MASQ;
    }
  }

  /**
   * Permit user masquerading -- the admin user can become another user for a period
   * of time to make a bookings etc
  **/
  function _checkMasq() {
    global $SESSIDX;
    if ($this->masqPermitted() && $this->_var_get('euid') != NULL) {
      $this->euid      = $this->_var_get('euid');
      $this->ename     = $this->_var_get('ename');
      $this->eusername = $this->_var_get('eusername');
    }
  }

  function isSystemAdmin() {
    return $this->isadmin;
  }
  
  function isInstrumentAdmin($instr) {
    if (isset($this->permissions[$instr])) {
      return $this->permissions[$instr];
    }
    $permission = 0;
    if ($instr==0) {
      // we can use cached queries for this too
      if (in_array(1, $this->permissions)) {
        return 1;
      }
      // then we look at *any* instrument that we have this permission for
       $row = quickSQLSelect('permissions',
                                array('userid',  'isadmin'), 
                                array($this->uid, 1)
                            );
      if (is_array($row)) {
        $this->permissions[$instr] = 1;
        $instr = $row['instrid'];
        $permission = 1;
      }
    } else {
      $row = quickSQLSelect('permissions',
                              array('userid',   'instrid'), 
                              array($this->uid, $instr)
                           );
      $permission = (is_array($row) && $row['isadmin']);
    }
    //save the permissions to speed this up later
    $this->permissions[$instr] = $permission;
    return $this->permissions[$instr];
  }
  
  function getEUID() {
    return (isset($this->euid) ? $this->euid : $this->uid);
  }
   
  function masqPermitted($instr=0) {
    return $this->isadmin || $this->isInstrumentAdmin($instr);
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
      return 0;
    }
  }
   
  /** 
  * stop masquerading as another user
  */
  function removeMasq() {
    global $SESSIDX;
    $this->_var_put('euid',        $this->euid      = null);
    $this->_var_put('eusername',   $this->eusername = null);
    $this->_var_put('ename',       $this->ename     = null);
  }

  function permitted($operation, $instrument=NULL) {
    // print "Requested: $operation and have permissions $this->system_permissions<br/>";
    if ($instrument===NULL) {
      // looking for system permissions
      return $operation & $this->system_permissions;
    } else {
      return $operation & $this->instrument_permission($instrument);
    }
  }
  
} //BumblebeeAuth

?> 
