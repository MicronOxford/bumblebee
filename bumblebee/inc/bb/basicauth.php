<?php
/**
* User Authentication object
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

/** sql manipulation routines */
require_once 'inc/formslib/sql.php';
/** type checking and data manipulation */
require_once 'inc/typeinfo.php';
/** username and password checks */
require_once 'inc/passwords.php';
/** permissions codes */
require_once 'inc/permissions.php';
/** logging functions */
require_once 'inc/logging.php';
/** configuration */
require_once 'inc/bb/configreader.php';

/**
* User Authentication
*
* @package    Bumblebee
* @subpackage DBObjects
* @todo //TODO: update permissions system
* @todo //TODO: documentation
*/
class BasicAuth {
  var $uid;    //user id from table
  var $username;
  var $name;
  var $email;
  var $_loggedin=0;
  var $_error = '';
  var $table;
  var $localLogin = 0;
  /** @var array      database row for the user */
  var $user_row = array();
  /** @var integer    debug level (0=off, 10=verbose)  */
  var $DEBUG = 0;

  /**
  *  Create the authentication object
  *
  * @param array   $data    array containing keys 'username' and 'pass'
  * @param boolean $recheck (optional) ignore session data and check anyway
  * @param string  $table  (optional) db table from which login data should be taken
  */
  function BasicAuth($data, $recheck = false, $table='users') {
    $conf = ConfigReader::getInstance();
    // Only start the session if one has not already been started (e.g. to cope
    // with the situation where session.auto_start=1 in php.ini or where
    // the entire thing is embedded within some other framework.
    // For session.auto_start, the following is enough:
    //      if (! ini_get('session.auto_start')) {
    // But we can check the session_id() (hexadecimal string if session has started
    // empty string "" if it hasn't)
    #preDump($_SESSION);
    if (! session_id()) {
      #print "Creating new session". session_id();;
      session_name('BumblebeeLogin');
      session_set_cookie_params(ini_get('session.cookie_lifetime'), $conf->BasePath.'/');
      session_start();
      #print "Started session, ". session_id();
    }
    $this->table = $table;
    if (!$recheck && $this->_var_get('uid') !== NULL) {
      // the we have a session login already done, check it
      $this->_loggedin = $this->_verifyLogin();
    } elseif (isset($data['username'])) {
      // then some login info has been provided, so we need to check it
      $this->_loggedin = $this->_login($data);
    } else {
      // we're not logged in at all
    }
  }

  /**
  * test function to see if user is logge in
  *
  * @returns boolean  user is logged in
  */
  function isLoggedIn() {
    return $this->_loggedin;
  }

  /**
  * log the user out of the system
  */
  function logout() {
    session_destroy();
    $this->_loggedin = 0;
    logmsg(4, "User '$this->username' logged out");
  }

  /**
  * obtain an error string that (if appropriate) describes the failure mode
  * @returns string  error message
  */
  function loginError() {
    $conf = ConfigReader::getInstance();
    if ($this->DEBUG ||
          ($conf->value('auth', 'authAdvancedSecurityHole') &&
           $conf->value('auth', 'verboseFailure'))) {
      return xssqw($this->_error);
    } elseif (strpos($this->_error, ':') !== false) {
      // protect any additional info that is in the error string:
      // functions in this class can report the error in the format 'General error: details'
      // Normally, we shouldn't reveal whether it was a bad username or password,
      // but for debugging purposes, it's nice to have the extra info.
      list($public,$private) = preg_split('/:/', $this->_error);
      return xssqw($public);
    } else {
      return xssqw($this->_error);
    }
  }

  /**
  * store a piece of data in the session for persistance across page calls
  *
  * @param string $var    name to call the data in the session
  * @param mixed  $value  value to store
  */
  function _var_put($var, $value) {
    $conf = ConfigReader::getInstance();
    $_SESSION[$conf->SessionIndex][$var] = $value;
  }

