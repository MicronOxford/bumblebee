<?php
# $Id$
# Authorisation object

include_once ('dbforms/typeinfo.php');

class SystemAuth {
  var $uid,
      $username,
      $name,
      $isadmin,
      $_loggedin=0,
      $_error;
  var $table;
  var $DEBUGMODE = 1;

  function SystemAuth($table="users") {
    session_start();
    $this->table = $table;
    if (isset($_SESSION['uid'])) {
      #the we have a session login already done, check it
      $this->_loggedin = $this->_verifyLogin();
    } elseif (isset($_POST['username'])) {
      #then some login info has been provided, so we need to check it
      $this->_loggedin = $this->_login();
    } else {
      #we're not logged in at all
    }
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

  function _verifyLogin() {
    #check that the credentials contained in the session are OK
    ## Actually, we just assume that the credentials are OK and load them
    ## into this object
    $this->uid = $_SESSION['uid'];
    $this->username = $_SESSION['username'];
    $this->name = $_SESSION['name'];
    ## FIXME Should we double check this?
    $this->isadmin = $_SESSION['isadmin'];
    return 1;
  }

  function _login() {
    #FIXME is it fair to assume alphabetic? how will we do non-radius users?
    if (! is_alphabetic($_POST['username']) || ! isset($_POST['pass']) ) {
        $this->_error = "Login failed: unknown username";
        return 0;
    }
    #then there is data provided to us in a login form
    # need to verify if it is valid login info
    $PASSWORD = $_POST['pass'];
    $USERNAME = $_POST['username'];
    $epass = md5($PASSWORD);
    $q = "SELECT username,name,passwd,id,isadmin,suspended "
        ."FROM ".$this->table." WHERE username='$USERNAME'";
    $sql = mysql_query($q);
    if (! $sql) die (mysql_error());
    if (mysql_num_rows($sql) != 1) {
      $this->_error = "Login failed: unknown username";
      return 0;
    }
    $row = mysql_fetch_array($sql);
    if ($row['passwd'] == 'radius') {
      if (!$this->_auth_via_radius($USERNAME, $PASSWORD)) {
      $this->_error = "Login failed: radius auth failed";
        return 0;
      }
    } elseif ($row['passwd'] != $epass) {
      $this->_error = "Login failed: bad password";
      return 0;
    }
    if ($row['suspended']) {
      $this->_error = "Login failed: this account is suspended, please contact us about this.";
      return 0;
    }
    # if we got to here, then we're logged in!
    $_SESSION['uid'] = $this->uid = $row['id'];
    $_SESSION['username'] = $this->username = $row['username'];
    $_SESSION['name'] = $this->name = $row['name'];
    $_SESSION['isadmin'] = $this->isadmin = $row['isadmin'];
    return 1;
  }
 
  function _auth_via_radius($username, $password) {
    require_once "Auth/Auth.php";
    $params = array(
                "servers" => array(array("localhost", 0, "secretKey", 3, 3)),
                "authtype" => "PAP"
                );
    $a = new Auth("RADIUS", $params);
    $a->username = $username;
    $a->password = $password;
    $a->start();
    return ($a->getAuth());
  }

} //SystemAuth
?> 
