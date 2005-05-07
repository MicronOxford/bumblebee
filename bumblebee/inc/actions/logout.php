<?php
# $Id$
# print out a login form

include_once 'inc/actions/actionaction.php';

class ActionLogout extends ActionAction {

  function ActionLogout($auth, $pdata) {
    parent::ActionAction($auth, $pdata);
  }

  function go() {
    global $BASEURL;
    //$this->auth->logout();
    echo "
      <h2>Successfully logged out</h2>
      <p>Thank you for using Bumblebee!</p>
      <p>(<a href='$BASEURL/'>login</a>)</p>
    ";
  }
}

?> 
