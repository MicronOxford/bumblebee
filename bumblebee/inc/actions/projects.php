<?php
# $Id$
# edit the projects

  function actionProjects() {
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
  }

  function getGroupOptions ($i, $p) {
    $q = "SELECT id,name,longname "
        ."FROM groups "
        ."ORDER BY name";
    $sql = mysql_query($q);
    if (! $sql) die (mysql_error());
    $groupoptions = "<option value='-1'>(none)</option>";
    while ($g = mysql_fetch_row($sql)) {
      $groupoptions .= "<option value='$g[0]'";
      if ($g[0]==$p['groupid']) {
        $groupoptions .= " selected";
      }
      $groupoptions .= ">$g[1] ($g[2])</option>";
    }
    return $groupoptions;
  }

  function selectproject() {
    echo <<<END
    <table>
    <tr><th>Select project to view/edit</th></tr>
    <tr><td>
END;
    projectselectbox('project', 'Create new project');
    echo <<<END
    </td></tr>
    <tr><td>
      <button name="action" type="submit" value="projects">
        Edit/create project
      </button>
    </td></tr>
    </table>
END;
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

  function deleteproject($gpid) {
    $q = "DELETE FROM projects WHERE id='$gpid'";
    if (!mysql_query($q)) die(mysql_error());
    echo "action: '$q' successful";
  }

  function insertproject() {
    $class = checkUserClassInfo();
    $q = "INSERT INTO projects "
        ."(name,longname,defaultclass) "
        ."VALUES "
        ."("
        ."'".$_POST['name']."','".$_POST['longname']."','$class'"
        .")";
    if (!mysql_query($q)) die(mysql_error());
    echo "<div class='sql'>action: '$q' successful</div>";
    $prid = mysql_insert_id();
    updategroupproject($prid);
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
    echo "action: '$delete' successful<br />";
    if (!mysql_query($insert)) die(mysql_error());
    echo "action: '$insert' successful<br />";
  }

  function updateproject($prid) {
    $class = checkUserClassInfo();
    $update = "UPDATE projects SET "
        ."name='".$_POST['name']."',"
        ."longname='".$_POST['longname']."',"
        ."defaultclass='$class' "
        ."WHERE id='$prid'";
    
    if (!mysql_query($update)) die(mysql_error());
    echo "<div class='sql'>action: '$update' successful</div>";
     updategroupproject($prid);
  }

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
  echo "<div class='sql'>action: '$q' successful</div>";
  $class = mysql_insert_id();
  return $class;
}


?> 
