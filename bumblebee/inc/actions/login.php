<?php
# $Id$
# print out a login form

include_once 'actionaction.php';

class ActionPrintLoginForm extends ActionAction {
  
  function ActionPrintLoginForm() {
    echo '
      <h2>Login required</h2>
      <p>Please login to view or book instrument usage</p>
      <table>
      <tr>
        <td>Username:</td>
        <td><input name="username" type="text" size="16" /></td>
      </tr>
      <tr>
        <td>Password:</td>
        <td><input name="pass" type="password" size="16" /></td>
      </tr>
      <tr>
        <td></td>
        <td><input name="submit" type="submit" value="login" /></td>
      </tr>
      </table>
    ';
  }
}

class ActionLogout extends ActionAction {

  function ActionLogout() {
    global $BASEURL;
    #logout();
    echo "
      <h2>Successfully logged out</h2>
      <p>Thank you for using BABS!</p>
      <p>(<a href='$BASEURL/'>login</a>)</p>
    ";
  }
}

?> 
