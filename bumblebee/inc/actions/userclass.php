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
*/

include_once 'inc/bb/userclass.php';
include_once 'inc/formslib/anchortablelist.php';
include_once 'inc/actions/actionaction.php';

/**
* Edit/create/delete userclass details
*/
class ActionUserClass extends ActionAction  {

  function ActionUserClass($auth, $pdata) {
    parent::ActionAction($auth, $pdata);
    $this->mungePathData();
  }

  function go() {
    global $BASEURL;
    if (! isset($this->PD['id'])) {
      $this->select();
    } elseif (isset($this->PD['delete'])) {
      $this->delete();
    } else {
      $this->edit();
    }
    echo "<br /><br /><a href='$BASEURL/userclass/'>Return to user class list</a>";
  }

  function edit() {
    $class = new UserClass($this->PD['id']);
    $class->update($this->PD);
    $class->checkValid();
    echo $this->reportAction($class->sync(), 
          array(
              STATUS_OK =>   ($this->PD['id'] < 0 ? 'User class created' : 'User class updated'),
              STATUS_ERR =>  'User class could not be changed: '.$class->errorMessage
          )
        );
        echo $class->display();
    if ($class->id < 0) {
      $submit = 'Create new class';
      $delete = '0';
    } else {
      $submit = 'Update entry';
      $delete = 'Delete entry';
    }
    #$submit = ($PD['id'] < 0 ? "Create new" : "Update entry");
    echo "<input type='submit' name='submit' value='$submit' />";
    if ($delete) echo "<input type='submit' name='delete' value='$delete' />";
  }

  function select() {
    global $BASEURL;
    $select = new AnchorTableList('UserClass', 'Select which user class to view');
    $select->connectDB('userclass', array('id', 'name'));
    $select->list->prepend(array('-1','Create new user class'));
    $select->hrefbase = $BASEURL.'/userclass/';
    $select->setFormat('id', '%s', array('name')/*, ' %50.50s', array('longname')*/);
    #echo $groupselect->list->text_dump();
    $select->numcols = 1;
    echo $select->display();
  }

  function delete() {
    $class = new UserClass($this->PD['id']);
    echo $this->reportAction($class->delete(), 
              array(
                  STATUS_OK =>   'User class deleted',
                  STATUS_ERR =>  'User class could not be deleted:<br/><br/>'.$class->errorMessage
              )
            );  
  }
}
?> 
