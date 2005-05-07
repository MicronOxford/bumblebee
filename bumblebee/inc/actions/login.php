<?php
# $Id$
# print out a login form

include_once 'inc/actions/actionaction.php';

class ActionPrintLoginForm extends ActionAction {
  
  function ActionPrintLoginForm() {
  }

  function go() {
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

?> 
