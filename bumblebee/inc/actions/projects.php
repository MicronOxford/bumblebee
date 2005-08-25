<?php
# $Id$
# edit the projects

include_once 'inc/bb/project.php';
include_once 'inc/formslib/anchortablelist.php';
include_once 'inc/actions/actionaction.php';

class ActionProjects extends ActionAction {

  function ActionProjects($auth, $pdata) {
    parent::ActionAction($auth, $pdata);
    $this->mungePathData();
  }

  function go() {
    global $BASEURL;
    if (! isset($this->PD['id'])) {
      $this->selectProject(issetSet($this->PD, 'showdeleted', false));
    } elseif (isset($this->PD['delete'])) {
      $this->deleteProject();
    } else {
      $this->editProject();
    }
    echo "<br /><br /><a href='$BASEURL/projects'>Return to project list</a>";
  }

  function selectProject($deleted=false) {
    global $BASEURL;
    $select = new AnchorTableList('Projects', 'Select which project to view');
    $select->deleted = $deleted;
    $select->connectDB('projects', array('id', 'name', 'longname'));
    $select->list->prepend(array('-1','Create new project'));
    $select->list->append(array('showdeleted','Show deleted projects'));
    $select->hrefbase = $BASEURL.'/projects/';
    $select->setFormat('id', '%s', array('name'), ' %50.50s', array('longname'));
    echo $select->display();
  }

  function editProject() {
    $project = new Project($this->PD['id']);
    $project->update($this->PD);
    $project->checkValid();
    echo $this->reportAction($project->sync(), 
          array(
              STATUS_OK =>   ($this->PD['id'] < 0 ? 'Project created' : 'Project updated'),
              STATUS_ERR =>  'Project could not be changed: '.$project->errorMessage
          )
        );
    echo $project->display();
    if ($project->id < 0) {
      $submit = 'Create new project';
      $delete = '0';
    } else {
      $submit = 'Update entry';
      $delete = $project->isDeleted ? 'Undelete entry' : 'Delete entry';
    }
    echo "<input type='submit' name='submit' value='$submit' />";
    if ($delete) echo "<input type='submit' name='delete' value='$delete' />";
  }

  function deleteProject() {
    $project = new Project($this->PD['id']);
    echo $this->reportAction($project->delete(), 
              array(
                  STATUS_OK =>   $project->isDeleted ? 'Project undeleted' : 'Project deleted',
                  STATUS_ERR =>  'Project could not be deleted:<br/><br/>'.$project->errorMessage
              )
            );  
  }

}

?>