  /**
  * retrieve a piece of data previously stored
  * @returns mixed  value stored
  */
  function _var_get($var) {
    $conf = ConfigReader::getInstance();
    return issetSet($_SESSION[$conf->SessionIndex], $var);
  }

  /**
  * create the login session for persistant data storage
  * @param array  $row  database row for the user
  */
  function _createSession($row) {
    $this->_var_put('uid',        $this->uid        = $row['id']);
    $this->_var_put('username',   $this->username   = $row['username']);
    $this->_var_put('name',       $this->name       = $row['name']);
//     $this->_var_put('email']      = $this->email      = $row['email'];
//     $this->_var_put('isadmin']    = $this->isadmin    = $row['isadmin'];
    $this->_var_put('localLogin', $this->localLogin);
    logmsg(4, "User '$this->username' logged in");
  }

  function _verifyLogin() {
    // check that the credentials contained in the session are OK
    $uid = $this->_var_get('uid');
    $row = $this->_retrieveUserInfo($uid, 0);
    $this->user_row = $row;
    if ($row['username']  == $this->_var_get('username') &&
        $row['name']      == $this->_var_get('name') ) {
      $this->uid        = $uid;
      $this->username   = $this->_var_get('username');
      $this->name       = $this->_var_get('name');
      $this->email      = $row['email'];
      $this->isadmin    = $row['isadmin'];
      $this->localLogin = $this->_var_get('localLogin');
      logmsg(4, "User '$this->username' session accepted");
      return 1;
    } else {
      $this->logout();
      $this->_error = T_('Login failed: SESSION INVALID!');
      return 0;
    }
  }

  /**
  * check login details, if OK, set up a PHP SESSION to manage the login
  *
  * @returns boolean credentialsOK
  */
  function _login($data) {
    $conf = ConfigReader::getInstance();
    // a login attempt must have a password
    if (! isset($data['pass']) ) {
      $this->_error = 'Login failed: no password specified.';
      return false;
    }
    // test the username to make sure it looks valid
    if (! is_valid_username($data['username'])) {
      $this->_error = T_('Login failed: bad username') .' -- '
                     .T_('Either change the username using phpMyAdmin or change how you define a valid username in config/bumblebee.ini (see the value "validUserRegexp")');
      return false;
    }
    // then there is data provided to us in a login form
    // need to verify if it is valid login info
    $PASSWORD = $data['pass'];
    $USERNAME = $data['username'];
    $row = $this->_retrieveUserInfo($USERNAME);
    $this->user_row = $row;

    // if the admin user has locked themselves out of the system, let them get back in:
    if ($conf->value('auth','authAdvancedSecurityHole') && $conf->value('auth','recoverAdminPassword')) {
      $this->_createSession($row);
      return true;
    }

    // the username has to exist in the users table for the login to be valid, so check that first
    if ($row == '0') {
      return false;
    }

    $authOK = 0;
    if ($conf->value('auth', 'useRadius') && $conf->value('auth', 'RadiusPassToken') == $row['passwd']) {
      $authOK = $this->_auth_via_radius($USERNAME, $PASSWORD);
    } elseif ($conf->value('auth','useLDAP') && $conf->value('auth','LDAPPassToken') == $row['passwd']) {
      $authOK = $this->_auth_via_ldap($USERNAME, $PASSWORD);
    } elseif ($conf->value('auth', 'useLocal')) {
      $this->localLogin = 1;
      $authOK = $this->_auth_local($USERNAME, $PASSWORD);
    } else {   //system is misconfigured
      $this->_error = T_('System has no login method enabled');
    }
    if (! $authOK) {
      return false;
    }
    if (isset($row['suspended']) && $row['suspended']) {
      $this->_error = T_('Login failed: this account is suspended, please contact us about this.');
      return false;
    }
    // if we got to here, then we're logged in!
    $this->_createSession($row);
    return true;
  }

