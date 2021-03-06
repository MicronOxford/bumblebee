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
*
* path (bumblebee root)/inc/actions/specialcosts.php
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

/** specialcosts object */
require_once 'inc/bb/specialcosts.php';
/** list of choices */
require_once 'inc/formslib/anchortablelist.php';
/** parent object */
require_once 'inc/actions/actionaction.php';

/**
* Edit/create/delete special instrument usage costs
*
* @package    Bumblebee
* @subpackage Actions
*/
class ActionSpecialCosts extends ActionAction {

  /**
  * Initialising the class
  *
  * @param  BumblebeeAuth $auth  Authorisation object
  * @param  array $pdata   extra state data from the call path
  * @return void nothing
  */
  function ActionSpecialCosts($auth, $pdata) {
    parent::ActionAction($auth, $pdata);
    //$this->DEBUG=10;
    $this->mungeInputData();
  }

  function go() {
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
    } elseif (isset($this->PD['delete'])) {
      if ($this->readOnly) {
        $this->readOnlyError();
      } else {
        $this->delete();
      }
    } else {
      if ($this->readOnly) $this->_dataCleanse(array('project', 'instrument', 'createnew'));
      $this->edit();
    }
    echo "<br /><br /><a href='".makeURL('specialcosts')."'>".T_('Return to special costs list')."</a>";
  }

  function mungeInputData() {
    parent::mungeInputData();
    $this->PD['createnew'] = isset($this->PD['createnew']) && $this->PD['createnew'];
    if (isset($this->PD['project']) && $this->PD['project'] == -1) {
      $this->PD['createnew'] = true;
      unset($this->PD['project']);
    }
    if (isset($this->PD['instrument']) && $this->PD['instrument'] == -1) {
      $this->PD['createnew'] = true;
      unset($this->PD['instrument']);
    }
/*    if (isset($this->PD[1]) && ($this->PDATA[1] == -1)) {
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
    echoData($this->PD, 0);*/
  }

  /**
  * Select for which project the special costs should be displayed
  *
  */
  function selectProject() {
    $this->log("selectProject: which existing rate to edit");
    $select = new AnchorTableList(T_('Projects'), T_('Select project to view rates'));
    $select->connectDB('projects', array('id', 'name', 'longname'),
                            'projectid IS NOT NULL',
                            'name',
                            'id',
                            NULL,
                            array('projectrates'=>'projectrates.projectid=projects.id'), true);
    $select->list->prepend(array('-1', T_('Create new project rate')));
    $select->hrefbase = makeURL('specialcosts', array('project'=>'__id__'));
    $select->setFormat('id', '%s', array('name'), '%50.50s', array('longname'));
    echo $select->display();
  }

  /**
  * Select for which project a special cost should be created
  *
  */
  function selectProjectCreate() {
    $this->log("selectProjectCreate: which project to create a new rate for");
    $select = new AnchorTableList(T_('Projects'), T_('Select project to create rate'));
    $select->connectDB('projects', array('id', 'name', 'longname'));
    $select->hrefbase = makeURL('specialcosts', array('project'=>'__id__', 'createnew'=>1));
    $select->setFormat('id', '%s', array('name'), '%50.50s', array('longname'));
    echo $select->display();
  }

  /**
  * Select for which instrument the special costs should be displayed
  *
  */
  function selectInstrument() {
    $this->log("selectInstrument: which existing rate to edit");
    $select = new AnchorTableList(T_('Instruments'), T_('Select instrument to view rates'));
    $select->connectDB('instruments', array('id', 'name', 'longname'),
                            'projectid='.qw($this->PD['project']),
                            'name',
                            'id',
                            NULL,
                            array('projectrates'=>'projectrates.instrid=instruments.id'), true);
    $select->list->prepend(array('-1', T_('Create new project rate')));
    $select->hrefbase = makeURL('specialcosts', array('instrument'=>'__id__', 'createnew'=>$this->PD['createnew'], 'project'=>$this->PD['project']));
    $select->setFormat('id', '%s', array('name'), '%50.50s', array('longname'));
    echo $select->display();
  }

  /**
  * Select for which instrument a special cost should be created
  *
  */
  function selectInstrumentCreate() {
    $this->log("selectInstrumentCreate: which instrument to create a new rate for");
    $select = new AnchorTableList(T_('Instruments'), T_('Select instrument to create rate'));
    $select->connectDB('instruments', array('id', 'name', 'longname'),
                            'projectid IS NULL',        //find rows *not* in the join
                            'name',
                            'id',
                            NULL,
                            array('projectrates'=>'projectrates.instrid=instruments.id AND projectrates.projectid='.qw($this->PD['project'])), true);
    $select->hrefbase = makeURL('specialcosts', array('instrument'=>'__id__', 'createnew'=>$this->PD['createnew'], 'project'=>$this->PD['project']));
    $select->setFormat('id', '%s', array('name'), '%50.50s', array('longname'));
    echo $select->display();
  }

  function edit() {
    list($id, $specCost) = $this->_getCostObject();
    $specCost->update($this->PD);
    $specCost->checkValid();
    echo $this->reportAction($specCost->sync(),
          array(
              STATUS_OK =>   ($id < 0 ? T_('Cost schedule created') : T_('Cost schedule updated')),
              STATUS_ERR =>  T_('Cost schedule could not be changed:').' '.$specCost->errorMessage
          )
        );
    echo $specCost->display();
    if ($id < 0) {
      $submit = T_('Create new project cost');
      $delete = '0';
    } else {
      $submit = T_('Update entry');
      $delete = T_('Delete entry');
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
                  STATUS_OK =>   T_('Cost deleted'),
                  STATUS_ERR =>  T_('Cost could not be deleted:').'<br/><br/>'.$cost->errorMessage
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
