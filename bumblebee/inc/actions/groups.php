<?php
# $Id$
# edit the groups

include_once 'inc/group.php';
include_once 'inc/anchortablelist.php';

  function actionGroup() {
    global $BASEURL;
    $PD = groupMungePathData();
    if (! isset($PD['id'])) {
      selectgroup();
    } elseif (isset($PD['delete'])) {
      deleteGroup($PD['id']);
    } else {
      editGroup($PD);
    }
    echo "<br /><br /><a href='$BASEURL/groups'>Return to group list</a>";
  }

  function groupMungePathData() {
    global $PDATA;
    $PD = array();
    foreach ($_POST as $k => $v) {
      $PD[$k] = $v;
    }
    if (isset($PDATA[1])) {
      $PD['id'] = $PDATA[1];
    }
    return $PD;
  }

  function editGroup($PD) {
    $group = new Group($PD['id']);
    $group->update($PD);
    $group->sync();
    #echo $group->text_dump();
    echo $group->display();
    if ($group->id < 0) {
      $submit = "Create new group";
      $delete = "0";
    } else {
      $submit = "Update entry";
      $delete = "Delete entry";
    }
    #$submit = ($PD['id'] < 0 ? "Create new" : "Update entry");
    echo "<input type='submit' name='submit' value='$submit' />";
    if ($delete) echo "<input type='submit' name='delete' value='$delete' />";
  }

  function selectgroup() {
    global $BASEURL;
    $groupselect = new AnchorTableList("Group", "Select which group to view");
    $groupselect->connectDB("groups", array("id", "name", "longname"));
    $groupselect->list->prepend(array("-1","Create new group"));
    $groupselect->hrefbase = "$BASEURL/groups/";
    $groupselect->setFormat("id", "%s"," %s", array("name"), array("longname"));
    #echo $groupselect->list->text_dump();
    echo $groupselect->display();
  }

  function deletegroup($gpid)
  {
    $q = "DELETE FROM groups WHERE id='$gpid'";
    db_quiet($q,1);
  }

?> 
