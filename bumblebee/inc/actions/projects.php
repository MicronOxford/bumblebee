<?php
# $Id$
# edit the projects

include_once 'inc/project.php';
include_once 'inc/simplelist.php';
include_once 'inc/anchorlist.php';


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
/*
    if (! isset($_POST['project'])) {
      selectproject();
    } elseif (! isset($_POST['updateproject'])) {
      editproject($_POST['project']);
    } elseif ($_POST['delete'] == 1) {
      deleteproject($_POST['project']);
    } elseif ($_POST['project'] == -1) {
      insertproject();
    } else {
      updateproject($_POST['updateproject']);
    }
*/
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
    #$projectlist = new SimpleList("projects", "id", "CONCAT(name, ' (', longname, ')')");
    $projectlist = new SimpleList("projects", "id", "name", "longname");
    $projectlist->prepend("-1","Create new project");
    #$grouplist->append("-1","Create new project");
    #echo $projectlist->display();
    $projectselect = new AnchorList("Projects", "Select which project to view");
    $projectselect->setChoices($projectlist);
    $projectselect->ulclass = "selectlist";
    $projectselect->hrefbase = "$BASEURL/projects/";
    echo $projectselect->display();
  }

  function editProject($PD) {
    $group = new Project($PD['id']);
    $group->update($PD);
    if (! $group->sync()) {
      #if we had to sync, then perhaps we should reload (if new charge band
      #created we need this)
      $group = new Project($PD['id']);
    }
    #echo $group->text_dump();
    echo $group->display();
    if ($group->id < 0) {
      $submit = "Create new project";
      $delete = "0";
    } else {
      $submit = "Update entry";
      $delete = "Delete entry";
    }
    #$submit = ($PD['id'] < 0 ? "Create new" : "Update entry");
    echo "<input type='submit' name='submit' value='$submit' />";
    if ($delete) echo "<input type='submit' name='delete' value='$delete' />";
  }

/*
  function editproject($gpid) {
    if ($gpid > 0) {
      $q = "SELECT * "
          ."FROM projects "
          ."WHERE id='$gpid'";
      $sql = mysql_query($q);
      if (! $sql) die (mysql_error());
      $p = mysql_fetch_array($sql);
    }

    echo "<table>"
        ."<tr><th>Edit/Create Project</th></tr>";
    echo "<tr><td>Short name</td>
          <td><input type='text' name='name' size='48' value='".$p['name']."' /></td></tr>";
    echo "<tr><td>Long name</td>
          <td><input type='text' name='longname' size='48' value='".$p['longname']."' /></td></tr>";

    if ($gpid > 0) {
      $q = "SELECT * "
          ."FROM projectgroups "
          ."LEFT JOIN groups ON groups.id=projectgroups.groupid "
          ."WHERE projectid='$gpid' "
          ."ORDER BY groups.name";
      $sql = mysql_query($q);
      if (! $sql) die (mysql_error());
    }
    for ($i=1; $i<=3; $i++)
    {
      if ($gpid > 0) $pg = mysql_fetch_array($sql);
      echo "<tr><td>Group $i</td>";
      echo "<td><select name='groupid-$i'>".getGroupOptions($i, $pg)."</select></td>";
      echo "<td>fraction: <input type='text' name='grouppc-$i' size='4' value='".$pg['grouppc']."' />%</td>";
    }
    echo "</tr>\n";

    $qc = "SELECT id,name  "
        ."FROM userclass "
        ."ORDER BY name";
    $sqlc = mysql_query($qc);
    if (! $sqlc) die (mysql_error());
    echo "<tr><td>Default charging band</td><td>";
    #echo "<select name='defaultclass'>";
    $checked="checked='checked' ";
    while ($pc = mysql_fetch_array($sqlc)) {
      $thischecked = "";
      #echo "<option value='".$pc['id']."'>".$pc['name']."</option>";
      #echo "(id,dc)=" . $pc['id'] .",".$p['defaultclass']."<br />";
      if ($pc['id']==$p['defaultclass']) {
        $thischecked = $checked;
        $hasbeenchecked = 1;
      }
      echo "<label><input type='radio' name='defaultclass' value='".$pc['id']."' $thischecked/> ".$pc['name']."</label><br />";
    }
    $thischecked = $hasbeenchecked ? "" : $checked;
    echo "<label><input type='radio' name='defaultclass' value='-1' $thischecked/></label> Create new <input type='text' name='newclassname' size='24' /><br />";
    #echo "<option value='".$pc['id']."'>".$pc['name']."</option>";
    #echo "</select></td></tr>\n";
    echo "</td></tr>\n";
    echo "
    <tr><td>
      <input type='checkbox' name='delete' value='1'> Delete group</input>
    </td>
    <td>
      <button name='action' type='submit' value='projects'>
        Edit/create project
      </button>
      <input type='hidden' name='project' value='$gpid' />
      <input type='hidden' name='updateproject' value='$gpid' />
    </td></tr>
    </table>
";
  }
*/

  function deleteProject($gpid) {
    $q = "DELETE FROM projects WHERE id='$gpid'";
    db_quiet($q, 1);
  }

/*
  function insertproject() {
    $class = checkUserClassInfo();
    $q = "INSERT INTO projects "
        ."(name,longname,defaultclass) "
        ."VALUES "
        ."("
        ."'".$_POST['name']."','".$_POST['longname']."','$class'"
        .")";
    if (!mysql_query($q)) die(mysql_error());
    echoSQL($q, 1);
    $prid = mysql_insert_id();
    updategroupproject($prid);
  }
*/

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

/*
  function updateproject($prid) {
    $class = checkUserClassInfo();
    $update = "UPDATE projects SET "
        ."name='".$_POST['name']."',"
        ."longname='".$_POST['longname']."',"
        ."defaultclass='$class' "
        ."WHERE id='$prid'";
    
    if (!mysql_query($update)) die(mysql_error());
    echoSQL($update, 1);
    updategroupproject($prid);
  }
*/

/*
function checkUserClassInfo() {
  if ($_POST['defaultclass'] == -1) {
    $class = createNewUserClass();
    #getNewClassInfo($class);
  } else {
    $class = $_POST['defaultclass'];
  }
  return $class;
}

function createNewUserClass() {
  $q = "INSERT INTO userclass (name) VALUES ('".$_POST['newclassname']."')";
  if (!mysql_query($q)) die(mysql_error());
  echoSQL($q,1);
  $class = mysql_insert_id();
  return $class;
}
*/

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
