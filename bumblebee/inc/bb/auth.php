<?php
# $Id$
# Authorisation object

include_once 'inc/formslib/sql.php';
include_once 'inc/typeinfo.php';

class BumbleBeeAuth {
  var $uid;    //user id from table
  var $username;
  var $name;
  var $isadmin;
  var $euid;   //permit user masquerading like su. Effective UID
  var $ename;  //effective name
  var $eusername;  //effective username
  var $permissions;
  var $_loggedin=0;
  var $_error;
  var $table;
  var $localLogin = 0;
  var $DEBUGMODE = 1;

  function BumbleBeeAuth($table='users') {
    session_start();
    $this->table = $table;
    if (isset($_SESSION['uid'])) {
      // the we have a session login already done, check it
      $this->_loggedin = $this->_verifyLogin();
      $this->_checkMasq();
    } elseif (isset($_POST['username'])) {
      // then some login info has been provided, so we need to check it
      $this->_loggedin = $this->_login();
    } else {
      // we're not logged in at all
    }
    $this->permissions = array();
  }

  function logout() {
    session_destroy();
    $this->_loggedin = 0;
  }

  function isLoggedIn() {
    return $this->_loggedin;
  }

  function loginError() {
    if ($this->DEBUGMODE) {
      return $this->_error;
    } else {
      list($public,$private) = preg_split("/:/", $this->_error);
      return $public;
    }
  }

  function _createSession($row) {
    $_SESSION['uid'] = $this->uid = $row['id'];
    $_SESSION['username'] = $this->username = $row['username'];
    $_SESSION['name'] = $this->name = $row['name'];
    $_SESSION['isadmin'] = $this->isadmin = $row['isadmin'];
    $_SESSION['localLogin'] = $this->localLogin = $this->localLogin;
  }
  
  function _verifyLogin() {
    // check that the credentials contained in the session are OK
    $uid = $_SESSION['uid'];
    $row = $this->_retrieveUserInfo($uid, 0);
    //preDump($row);
    if ($row['username'] == $_SESSION['username'] && 
        $row['name'] == $_SESSION['name'] && 
        $row['isadmin'] == $_SESSION['isadmin']) {
      $this->uid = $uid;
      $this->username = $_SESSION['username'];
      $this->name = $_SESSION['name'];
      $this->isadmin = $_SESSION['isadmin'];
      $this->localLogin = $_SESSION['localLogin'];
      return 1;
    } else {
      $this->logout();
      $this->_error = 'SESSION INVALID: Login failed.';
      return 0;
    }
  }

  /**
   * Permit user masquerading -- the admin user can become another user for a period
   * of time to make a bookings etc
  **/
  function _checkMasq() {
    if ($this->masqPermitted() && isset($_SESSION['euid'])) {
      $this->euid = $_SESSION['euid'];
      $this->ename = $_SESSION['ename'];
      $this->eusername = $_SESSION['eusername'];
    }
    return 1;
  }
  
  /** 
   * check login details, if OK, set up a PHP SESSION to manage the login
   *
   * @returns boolean credentialsOK
   */
  function _login() {
    global $CONFIG;
    //FIXME is it fair to assume alphabetic? 
    if (! is_alphabetic($_POST['username']) || ! isset($_POST['pass']) ) {
      //preDump($_POST);
      $this->_error = 'Login failed: bad username';
      return 0;
    }
    // then there is data provided to us in a login form
    // need to verify if it is valid login info
    $PASSWORD = $_POST['pass'];
    $USERNAME = $_POST['username'];
    $row = $this->_retrieveUserInfo($USERNAME);
    if ($row == '0') { 
      $this->_error = 'Login failed: username doesn\'t exist in table';
      return false;
    }
    
    $authOK = 0;
    if ($CONFIG['auth']['useRadius'] && $CONFIG['auth']['RadiusPassToken'] == $row['passwd']) {
      $authOK = $this->_auth_via_radius($USERNAME, $PASSWORD);
    } elseif ($CONFIG['auth']['useLDAP'] && $CONFIG['auth']['LDAPPassToken'] == $row['passwd']) {
      $authOK = $this->_auth_via_ldap($USERNAME, $PASSWORD);
    } elseif ($CONFIG['auth']['useLocal']) {
      $this->localLogin = 1;
      $authOK = $this->_auth_local($USERNAME, $PASSWORD, $row);
    } else {   //system is misconfigured
      $this->_error = 'System has no login method enabled';
    }
    if (! $authOK) {
      return false;
    }
    if ($row['suspended']) {
      $this->_error = 'Login failed: this account is suspended, please contact us about this.';
      return false;
    }
    // if we got to here, then we're logged in!
    $this->_createSession($row);
    return true;
  }
 
