<?php
/**
* Edit/create/delete consumables
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Actions
*/

/** Consumable object */
require_once 'inc/bb/consumable.php';
/** list of choices */
require_once 'inc/formslib/anchortablelist.php';
/** parent object */
require_once 'inc/actions/actionaction.php';

/**
* Edit/create/delete consumables
* @package    Bumblebee
* @subpackage Actions
*/
class ActionConsumables extends ActionAction {

  /**
  * Initialising the class 
  * 
  * @param  BumblebeeAuth $auth  Authorisation object
  * @param  array $pdata   extra state data from the call path
  * @return void nothing
  */
  function ActionConsumables($auth, $PDATA) {
    parent::ActionAction($auth, $PDATA);
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
    echo "<br /><br /><a href='".makeURL('consumables')."'>"
              .T_('Return to consumables list')."</a>";
  }

  function select($deleted=false) {
    $select = new AnchorTableList(T_('Consumables'), T_('Select which Consumables to view'));
    $select->deleted = $deleted;
    $select->connectDB('consumables', array('id', 'name', 'longname'));
    $select->list->prepend(array('-1', T_('Create new consumable')));
    $select->list->append(array('showdeleted', T_('Show deleted consumables')));
    $select->hrefbase = makeURL('consumables', array('id'=>'__id__'));
    $select->setFormat('id', '%s', array('name'), ' %50.50s', array('longname'));
    echo $select->display();
  }

  function edit() {
    $consumable = new Consumable($this->PD['id']);
    $consumable->update($this->PD);
    $consumable->checkValid();
    echo $this->reportAction($consumable->sync(), 
          array(
              STATUS_OK =>   ($this->PD['id'] < 0 
                                ? T_('Consumable created') : T_('Consumable updated')),
              STATUS_ERR =>  T_('Consumable could not be changed:').' '.$consumable->errorMessage
          )
        );
    echo $consumable->display();
    if ($consumable->id < 0) {
      $submit = T_('Create new consumable');
      $delete = '0';
    } else {
      $submit = T_('Update entry');
      $delete = $consumable->isDeleted ? T_('Undelete entry') : T_('Delete entry');
    }
    echo "<input type='submit' name='submit' value='$submit' />";
    if ($delete) echo "<input type='submit' name='delete' value='$delete' />";
    echo '<p>'.sprintf(T_('<a href="%s">View listing</a> for selected consumable'), 
                makeURL('consume', array('consumableid'=>$consumable->id, 'list'=>1)))."</p>\n";
  }

  function delete() {
    $consumable = new Consumable($this->PD['id']);
    echo $this->reportAction($consumable->delete(), 
              array(
                  STATUS_OK =>   $consumable->isDeleted ? T_('Consumable undeleted') 
                                                        : T_('Consumable deleted'),
                  STATUS_ERR =>  T_('Consumable could not be deleted:')
                                 .'<br/><br/>'.$consumable->errorMessage
              )
            );  
  }
}

?> 
