<?php
# $Id$
# edit the user details, project associations and permissions

include_once 'inc/user.php';
include_once 'inc/dbforms/anchortablelist.php';


  function actionUsers() {
    global $BASEURL;
    $PD = userMungePathData();
    if (! isset($PD['id'])) {
      selectUser();
    } elseif (isset($PD['delete'])) {
      deleteUser($PD['id']);
    } else {
      editUser($PD);
    }
    echo "<br /><br /><a href='$BASEURL/projects'>Return to user list</a>";
  }

  function userMungePathData() {
    global $PDATA;
    $PD = array();
    foreach ($_POST as $k => $v) {
      $PD[$k] = $v;
    }
    if (isset($PDATA[1])) {
      $PD['id'] = $PDATA[1];
    }
    #echo "<pre>".print_r($PD,true)."</pre>";
    return $PD;
  }

  function selectUser() {
    global $BASEURL;
    $projectselect = new AnchorTableList("Users", "Select which user to view");
    $projectselect->connectDB("user", array("id", "name", "longname"));
    $projectselect->list->prepend(array("-1","Create new user"));
    $projectselect->hrefbase = "$BASEURL/users/";
    $projectselect->setFormat("id", "%s", array("name"), " %s", array("longname"));
    echo $projectselect->display();
  }

  function editUser($PD) {
    $project = new User($PD['id']);
    $project->update($PD);
    #$project->fields['defaultclass']->invalid = 1;
    $project->sync();
    echo $project->display();
    if ($project->id < 0) {
      $submit = "Create new user";
      $delete = "0";
    } else {
      $submit = "Update entry";
      $delete = "Delete entry";
    }
    echo "<input type='submit' name='submit' value='$submit' />";
    if ($delete) echo "<input type='submit' name='delete' value='$delete' />";
  }

  function deleteUser($gpid) {
    $q = "DELETE FROM users WHERE id='$gpid'";
    db_quiet($q, 1);
  }

?> 
