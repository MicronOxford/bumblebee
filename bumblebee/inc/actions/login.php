<?php
# $Id$
# print out a login form

function printLoginForm() {
?>
  <table>
  <tr>
    <td>username:</td>
    <td><input name="username" type="text" size="16" /></td>
  </tr>
  <tr>
    <td>password:</td>
    <td><input name="pass" type="password" size="16" /></td>
  </tr>
  <tr>
    <td></td>
    <td><input name="submit" type="submit" value="login" /></td>
  </tr>
  </table>
<?
}

function actionLogout() {
  #logout();
?>
  <h2>Successfully logged out</h2>
  <p>Thank you for using BABS!</p>
<?
}

function isLoggedIn() {
  #return false if we require a login
  #if credentials are OK then return true
  global $ERRORMSG;
  global $USERNAME, $UID, $EPASS, $ISADMIN;
  # first, we need to determine if we are actually logged in or not
  # if we are not logged in, the the action *has* to be 'login'

  if (isset($_COOKIE['auth'])) {
    #then there is some login info there, let's test it out
    preg_match("/(\d+)-(.+)/", $_COOKIE['auth'], $p);
    $uid   = $p[1];
    $epass = $p[2];
    #echo "Munched cookie: '$uid', '$epass' :".$_COOKIE['auth'];
    #if (! is_int($uid)) {
    if (intval($uid)!=$uid) {
      $ERRORMSG = "Bad login: non-numeric userid ($uid)";
      killLoginCookie();
      return 0;
    }
    #basic sanity checks ok; let's now check that the auth is valid
    $q = "SELECT username,passwd,isadmin,suspended "
        ."FROM users WHERE id='$uid'";
    $sql = mysql_query($q);
    if (! $sql) die (mysql_error());
    if (mysql_num_rows($sql) != 1) {
      $ERRORMSG = "Bad login: unknown userid";
      killLoginCookie();
      return 0;
    }
    $row = mysql_fetch_array($sql);
    if ($epass != $row['passwd']) {
      $ERRORMSG = "Bad login: bad password";
      killLoginCookie();
      return 0;
    }
    if ($row['suspended']) {
      $ERRORMSG = "This account is suspended, please contact us about this.";
      killLoginCookie();
      return 0;
    }
    # if we got to here, then we're logged in!
    $UID = $uid;
    $EPASS = $epass;
    $ISADMIN = $row['isadmin'];
    $USERNAME = $row['username'];
    #let's renew the validity on the cookie to make sure that we don't get
    #logged out
    setLoginCookie();
    return 1;
  } elseif (isset($_POST['username']) && is_alphabetic($_POST['username'])
             && isset($_POST['pass']) ) {
    #then there is data provided to us in a login form
    # need to verify if it is valid login info
    $epass = md5($_POST['pass']);
    #check that the pass is correct
    $USERNAME = $_POST['username'];
    $q = "SELECT passwd,id,isadmin,suspended "
        ."FROM users WHERE username='$USERNAME'";
    $sql = mysql_query($q);
    if (! $sql) die (mysql_error());
    if (mysql_num_rows($sql) != 1) {
      $ERRORMSG = "Bad login: unknown username";
      return 0;
    }
    $row = mysql_fetch_array($sql);
    if ($epass != $row['passwd']) {
      $ERRORMSG = "Bad login: bad password";
      return 0;
    }
    if ($row['suspended']) {
      $ERRORMSG = "This account is suspended, please contact us about this.";
      return 0;
    }
    # if we got to here, then we're logged in!
    $UID = $row['id'];
    $EPASS = $epass;
    $ISADMIN = $row['isadmin'];
    # set a login cookie to do this next time
    setLoginCookie();
    return 1;
  } else {
    # no login info. require login
    return 0;
  }
}

function logout() {
  #set the cookie to null value AND set the expiry time to one hour ago
  #to force the cookie to be invalidated for our purposes and cleared
  #from the browser's cache
  setcookie("auth", "", time()-3600, "/");
}

function setLoginCookie() {
  global $USERNAME, $UID, $EPASS, $ISADMIN;
  # -- expire time is not set, cookie is lost on browser close
  #echo "Setting $UID-$EPASS cookie";
  setcookie("auth", "$UID-$EPASS", time()+7200, "/");
}

function killLoginCookie() {
  #echo "Killing cookie";
  logout();
}
?> 