  function _retrieveUserInfo($identifier, $type=1) {
    $row = quickSQLSelect('users',($type?'username':'id'),$identifier);
    if (! is_array($row)) {
      $this->_error = "Login failed: unknown username";
      return 0;
    }
    //$row = mysql_fetch_array($sql);
    return $row;
  }
  
  function _auth_via_radius($username, $password) {
    require_once 'Auth/Auth.php';
    $RADIUSCONFIG = parse_ini_file('config/radius.ini');
    $params = array(
                "servers" => array(array($RADIUSCONFIG['host'], 
                                         0, 
                                         $RADIUSCONFIG['key'],
                                         3, 3)
                                  ),
                "authtype" => $RADIUSCONFIG['authtype']
                );
    $a = new Auth("RADIUS", $params);
    $a->username = $username;
    $a->password = $password;
    // the PEAR::Auth classes throw up a "login" dialogue automatically, and we don't want it
    // discard it using output buffering.
    ob_start();
    $a->start();
    ob_end_clean();
    $auth = $a->getAuth();
    if (! $auth) {
      $this->_error = 'Login failed: radius auth failed';
    }
    return $auth;
  }

  function _auth_via_ldap($username, $password) {
    $this->_error = 'Login failed: LDAP authentication unimplemented';
    return false;
  }

  function _auth_local($username, $password, $row) {
    $auth = ($row['passwd'] == md5($password));
    if (! $auth) {
      $this->_error = 'Login failed: bad password';
    }
    return $auth;
  }
        
  function isSystemAdmin() {
    return $this->isadmin;
  }
  
  function isInstrumentAdmin($instr) {
    if ($instr==0) {
       return false;
    }
    if (! isset($this->permissions[$instr]) || $this->permissions[$instr]===NULL) {
       $row = quickSQLSelect('permissions',array('userid','instrid'), array($this->uid,$instr));
       $this->permissions[$instr] = (is_array($row) && $row['isadmin']);
    }
    return $this->permissions[$instr];
  }
  
  function getEUID() {
    return (isset($this->euid) ? $this->euid : $this->uid);
  }
   
  function masqPermitted($instr=0) {
    return $this->isadmin || $this->isInstrumentAdmin($instr);
  }

  /** 
   * start masquerading as another user
  **/
  function assumeMasq($id) {
    if ($this->masqPermitted()) {
      //masquerade permitted
      $row = $this->_retrieveUserInfo($id, 1);
      $_SESSION['euid'] = $this->uid = $row['id'];
      $_SESSION['eusername'] = $this->username = $row['username'];
      $_SESSION['ename'] = $this->name = $row['name'];
    } else {
      // masquerade not permitted
      return 0;
    }
  }
   
  /** 
   * stop masquerading as another user
  **/
  function removeMasq($id) {
    $_SESSION['euid'] = $this->euid = null;
    $_SESSION['eusername'] = $this->eusername = null;
    $_SESSION['ename'] = $this->ename = null;
  }
  
  function isMe($id) {
    return $id == $this->uid;
  }

  function getRemoteIP() {
    return (getenv('HTTP_X_FORWARDED_FOR')
           ?  getenv('HTTP_X_FORWARDED_FOR')
           :  getenv('REMOTE_ADDR'));
  }

} //BumbleBeeAuth

?> 
