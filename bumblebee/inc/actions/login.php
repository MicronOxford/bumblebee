<?php
# print out a login form

function printLoginForm()
{
echo <<<END
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
  <input name="action" type="hidden" value="main" />
  </table>

END;
}

function isLoggedIn()
{
  #return false if we require a login
  #if credentials are OK then return true
  global $actiontitle, $ERRORMSG;
  global $USERNAME, $UID, $EPASS, $ISADMIN;
  # first, we need to determine if we are actually logged in or not
  # if we are not logged in, the the action *has* to be 'login'

  if ( #(     (isset($_POST['uid']) && is_numeric($_POST['uid'])) ||
         (isset($_POST['username']) && is_alphabetic($_POST['username']))
       #)
       && (isset($_POST['pass']) || isset($_POST['epass']))
     )
  {
    # then there is at least login info present
    # need to verify if it is valid login info
    $encpass = isset($_POST['epass']) ?  $_POST['epass'] : md5($_POST['pass']);
    #check that the pass is correct
    $q = "SELECT passwd,id,isadmin,suspended "
        ."FROM users WHERE username='".$_POST['username']."'";
    $sql = mysql_query($q);
    if (! $sql) die (mysql_error());
    if (mysql_num_rows($sql) != 1)
    {
      $ERRORMSG = "Bad login: unknown username";
      $actiontitle = "Login";
      return 0;
    }
    $row = mysql_fetch_row($sql);
    if ($encpass != $row[0])
    {
      $ERRORMSG = "Bad login: bad password";
      $actiontitle = "Login";
      return 0;
    }
    if ($row[3])
    {
      $ERRORMSG = "This account is suspended, please contact us about this.";
      $actiontitle = "Login";
      return 0;
    }
    # if we got to here, then we're logged in!
    $UID = $row[1];
    $EPASS = $row[0];
    $ISADMIN = $row[2];
    $USERNAME = $_POST['username'];
  }
  else
  {
    # no login info. require login
    $actiontitle = "Login";
    return 0;
  }
  return 1;
}
?> 
