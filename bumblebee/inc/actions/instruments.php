<?php
/**
* Edit/create/delete instruments
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Actions
*/

/** instrument object */
require_once 'inc/bb/instrument.php';
/** list of choices */
require_once 'inc/formslib/anchortablelist.php';
/** parent object */
require_once 'inc/actions/actionaction.php';

/**
* Edit/create/delete instruments
* @package    Bumblebee
* @subpackage Actions
*/
class ActionInstruments extends ActionAction {

  /**
  * Initialising the class 
  * 
  * @param  BumblebeeAuth $auth  Authorisation object
  * @param  array $pdata   extra state data from the call path
  * @return void nothing
  */
  function ActionInstruments($auth, $pdata) {
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
    echo "<br /><br /><a href='".makeURL('instruments')."'>".T_('Return to instruments list')."</a>";
  }

  function select($deleted=false) {
    $select = new AnchorTableList('Instrument', T_('Select which instrument to view'));
    $select->deleted = $deleted;
    $select->connectDB('instruments', array('id', 'name', 'longname'));
    $select->list->prepend(array('-1', T_('Create new instrument')));
    $select->list->append(array('showdeleted', T_('Show deleted instruments')));
    $select->hrefbase = makeURL('instruments', array('id'=>'__id__'));
    $select->setFormat('id', '%s', array('name'), ' %30.30s', array('longname'));
    #echo $groupselect->list->text_dump();
    echo $select->display();
  }

  function edit() {
    $instrument = new Instrument($this->PD['id']);
    $instrument->update($this->PD);
    $instrument->checkValid();
    echo $this->reportAction($instrument->sync(), 
          array(
              STATUS_OK =>   ($this->PD['id'] < 0 ? T_('Instrument created') 
                                                  : T_('Instrument updated')),
              STATUS_ERR =>  T_('Instrument could not be changed:').' '.$instrument->errorMessage
          )
        );
    echo $instrument->display();
    if ($instrument->id < 0) {
      $submit = T_('Create new instrument');
      $delete = '0';
    } else {
      $submit = T_('Update entry');
      $delete = $instrument->isDeleted ? T_('Undelete entry') : T_('Delete entry');
    }
    #$submit = ($PD['id'] < 0 ? "Create new" : "Update entry");
    echo "<input type='submit' name='submit' value='$submit' />";
    if ($delete) echo "<input type='submit' name='delete' value='$delete' />";
  }

  function delete()   {
    $instrument = new Instrument($this->PD['id']);
    echo $this->reportAction($instrument->delete(), 
              array(
                  STATUS_OK =>   $instrument->isDeleted ? T_('Instrument undeleted') 
                                                        : T_('Instrument deleted'),
                  STATUS_ERR =>  T_('Instrument could not be deleted:')
                                 .'<br/><br/>'.$instrument->errorMessage
              )
            );  
  }
}
?> 
