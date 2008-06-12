<?php
/**
* Edit/create/delete users, their project associations and permissions
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Actions
*
* path (bumblebee root)/inc/actions/users.php
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

/** User object */
require_once 'inc/bb/user.php';
/** list of choices */
require_once 'inc/formslib/anchortablelist.php';
/** parent object */
require_once 'inc/actions/actionaction.php';

/**
* Edit/create/delete users, their project associations and permissions
* @package    Bumblebee
* @subpackage Actions
*/
class ActionUsers extends ActionAction {

  /**
  * Initialising the class
  *
  * @param  BumblebeeAuth $auth  Authorisation object
  * @param  array $pdata   extra state data from the call path
  * @return void nothing
  */
  function ActionUsers($auth, $pdata) {
    parent::ActionAction($auth, $pdata);
    $this->mungeInputData();
  }

  function go() {
    if (! isset($this->PD['edituserid'])) {
      $this->select(issetSet($this->PD, 'showdeleted', false));
    } elseif (isset($this->PD['delete'])) {
      if ($this->readOnly) {
        $this->readOnlyError();
      } else {
        $this->delete();
      }
    } else {
      if ($this->readOnly) $this->_dataCleanse('edituserid');
      $this->edit();
    }
    echo "<br /><br /><a href='".makeURL('users')."'>".T_('Return to user list')."</a>";
  }

  function select($deleted=false) {
    $select = new AnchorTableList(T_('Users'), T_('Select which user to view'));
    $select->deleted = $deleted;
    $select->connectDB('users', array('id', 'name', 'username'));
    $select->list->prepend(array('-1', T_('Create new user')));
    $select->list->append(array('showdeleted', T_('Show deleted users')));
    $select->hrefbase = makeURL('users', array('edituserid'=>'__id__'));
    $select->setFormat('id', '%s', array('name'), ' %s', array('username'));
    echo $select->display();
  }

  function edit() {
    $user = new User($this->auth, $this->PD['edituserid']);
    // add a formname to the user object to prevent autocomplete getting to happy with "username" and "password" fields
    $user->setFormName('edituser');
    $user->update($this->PD);
    #$project->fields['defaultclass']->invalid = 1;
    $user->checkValid();
    echo $this->reportAction($user->sync(),
          array(
              STATUS_OK =>   ($this->PD['edituserid'] < 0 ? T_('User created') : T_('User updated')),
              STATUS_ERR =>  T_('User could not be changed:').' '.$user->errorMessage
          )
        );
    echo $user->display();
    if ($user->id < 0) {
      $submit = T_('Create new user');
      $delete = '0';
    } else {
      $submit = T_('Update entry');
      $delete = $user->isDeleted ? T_('Undelete entry') : T_('Delete entry');
    }
    echo "<input type='submit' name='submit' value='$submit' />";
    if ($delete) echo "<input type='submit' name='delete' value='$delete' />";
  }

  function delete() {
    $user = new User($this->auth, $this->PD['edituserid']);
    echo $this->reportAction($user->delete(),
              array(
                  STATUS_OK =>   $user->isDeleted ? T_('User undeleted') : T_('User deleted'),
                  STATUS_ERR =>  T_('User could not be deleted:').'<br/><br/>'.$user->errorMessage
              )
            );
  }
}

?>
