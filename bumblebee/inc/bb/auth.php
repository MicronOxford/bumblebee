<?php
# $Id$
# Authorisation object

include_once ('inc/typeinfo.php');

class Auth {
  var $uid,
      $username,
      $name,
      $isadmin,
      $_loggedin=0,
      $_error;
  var $table;

  function Auth($table="users") {
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
    $epass = md5($_POST['pass']);
    #check that the pass is correct
    $USERNAME = $_POST['username'];
    $q = "SELECT passwd,id,isadmin,suspended "
        ."FROM ".$this->table." WHERE username='$USERNAME'";
    $sql = mysql_query($q);
    if (! $sql) die (mysql_error());
    if (mysql_num_rows($sql) != 1) {
      $this->_error = "Login failed: unknown username";
      return 0;
    }
    $row = mysql_fetch_array($sql);
    if ($epass != $row['passwd']) {
      $this->_error = "Login failed: bad password";
      return 0;
    }
    if ($row['suspended']) {
      $this->_error = "Login failed: this account is suspended, please contact us about this.";
      return 0;
    }
    # if we got to here, then we're logged in!
    $_SESSION['uid'] = $row['id'];
    $_SESSION['username'] = $row['username'];
    $_SESSION['name'] = $row['name'];
    $_SESSION['isadmin'] = $row['isadmin'];
    return 1;
  }
}
?> 
