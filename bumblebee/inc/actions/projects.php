<?php
# $Id$
# edit the projects

include_once 'inc/project.php';
include_once 'inc/anchortablelist.php';


  function actionProjects() {
    global $BASEURL;
    $PD = projectMungePathData();
    if (! isset($PD['id'])) {
      selectProject();
    } elseif (isset($PD['delete'])) {
      deleteProject($PD['id']);
    } else {
      editProject($PD);
    }
    echo "<br /><br /><a href='$BASEURL/projects'>Return to group list</a>";
  }

  function projectMungePathData() {
    global $PDATA;
    $PD = array();
    foreach ($_POST as $k => $v) {
      $PD[$k] = $v;
    }
    if (isset($PDATA[1])) {
      $PD['id'] = $PDATA[1];
    }
    echo "<pre>".print_r($PD,true)."</pre>";
    return $PD;
  }

  function selectProject() {
    global $BASEURL;
    $projectselect = new AnchorTableList("Projects", "Select which project to view");
    $projectselect->connectDB("projects", array("id", "name", "longname"));
    $projectselect->list->prepend(array("-1","Create new project"));
    $projectselect->hrefbase = "$BASEURL/projects/";
    $projectselect->setFormat("id", "%s"," %s", array("name"), array("longname"));
    echo $projectselect->display();
  }

  function editProject($PD) {
    $project = new Project($PD['id']);
    $project->update($PD);
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

  function deleteProject($gpid) {
    $q = "DELETE FROM projects WHERE id='$gpid'";
    db_quiet($q, 1);
  }

  function updategroupproject($prid) {
    $delete = "DELETE FROM projectgroups WHERE projectid='$prid'";
    $insert = "INSERT INTO projectgroups (projectid,groupid,grouppc) VALUES ";
    for ($i=1; $i<=3; $i++) {
      $gpid = $_POST["groupid-$i"];
      $gppc = $_POST["grouppc-$i"];
      $insert .= $gpid==-1 ? "" : "('$prid','$gpid','$gppc'),";
    }
    $insert = substr($insert,0,-1);  #cut off the final comma!
    if (!mysql_query($delete)) die(mysql_error());
    echoSQL($delete, 1);
    if (!mysql_query($insert)) die(mysql_error());
    echoSQL($insert, 1);
  }

  function projectselectbox($name,$firstoption) {
    echo "<select name='$name'>";
    if ($firstoption != "") {
      echo "<option value='-1'>--- $firstoption</option>";
    }
    $q = "SELECT id,name,longname "
        ."FROM projects "
        ."ORDER BY name";
    $sql = mysql_query($q);
    if (! $sql) die (mysql_error());
    while ($row = mysql_fetch_row($sql))
    {
      echo "<option value='$row[0]'>$row[1] ($row[2])</option>";
    }                                    
    echo "</select>";
  }

?> 
