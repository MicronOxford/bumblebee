<?php
# $Id$
# edit the groups

include_once 'inc/bb/group.php';
include_once 'inc/formslib/anchortablelist.php';
include_once 'inc/actions/actionaction.php';

class ActionGroup extends ActionAction  {

  function ActionGroup($auth, $pdata) {
    parent::ActionAction($auth, $pdata);
    $this->mungePathData();
  }

  function go() {
    global $BASEURL;
    if (! isset($this->PD['id'])) {
      $this->selectgroup(issetSet($this->PD, 'showdeleted', false));
    } elseif (isset($this->PD['delete'])) {
      $this->deleteGroup();
    } else {
      $this->editGroup();
    }
    echo "<br /><br /><a href='$BASEURL/groups'>Return to group list</a>";
  }

  function selectgroup($deleted=false) {
    global $BASEURL;
    $select = new AnchorTableList('Group', 'Select which group to view');
    $select->deleted = $deleted;
    $select->connectDB('groups', array('id', 'name', 'longname'));
    $select->list->prepend(array('-1','Create new group'));
    $select->list->append(array('showdeleted','Show deleted groups'));
    $select->hrefbase = $BASEURL.'/groups/';
    $select->setFormat('id', '%s', array('name'), ' %50.50s', array('longname'));
    #echo $select->list->text_dump();
    echo $select->display();
  }
  
  function editGroup() {
    $group = new Group($this->PD['id']);
    $group->update($this->PD);
    $group->checkValid();
    echo $this->reportAction($group->sync(), 
          array(
              STATUS_OK =>   ($this->PD['id'] < 0 ? 'Group created' : 'Group updated'),
              STATUS_ERR =>  'Group could not be changed: '.$group->errorMessage
          )
        );
    echo $group->display();
    if ($group->id < 0) {
      $submit = 'Create new group';
      $delete = '0';
    } else {
      $submit = 'Update entry';
      $delete = $group->isDeleted ? 'Undelete entry' : 'Delete entry';
    }
    #$submit = ($PD['id'] < 0 ? "Create new" : "Update entry");
    echo "<input type='submit' name='submit' value='$submit' />";
    if ($delete) echo "<input type='submit' name='delete' value='$delete' />";
  }

  function deletegroup() {
    $group = new Group($this->PD['id']);
    echo $this->reportAction($group->delete(), 
              array(
                  STATUS_OK =>   $group->isDeleted ? 'Group undeleted' : 'Group deleted',
                  STATUS_ERR =>  'Group could not be deleted:<br/><br/>'.$group->errorMessage
              )
            );  
  }
}
?> 
