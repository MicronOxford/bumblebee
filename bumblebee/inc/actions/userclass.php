<?php
/**
* Edit/create/delete userclass details
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Actions
*
* path (bumblebee root)/inc/actions/userclass.php
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

/** userclass object */
require_once 'inc/bb/userclass.php';
/** list of choices */
require_once 'inc/formslib/anchortablelist.php';
/** parent object */
require_once 'inc/actions/actionaction.php';

/**
* Edit/create/delete userclass details
* @package    Bumblebee
* @subpackage Actions
*/
class ActionUserClass extends ActionAction  {

  /**
  * Initialising the class
  *
  * @param  BumblebeeAuth $auth  Authorisation object
  * @param  array $pdata   extra state data from the call path
  * @return void nothing
  */
  function ActionUserClass($auth, $pdata) {
    parent::ActionAction($auth, $pdata);
    $this->mungeInputData();
  }

  function go() {
    if (! isset($this->PD['id'])) {
      $this->select();
    } elseif (isset($this->PD['delete'])) {
      if ($this->readOnly) {
        $this->readOnlyError();
      } else {
        $this->delete();
      }
    } else {
      if ($this->readOnly) $this->_dataCleanse('id');
      $this->edit();
    }
    echo "<br /><br /><a href='".makeURL('userclass')."'>".T_('Return to user class list')."</a>";
  }

  function edit() {
    $class = new UserClass($this->PD['id']);
    $class->update($this->PD);
    $class->checkValid();
    echo $this->reportAction($class->sync(),
          array(
              STATUS_OK =>   ($this->PD['id'] < 0 ? T_('User class created')
                                                  : T_('User class updated')),
              STATUS_ERR =>  T_('User class could not be changed:').' '.$class->errorMessage
          )
        );
        echo $class->display();
    if ($class->id < 0) {
      $submit = T_('Create new class');
      $delete = '0';
    } else {
      $submit = T_('Update entry');
      $delete = T_('Delete entry');
    }
    #$submit = ($PD['id'] < 0 ? "Create new" : "Update entry");
    echo "<input type='submit' name='submit' value='$submit' />";
    if ($delete) echo "<input type='submit' name='delete' value='$delete' />";
  }

  /**
  * @param $deleted  (unused)
  */
  function select($deleted=false) {
    $select = new AnchorTableList(T_('UserClass'), T_('Select which user class to view'));
    $select->connectDB('userclass', array('id', 'name'));
    $select->list->prepend(array('-1','Create new user class'));
    $select->hrefbase = makeURL('userclass', array('id'=>'__id__'));
    $select->setFormat('id', '%s', array('name')/*, ' %50.50s', array('longname')*/);
    #echo $groupselect->list->text_dump();
    $select->numcols = 1;
    echo $select->display();
  }

  function delete() {
    $class = new UserClass($this->PD['id']);
    echo $this->reportAction($class->delete(),
              array(
                  STATUS_OK =>   T_('User class deleted'),
                  STATUS_ERR =>  T_('User class could not be deleted:')
                                 .'<br/><br/>'.$class->errorMessage
              )
            );
  }
}
?>
