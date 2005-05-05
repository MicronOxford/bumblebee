<?php
# $Id$
# edit the user details, project associations and permissions

include_once 'inc/user.php';
include_once 'inc/dbforms/anchortablelist.php';

class ActionUsers extends ActionAction {

  function ActionUsers($auth, $pdata) {
    parent::ActionAction($auth, $pdata);
    $this->mungePathData();
  }

  function go() {
    global $BASEURL;
    if (! isset($this->PD['id'])) {
      $this->selectUser();
    } elseif (isset($this->PD['delete'])) {
      $this->deleteUser();
    } else {
      $this->editUser();
    }
    echo "<br /><br /><a href='$BASEURL/users'>Return to user list</a>";
  }

  function selectUser() {
    global $BASEURL;
    $select = new AnchorTableList("Users", "Select which user to view");
    $select->connectDB("users", array("id", "name", "username"));
    $select->list->prepend(array("-1","Create new user"));
    $select->hrefbase = "$BASEURL/users/";
    $select->setFormat("id", "%s", array("name"), " %s", array("username"));
    echo $select->display();
  }

  function editUser() {
    $user = new User($this->PD['id']);
    $user->update($this->PD);
    #$project->fields['defaultclass']->invalid = 1;
    $user->checkValid();
    echo $this->reportAction($user->sync(), 
          array(
              STATUS_OK =>   ($this->PD['id'] < 0 ? 'User created' : 'User updated'),
              STATUS_ERR =>  'User could not be changed: '.$user->errorMessage
          )
        );
    echo $user->display();
    if ($user->id < 0) {
      $submit = "Create new user";
      $delete = "0";
    } else {
      $submit = "Update entry";
      $delete = "Delete entry";
    }
    echo "<input type='submit' name='submit' value='$submit' />";
    if ($delete) echo "<input type='submit' name='delete' value='$delete' />";
  }

  function deleteUser() {
    $user = new User($this->PD['id']);
    echo $this->reportAction($user->delete(), 
              array(
                  STATUS_OK =>   'User deleted',
                  STATUS_ERR =>  'User could not be deleted:<br/><br/>'.$user->errorMessage
              )
            );  
  }
}

?> 
