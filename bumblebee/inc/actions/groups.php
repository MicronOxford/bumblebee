<?php
# $Id$
# edit the groups

include_once 'inc/group.php';
include_once 'inc/dbforms/anchortablelist.php';

class ActionGroup extends ActionAction  {

  function ActionGroup($auth, $pdata) {
    parent::ActionAction($auth, $pdata);
    $this->mungePathData();
  }

  function go() {
    global $BASEURL;
    if (! isset($this->PD['id'])) {
      $this->selectgroup();
    } elseif (isset($this->PD['delete'])) {
      $this->deleteGroup();
    } else {
      $this->editGroup();
    }
    echo "<br /><br /><a href='$BASEURL/groups'>Return to group list</a>";
  }

  function editGroup() {
    $group = new Group($this->PD['id']);
    $group->update($this->PD);
    $group->checkValid();
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
    $groupselect->setFormat("id", "%s", array("name"), " %s", array("longname"));
    #echo $groupselect->list->text_dump();
    echo $groupselect->display();
  }

  function deletegroup()
  {
    $group = new Group($this->PD['id']);
    $group->delete();

    #$q = "DELETE FROM groups WHERE id='$gpid'";
    #db_quiet($q,1);
  }
}
?> 
