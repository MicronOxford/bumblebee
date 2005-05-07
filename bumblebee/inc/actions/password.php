<?php
# $Id$
# edit the user details, project associations and permissions

include_once 'inc/actions/actionaction.php';
include_once 'inc/bb/user.php';

class ActionPassword extends ActionAction {

  function ActionPassword($auth, $pdata) {
    parent::ActionAction($auth, $pdata);
    $this->mungePathData();
  }

  function go() {
    global $BASEURL;
    $this->editUser();
    echo "<br /><br /><a href='$BASEURL/'>Return to main menu</a>";
  }

  function editUser() {
    $user = new User($this->auth->uid, true);
    $user->update($this->PD);
    #$project->fields['defaultclass']->invalid = 1;
    $user->checkValid();
    echo $this->reportAction($user->sync(), 
          array(
              STATUS_OK =>   'Password changed successfully.',
              STATUS_ERR =>  'Password could not be changed: '.$user->errorMessage
          )
        );
    echo $user->display();
    echo "<input type='submit' name='submit' value='Change password' />";
  }
}

?> 
