<?php
# $Id$
# edit the projects

include_once 'inc/project.php';
include_once 'inc/dbforms/anchortablelist.php';

class ActionProjects extends ActionAction {

  function ActionProjects($auth, $pdata) {
    parent::ActionAction($auth, $pdata);
    $this->mungePathData();
  }

  function go() {
    global $BASEURL;
    if (! isset($this->PD['id'])) {
      $this->selectProject();
    } elseif (isset($this->PD['delete'])) {
      $this->deleteProject($this->PD['id']);
    } else {
      $this->editProject($this->PD);
    }
    echo "<br /><br /><a href='$BASEURL/projects'>Return to group list</a>";
  }

  function selectProject() {
    global $BASEURL;
    $projectselect = new AnchorTableList("Projects", "Select which project to view");
    $projectselect->connectDB("projects", array("id", "name", "longname"));
    $projectselect->list->prepend(array("-1","Create new project"));
    $projectselect->hrefbase = "$BASEURL/projects/";
    $projectselect->setFormat("id", "%s", array("name"), " %s", array("longname"));
    echo $projectselect->display();
  }

  function editProject() {
    $project = new Project($this->PD['id']);
    $project->update($this->PD);
    $project->checkValid();
    #$project->fields['defaultclass']->invalid = 1;
    $project->sync();
    echo $project->display();
    if ($project->id < 0) {
      $submit = "Create new project";
      $delete = "0";
    } else {
      $submit = "Update entry";
      $delete = "Delete entry";
    }
    echo "<input type='submit' name='submit' value='$submit' />";
    if ($delete) echo "<input type='submit' name='delete' value='$delete' />";
  }

  function deleteProject() {
    $project = new Project($this->PD['id']);
    $project->delete();
  }
}
?> 
