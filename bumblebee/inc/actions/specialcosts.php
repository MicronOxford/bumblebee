<?php
/**
* Edit/create/delete special instrument usage costs 
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Actions
*/
  
include_once 'inc/bb/specialcosts.php';
include_once 'inc/formslib/anchortablelist.php';
include_once 'inc/actions/actionaction.php';

/**
* Edit/create/delete special instrument usage costs 
*
*/
class ActionSpecialCosts extends ActionAction {

  /**
  * Initialising the class 
  * 
  * @param  BumbleBeeAuth $auth  Authorisation object
  * @param  array $pdata   extra state data from the call path
  * @return void nothing
  */
  function ActionSpecialCosts($auth, $pdata) {
    parent::ActionAction($auth, $pdata);
    $this->mungePathData();
  }

  function go() {
    global $BASEURL;
    if (! isset($this->PD['project'])) {
      if ($this->PD['createnew']) {
        $this->selectProjectCreate();
      } else {
        $this->selectProject();
      }
    } elseif (! isset($this->PD['instrument'])) {
      if ($this->PD['createnew']) {
        $this->selectInstrumentCreate();
      } else {
        $this->selectInstrument();
      }
    } elseif (isset($this->PD['delete']) && $this->PD['delete'] == 1) {
      $this->delete();
    } else {
      $this->edit();
    }
    echo "<br /><br /><a href='$BASEURL/specialcosts/'>Return to special costs list</a>";
  }
  
  function mungePathData() {
    $this->PD = array();
    foreach ($_POST as $k => $v) {
      $this->PD[$k] = $v;
    }
    $this->PD['createnew'] = 0;
    if (isset($this->PDATA[1]) && ($this->PDATA[1] == -1)) {
      $this->PD['createnew'] = 1;
      array_shift($this->PDATA);
    }
    if (isset($this->PDATA[1]) && ! empty($this->PDATA[1]) && is_numeric($this->PDATA[1])) {
      $this->PD['project'] = $this->PDATA[1];
    }
    if (isset($this->PDATA[2]) && ! empty($this->PDATA[2]) && is_numeric($this->PDATA[2])) {
      if ($this->PDATA[2] == -1) {
        $this->PD['createnew'] = 1;
      } else {
        $this->PD['instrument'] = $this->PDATA[2];
      }
    }
    if (isset($this->PD['delete']) && !empty($this->PD['delete'])) {
      $this->PD['delete'] = 1;
    }
    echoData($this->PD, 0);
  }

  /**
  * Select for which project the special costs should be displayed
  *
  */
  function selectProject() {
    global $BASEURL;
    $select = new AnchorTableList('Projects', 'Select project to rates to view');
    $select->connectDB('projects', array('id', 'name', 'longname'),
                            'projectid IS NOT NULL',
                            'name', 
                            'id', 
                            NULL, 
                            array('projectrates'=>'projectrates.projectid=projects.id'), true);
    $select->list->prepend(array('-1','Create new project rate'));
    $select->hrefbase = "$BASEURL/specialcosts/";
    $select->setFormat('id', '%s', array('name'), '%50.50s', array('longname'));
    echo $select->display();
  }

  /**
  * Select for which project a special cost should be created
  *
  */
  function selectProjectCreate() {
    global $BASEURL;
    $select = new AnchorTableList('Projects', 'Select project to create rate');
    $select->connectDB('projects', array('id', 'name', 'longname'));
    $select->hrefbase = "$BASEURL/specialcosts/-1/";
    $select->setFormat('id', '%s', array('name'), '%50.50s', array('longname'));
    echo $select->display();
  }

  /**
  * Select for which instrument the special costs should be displayed
  *
  */
  function selectInstrument() {
    global $BASEURL;
    $select = new AnchorTableList('Instruments', 'Select instrument to view rates');
    $select->connectDB('instruments', array('id', 'name', 'longname'),
                            'projectid='.qw($this->PD['project']),
                            'name', 
                            'id', 
                            NULL, 
                            array('projectrates'=>'projectrates.instrid=instruments.id'), true);
    $select->list->prepend(array('-1','Create new project rate'));
    $select->hrefbase = $BASEURL.'/specialcosts/'
                           .($this->PD['createnew'] ? '-1/' : '')
                           .$this->PD['project'].'/';
    $select->setFormat('id', '%s', array('name'), '%50.50s', array('longname'));
    echo $select->display();
  }

  /**
  * Select for which instrument a special cost should be created
  *
  */
  function selectInstrumentCreate() {
    global $BASEURL;
    $select = new AnchorTableList('Instruments', 'Select instrument to create rate');
    $select->connectDB('instruments', array('id', 'name', 'longname'),
                            'projectid IS NULL',        //find rows *not* in the join
                            'name', 
                            'id', 
                            NULL, 
                            array('projectrates'=>'projectrates.instrid=instruments.id AND projectrates.projectid='.qw($this->PD['project'])), true);
    $select->hrefbase = $BASEURL.'/specialcosts/-1/'.$this->PD['project'].'/';
    $select->setFormat('id', '%s', array('name'), '%50.50s', array('longname'));
    echo $select->display();
  }

  function edit() {
    list($id, $specCost) = $this->_getCostObject();
    $specCost->update($this->PD);
    $specCost->checkValid();
    echo $this->reportAction($specCost->sync(), 
          array(
              STATUS_OK =>   ($id < 0 ? 'Cost schedule created' : 'Cost schedule updated'),
              STATUS_ERR =>  'Cost schedule could not be changed: '.$specCost->errorMessage
          )
        );
    echo $specCost->display();
    if ($id < 0) {
      $submit = 'Create new project cost';
      $delete = '0';
    } else {
      $submit = 'Update entry';
      $delete = 'Delete entry';
    }
    //echo '<input type="hidden" name="project" value="'.$this->PD['project'].'" />';
    //echo '<input type="hidden" name="instrument" value="'.$this->PD['instrument'].'" />';
    echo "<input type='submit' name='submit' value='$submit' />";
    if ($delete) echo "<input type='submit' name='delete' value='$delete' />";
  }

  function delete() {
    list($id, $cost) = $this->_getCostObject();
    echo $this->reportAction($cost->delete(), 
              array(
                  STATUS_OK =>   'Cost deleted',
                  STATUS_ERR =>  'Cost could not be deleted:<br/><br/>'.$cost->errorMessage
              )
            );  
  }

  /**
  * Create a SpecialCost object 
  *
  * @return array ($id, $special_cost) 
  */
  function _getCostObject() {    
    if ($this->PD['createnew']) {
      $id = -1;
    } else {
      $row = quickSQLSelect('projectrates', array('projectid',         'instrid'),
                                            array($this->PD['project'], $this->PD['instrument']));
      $id = (is_array($row) && isset($row['rate'])) ? $row['rate'] : -1;
    }
    $specCost = new SpecialCost($id, $this->PD['project'], $this->PD['instrument']);  
    return array($id, $specCost);
  }
  
  
} //ActionSpecialCost


?> 
