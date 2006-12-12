<?php
/**
* Interface for editing details of groups
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Actions
*
* path (bumblebee root)/inc/actions/groups.php
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

/** Group object */
require_once 'inc/bb/group.php';
/** list of choices */
require_once 'inc/formslib/anchortablelist.php';
/** parent object */
require_once 'inc/actions/actionaction.php';

/**
* Interface for editing details of groups
*
* @package    Bumblebee
* @subpackage Actions
*/
class ActionGroups extends ActionAction  {

  /**
  * Initialising the class
  *
  * @param  BumblebeeAuth $auth  Authorisation object
  * @param  array $pdata   extra state data from the call path
  * @return void nothing
  */
  function ActionGroups($auth, $pdata) {
    parent::ActionAction($auth, $pdata);
    $this->mungeInputData();
  }

  function go() {
    if (! isset($this->PD['id'])) {
      $this->select(issetSet($this->PD, 'showdeleted', false));
    } elseif (isset($this->PD['delete'])) {
      $this->delete();
    } else {
      $this->edit();
    }
    echo "<br /><br /><a href='".makeURL('groups')."'>".T_('Return to group list')."</a>";
  }

  function select($deleted=false) {
    $select = new AnchorTableList(T_('Group'), T_('Select which group to view'));
    $select->deleted = $deleted;
    $select->connectDB('groups', array('id', 'name', 'longname'));
    $select->list->prepend(array('-1', T_('Create new group')));
    $select->list->append(array('showdeleted', T_('Show deleted groups')));
    $select->hrefbase = makeURL('groups', array('id'=>'__id__'));
    $select->setFormat('id', '%s', array('name'), ' %50.50s', array('longname'));
    #echo $select->list->text_dump();
    echo $select->display();
  }

  function edit() {
    $group = new Group($this->PD['id']);
    $group->update($this->PD);
    $group->checkValid();
    echo $this->reportAction($group->sync(),
          array(
              STATUS_OK =>   ($this->PD['id'] < 0 ? T_('Group created') : T_('Group updated')),
              STATUS_ERR =>  T_('Group could not be changed:').' '.$group->errorMessage
          )
        );
    echo $group->display();
    if ($group->id < 0) {
      $submit = T_('Create new group');
      $delete = '0';
    } else {
      $submit = T_('Update entry');
      $delete = $group->isDeleted ? T_('Undelete entry') : T_('Delete entry');
    }
    #$submit = ($PD['id'] < 0 ? "Create new" : "Update entry");
    echo "<input type='submit' name='submit' value='$submit' />";
    if ($delete) echo "<input type='submit' name='delete' value='$delete' />";
  }

  function delete() {
    $group = new Group($this->PD['id']);
    echo $this->reportAction($group->delete(),
              array(
                  STATUS_OK =>   $group->isDeleted ? T_('Group undeleted') : T_('Group deleted'),
                  STATUS_ERR =>  T_('Group could not be deleted:').'<br/><br/>'.$group->errorMessage
              )
            );
  }
}
?>
