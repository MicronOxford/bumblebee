<?php
# $Id$
# Authorisation object

include_once ('dbforms/sql.php');
include_once ('dbforms/typeinfo.php');

class SystemAuth {
  var $uid,    //user id from table
      $username,
      $name,
      $isadmin,
      $euid,   //permit user masquerading like su. Effective UID
      $ename,  //effective name
      $eusername,  //effective username
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
      $this->_checkMasq();
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
  
  function _login() {
    #FIXME is it fair to assume alphabetic? how will we do non-radius users?
    if (! is_alphabetic($_POST['username']) || ! isset($_POST['pass']) ) {
      preDump($_POST);
      $this->_error = "Login failed: unknown username";
      return 0;
    }
    #then there is data provided to us in a login form
    # need to verify if it is valid login info
    $PASSWORD = $_POST['pass'];
    $USERNAME = $_POST['username'];
    $row = $this->_retrieveUserInfo($USERNAME);
    if ($row == '0') { 
      return 0;
    }
    if ($row['passwd'] == 'radius') {
      if (!$this->_auth_via_radius($USERNAME, $PASSWORD)) {
      $this->_error = "Login failed: radius auth failed";
        return 0;
      }
    } elseif ($row['passwd'] != md5($PASSWORD)) {
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
 
  function _retrieveUserInfo($identifier, $type=1) {
    $q = 'SELECT username,name,passwd,id,isadmin,suspended '
        .'FROM '.$this->table.' WHERE ';
    if ($type) {
      $q .= 'username='.qw($identifier);
    } else {
      $q .= 'id='.qw($identifier);
    }
    //$sql = mysql_query($q);
    //if (! $sql) die (mysql_error());
    $row = db_get_single($q, 1);
    //if (mysql_num_rows($sql) != 1) {
    if (! is_array($row)) {
      $this->_error = "Login failed: unknown username";
      return 0;
    }
    //$row = mysql_fetch_array($sql);
    return $row;
  }
  
  function _auth_via_radius($username, $password) {
    require_once "Auth/Auth.php";
    #FIXME: need to include these data in a config file somewhere
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
  
  function isSystemAdmin() {
    return $this->isadmin;
   }
  
  function isInstrumentAdmin($instr) {
     #FIXME: this should be rolled into a better permissions system
     return $this->isadmin;
   }
   
   function getEUID() {
     return (isset($this->euid) ? $this->euid : $this->uid);
   }
   
   function masqPermitted() {
     #FIXME: this should be rolled into a better permissions system
     return $this->isadmin;
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
   
} //SystemAuth
?> 
