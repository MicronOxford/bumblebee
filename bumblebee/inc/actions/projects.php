<?php
/**
* Edit/create/delete projects
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Actions
*/

/** project object */
require_once 'inc/bb/project.php';
/** list of choices */
require_once 'inc/formslib/anchortablelist.php';
/** parent object */
require_once 'inc/actions/actionaction.php';

/**
* Edit/create/delete projects
*
* @package    Bumblebee
* @subpackage Actions
*/
class ActionProjects extends ActionAction {

  /**
  * Initialising the class 
  * 
  * @param  BumblebeeAuth $auth  Authorisation object
  * @param  array $pdata   extra state data from the call path
  * @return void nothing
  */
  function ActionProjects($auth, $pdata) {
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
    echo "<br /><br /><a href='".makeURL('projects')."'>".T_('Return to project list')."</a>";
  }

  function select($deleted=false) {
    $select = new AnchorTableList(T_('Projects'), T_('Select which project to view'));
    $select->deleted = $deleted;
    $select->connectDB('projects', array('id', 'name', 'longname'));
    $select->list->prepend(array('-1', T_('Create new project')));
    $select->list->append(array('showdeleted', T_('Show deleted projects')));
    $select->hrefbase = makeURL('projects', array('id'=>'__id__'));
    $select->setFormat('id', '%s', array('name'), ' %50.50s', array('longname'));
    echo $select->display();
  }

  function edit() {
    $project = new Project($this->PD['id']);
    $project->update($this->PD);
    $project->checkValid();
    echo $this->reportAction($project->sync(), 
          array(
              STATUS_OK =>   ($this->PD['id'] < 0 ? T_('Project created') : T_('Project updated')),
              STATUS_ERR =>  T_('Project could not be changed:').' '.$project->errorMessage
          )
        );
    echo $project->display();
    if ($project->id < 0) {
      $submit = T_('Create new project');
      $delete = '0';
    } else {
      $submit = T_('Update entry');
      $delete = $project->isDeleted ? T_('Undelete entry') : T_('Delete entry');
    }
    echo "<input type='submit' name='submit' value='$submit' />";
    if ($delete) echo "<input type='submit' name='delete' value='$delete' />";
  }

  function delete() {
    $project = new Project($this->PD['id']);
    echo $this->reportAction($project->delete(), 
              array(
                  STATUS_OK =>   $project->isDeleted ? T_('Project undeleted') : T_('Project deleted'),
                  STATUS_ERR =>  T_('Project could not be deleted:')
                                 .'<br/><br/>'.$project->errorMessage
              )
            );  
  }

}

?>
