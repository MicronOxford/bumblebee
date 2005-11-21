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

include_once 'inc/bb/instrument.php';
include_once 'inc/formslib/anchortablelist.php';
include_once 'inc/actions/actionaction.php';

/**
* Edit/create/delete instruments
*/
class ActionInstruments extends ActionAction {

  /**
  * Initialising the class 
  * 
  * @param  BumbleBeeAuth $auth  Authorisation object
  * @param  array $pdata   extra state data from the call path
  * @return void nothing
  */
  function ActionInstruments($auth, $pdata) {
    parent::ActionAction($auth, $pdata);
    $this->mungePathData();
  }

  function go() {
    global $BASEURL;
    if (! isset($this->PD['id'])) {
      $this->select(issetSet($this->PD, 'showdeleted', false));
    } elseif (isset($this->PD['delete'])) {
      $this->delete();
    } else {
      $this->edit();
    }
    echo "<br /><br /><a href='$BASEURL/instruments'>Return to instruments list</a>";
  }

  function select($deleted=false) {
    global $BASEURL;
    $select = new AnchorTableList('Instrument', 'Select which instrument to view');
    $select->deleted = $deleted;
    $select->connectDB('instruments', array('id', 'name', 'longname'));
    $select->list->prepend(array('-1','Create new instrument'));
    $select->list->append(array('showdeleted','Show deleted instruments'));
    $select->hrefbase = $BASEURL.'/instruments/';
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
              STATUS_OK =>   ($this->PD['id'] < 0 ? 'Instrument created' : 'Instrument updated'),
              STATUS_ERR =>  'Instrument could not be changed: '.$instrument->errorMessage
          )
        );
    echo $instrument->display();
    if ($instrument->id < 0) {
      $submit = 'Create new instrument';
      $delete = '0';
    } else {
      $submit = 'Update entry';
      $delete = $instrument->isDeleted ? 'Undelete entry' : 'Delete entry';
    }
    #$submit = ($PD['id'] < 0 ? "Create new" : "Update entry");
    echo "<input type='submit' name='submit' value='$submit' />";
    if ($delete) echo "<input type='submit' name='delete' value='$delete' />";
  }

  function delete()   {
    $instrument = new Instrument($this->PD['id']);
    echo $this->reportAction($instrument->delete(), 
              array(
                  STATUS_OK =>   $instrument->isDeleted ? 'Instrument undeleted' : 'Instrument deleted',
                  STATUS_ERR =>  'Instrument could not be deleted:<br/><br/>'.$instrument->errorMessage
              )
            );  
  }
}
?> 