  function _retrieveUserInfo($identifier, $type=1) {
    $conf = ConfigReader::getInstance();
    $row = quickSQLSelect('users',($type?'username':'id'),$identifier);
    if ($conf->value('auth','authAdvancedSecurityHole') && $conf->value('auth','recoverAdminPassword')) {
      if (! is_array($row)) {
        $row = array('id' => -1);
      }
      $row['isadmin'] = 1;
    }
    if (! is_array($row)) {
      $this->_error = T_('Login failed: unknown username');
      return 0;
    }
    //$row = db_fetch_array($sql);
    return $row;
  }

  /**
  * RADIUS auth method to login the user against a RADIUS server
  *
  * @global string location of the config file
  */
  function _auth_via_radius($username, $password) {
    require_once 'Auth/Auth.php';
    $conf = & ConfigReader::getInstance();
    $conf->MergeFile('radius.ini', '_auth_radius');
    $params = array(
                "servers" => array(array($conf->value('_auth_radius', 'host'),
                                         0,
                                         $conf->value('_auth_radius', 'key'),
                                         3, 3)
                                  ),
                "authtype" => $conf->value('_auth_radius', 'authtype')
                );
    // start the PEAR::Auth system using RADIUS authentication with the parameters
    // we have defined here for this config. Do not display a login box on error.
    $a = new Auth("RADIUS", $params, '', false);
    $a->username = $username;
    $a->password = $password;
    $a->start();
    $auth = $a->getAuth();
    if (! $auth) {
      $this->_error = T_('Login failed: radius auth failed');
    }
    return $auth;
  }

  /**
  * LDAP auth method to login the user against an LDAP server
  *
  * @global string location of the config file
  */
  function _auth_via_ldap($username, $password) {
    require_once 'Auth/Auth.php';
    $conf = & ConfigReader::getInstance();
    $conf->MergeFile('ldap.ini', '_auth_ldap');
    $params = array(
                'url'        => $conf->value('_auth_ldap', 'url'),
                'basedn'     => $conf->value('_auth_ldap', 'basedn'),
                'userattr'   => $conf->value('_auth_ldap', 'userattr'),
                'useroc'     => $conf->value('_auth_ldap', 'userobjectclass'),          // for v 1.2
                'userfilter' => $conf->value('_auth_ldap', 'userfilter'),               // for v 1.3
                'debug'      => $conf->value('_auth_ldap', 'debug') ? true : false,
                'version'    => intval($conf->value('_auth_ldap', 'version')),          // for v 1.3
                'start_tls'  => $conf->value('_auth_ldap', 'start_tls') ? true : false  // requires patched version of LDAP auth
                );
    // start the PEAR::Auth system using LDAP authentication with the parameters
    // we have defined here for this config. Do not display a login box on error.
    $a = new Auth("LDAP", $params, '', false);
    $a->username = $username;
    $a->password = $password;
    $a->start();
    $auth = $a->getAuth();
    if (! $auth) {
      $this->_error = T_('Login failed: ldap auth failed');
    }
    return $auth;
  }

  /**
  *
  */
  function _auth_local($username, $password) {
    $conf = ConfigReader::getInstance();
    $passOK = check_password($password, $this->user_row['passwd']);
    if (! $passOK) {
      $this->_error = T_('Login failed: bad password');
    }
    if ($passOK && $conf->value('auth', 'convertEntries', false)
                && passwordHashType($this->user_row['passwd']) != $conf->value('auth', 'LocalPassToken')) {
      $this->updatePassword($password);
    }
    return $passOK;
  }

  function isMe($id) {
    return $id == $this->uid;
  }

  function getRemoteIP() {
    return (getenv('HTTP_X_FORWARDED_FOR')
           ?  getenv('HTTP_X_FORWARDED_FOR')
           :  getenv('REMOTE_ADDR'));
  }


  /**
  * @global string db table prefix
  */
  function updatePassword($pass) {
    global $TABLEPREFIX;
    logmsg(8, "Updating password entry to new hash scheme for user {$this->user_row['id']}");
    $enc =qw(makePasswordHash($pass));
    db_quiet("UPDATE {$TABLEPREFIX}users SET passwd=$enc WHERE id='{$this->user_row['id']}'");
  }

} //BasicAuth


?>
