<?php
# $Id$
# edit the users

  function actionUsers()
  {
    if (! isset($_POST['user'])) {
      selectuser('users', 'Create new user', 'Edit/create user');
    } elseif (! isset($_POST['updateuser'])) {
      edituser($_POST['user']);
    } elseif ($_POST['delete'] == 1) {
      deleteuser($_POST['user']);
    } elseif ($_POST['user'] == -1) {
      insertuser();
    } else {
      updateuser($_POST['updateuser']);
    }
  }

  function getUsername($uid) {
    $q = "SELECT username,name "
        ."FROM users "
        ."WHERE id = '$uid'";
    $sql = mysql_query($q);
    if (! $sql) die (mysql_error());
    $g = mysql_fetch_row($sql);
    return $g;
  }

  function getUserOptions ($table, $i) {
    $q = "SELECT id,name,longname "
        ."FROM $table "
        ."ORDER BY name";
    $sql = mysql_query($q);
    if (! $sql) die (mysql_error());
    $options = "<option value='-1'>(none)</option>";
    while ($g = mysql_fetch_row($sql)) {
      $options .= "<option value='$g[0]'";
      if ($g[0]==$i) {
        $options .= " selected";
      }
      $options .= ">$g[1] ($g[2])</option>";
    }
    return $options;
  }

  function getInstrumentOptions($i,$permission)
  {
    echo "<tr><td>Instrument:</td><td>"
        ."<select name='permission$i'>".getUserOptions ("instruments", $permission['instrid'])
        ."</select></td></tr>";
    echo "<tr><td>Subscriptions</td><td>"
        ."Announce: "
        ."<input type='checkbox' name='permission$i-announce' value='1'"
        .($permission['announce']?" checked":"")
        ." /><br />"
        ."Unbook: "
        ."<input type='checkbox' name='permission$i-unbook' value='1'"
        .($permission['unbook']?" checked":"")
        ." /></td></tr>";

    echo "<tr><td>Priorities</td><td>"
        ."Priority user: "
        ."<input type='checkbox' name='permission$i-haspriority' value='1'"
        .($permission['haspriority']?" checked":"")
        ." /><br />"
        ."Points: "
        ."<input type='text' size='16' name='permission$i-points' value='"
        .$permission['points']."' /><br />"
        ."Recharge: "
        ."<input type='text' size='16' name='permission$i-pointsrecharge' value='"
        .$permission['pointsrecharge']."' /></td></tr>";
  }
    
  function selectuser($action, $firstoption, $button)
  {
    echo "<table>"
        ."<tr><th>Select user</th></tr>"
        ."<tr><td>";
    userselectbox('user',$firstoption);
    echo "</td></tr>";
    echo "<tr><td>
      <button name='action' type='submit' value='$action'>
        $button
      </button>
    </td></tr>
    </table>
    ";
  }

  function userselectbox($name, $firstoption, $selected=0)
  {
    echo "<select name='$name'>";
    if ($firstoption != "") {
      echo "<option value='-1'>--- $firstoption</option>";
    }
    $q = "SELECT id,username,name "
        ."FROM users "
        ."ORDER BY username";
    $sql = mysql_query($q);
    if (! $sql) die (mysql_error());
    while ($row = mysql_fetch_row($sql)) {
      $s = ($row[0] == $selected) ? " selected='1'" : "";
      echo "<option value='$row[0]'$s>$row[1] ($row[2])</option>";
    }                                    
    echo "</select>";
  }

  function edituser($gpid)
  {
    echo "retrieving user data<br />";
    if ($gpid > 0) {
      $q = "SELECT * "
          ."FROM users "
          ."WHERE id='$gpid'";
      $sql = mysql_query($q);
      if (! $sql) die (mysql_error());
      $p = mysql_fetch_array($sql);
    }

    echo "<table>"
        ."<tr><th colspan='2'>Edit/Create User</th></tr>";
    echo "<tr><td>Username</td>
          <td><input type='text' name='editusername' size='48' value='".$p['username']."' /></td></tr>";
    echo "<tr><td>Name</td>
          <td><input type='text' name='name' size='48' value='".$p['name']."' /></td></tr>";
    echo "<tr><td>Password</td>
          <td><input type='text' name='passwd' size='48' value='(secret)' /></td></tr>";
    echo "<tr><td>Email</td>
          <td><input type='text' name='email' size='48' value='".$p['email']."' /></td></tr>";
    echo "<tr><td>Phone</td>
          <td><input type='text' name='phone' size='48' value='".$p['phone']."' /></td></tr>";
    echo "<tr><td>Suspended</td>
          <td><input type='checkbox' name='suspended' value='1'"
          .($p['suspended']?" checked":"")."' /></td></tr>";
    echo "<tr><td>Admin</td>
          <td><input type='checkbox' name='isadmin' value='1'"
          .($p['isadmin']?" checked":"")."' /></td></tr>";

    #USER-PROJECT ASSOCIATIONS
    echo "<tr><th colspan='2'>User's projects</th></tr>";
    echo "<tr><td>Projects</td><td>Default</td></tr>";
    $q = "SELECT projectid,isdefault "
        ."FROM userprojects "
        ."LEFT JOIN projects on projects.id=userprojects.projectid "
        ."WHERE userid='$gpid' "
        ."ORDER BY projects.name";
    $sql = mysql_query($q);
    if (! $sql) die (mysql_error());
    $i=0;
    for ($i=0; $i<mysql_num_rows($sql)+2; $i++) {
      if ($i < mysql_num_rows($sql)) {
        $project = mysql_fetch_array($sql);
      } else {
        $project = array();
      }
      echo "<tr><td>"
          ."<select name='project$i'>".getUserOptions ("projects", $project['projectid'])
          ."</select></td>";
      echo "<td>"
          ."<input type='radio' name='defaultproject' value='$i'".($project['isdefault'] ? " checked='1'" : "")." />"
          ."</td>";
      echo "</tr>";
    }

    # USER-PERMISSION ASSOCIATIONS
    echo "<tr><th colspan='2'>User's permissions</th></tr>";
    $q = "SELECT * "
        ."FROM permissions "
        ."WHERE userid='$gpid'";
    $sql = mysql_query($q);
    if (! $sql) die (mysql_error());
    $i=0;
    while ($permission = mysql_fetch_array($sql)) {
      $i++;
      echo getInstrumentOptions($i,$permission);
    }
    for ($j=0; $j<2; $j++) {
      $i++;
      echo getInstrumentOptions($i,array());
    }

      
    echo "
    <tr><td>
      <input type='checkbox' name='delete' value='1'> Delete user</input>
    </td>
    <td>
      <button name='action' type='submit' value='users'>
        Edit/create project
      </button>
      <input type='hidden' name='user' value='$gpid' />
      <input type='hidden' name='updateuser' value='$gpid' />
    </td></tr>
    </table>
";
  }

  function deleteuser($gpid)
  {
    echo "deleting user data<br />";
    $q = "DELETE FROM users WHERE id='$gpid'";
    if (!mysql_query($q)) die(mysql_error());
    echo "<div class='sql'>action: '$q' successful</div>";
    $q = "DELETE FROM userprojects WHERE userid='$gpid'";
    if (!mysql_query($q)) die(mysql_error());
    echo "<div class='sql'>action: '$q' successful</div>";
    $q = "DELETE FROM permissions WHERE userid='$gpid'";
    if (!mysql_query($q)) die(mysql_error());
    echo "<div class='sql'>action: '$q' successful</div>";
  }

  function insertuser()
  {
    echo "inserting user data<br />";
    $q = "INSERT INTO users "
        ."(username,name,passwd,email,phone,suspended,isadmin) "
        ."VALUES "
        ."("
        ."'".$_POST['editusername']."','".$_POST['name']."','".md5($_POST['passwd'])."','".$_POST['email']."','".$_POST['phone']."','".$_POST['suspended']."','".$_POST['isadmin']."'"
        .")";
    if (!mysql_query($q)) die(mysql_error());
    echo "<div class='sql'>action: '$q' successful</div>";
    $newuserid = mysql_insert_id();
    updateuserprojects($newuserid);
    updateuserpermissions($newuserid);
  }

  function updateuser($gpid)
  {
    echo "updating user data<br />";
    $q = "UPDATE users SET "
        ."username='".$_POST['editusername']."',"
        ."name='".$_POST['name']."',"
        .($_POST['passwd']!='(secret)'?"passwd='".md5($_POST['passwd'])."',":"")
        ."email='".$_POST['email']."',"
        ."phone='".$_POST['phone']."',"
        ."suspended='".$_POST['suspended']."',"
        ."isadmin='".$_POST['isadmin']."' "
        ."WHERE id='$gpid'";
    
    #echo "SQL='$q'";
    #echo "<br />now run the query<br />";
    if (!mysql_query($q)) die(mysql_error());
    echo "<div class='sql'>action: '$q' successful</div>";
    updateuserprojects($gpid);
    updateuserpermissions($gpid);
  }

  function updateuserprojects($id) {
    $existing=array();
    #echo "<br />Looking for projects<br />";
    for ($j=0; isset($_POST['project'.$j]); $j++) {
      $existing['p'.$_POST['project'.$j]] = "insert";
      #echo "$j=".$existing['p'.$_POST['project'.$j]]. 
                 #" ". $_POST['project'.$j]. "<br />";
    }
    $q = "SELECT projectid "
        ."FROM userprojects "
        ."WHERE userid='$id'";
    $sql = mysql_query($q);
    if (! $sql) die (mysql_error());
    while ($project = mysql_fetch_row($sql)) {
      #echo "existing project $project[0]: '".$existing['p'.$project[0]]."' ";
      if (isset($existing['p'.$project[0]])) {
        $existing['p'.$project[0]] = "keep";
      } else {
        $existing['p'.$project[0]] = "remove";
      }
      #echo $existing['p'.$project[0]]. "<br />";
    }
    while (list($pproj, $action) = each($existing)) {
      $proj=substr($pproj,1);
      if ($proj != -1 && $action != "keep") {
        $q="";
        if ($action == "insert") {
          $q="INSERT IGNORE INTO userprojects (userid,projectid) VALUES ('$id','$proj')";
        } elseif ($action == "remove") {
          $q="DELETE FROM userprojects WHERE userid='$id' AND projectid='$proj'";
        }
        echo "Projects: $q<br />";
        if (!mysql_query($q)) die(mysql_error());
      }
    }
    #finally, look for the 'default' project
    $default = $_POST['defaultproject'];
    $default = $_POST["project$default"];
    $q="UPDATE userprojects SET isdefault='0' WHERE userid='$id'";
    if (!mysql_query($q)) die(mysql_error());
    $q="UPDATE userprojects SET isdefault='1' WHERE userid='$id' AND projectid='$default'";
    echo "Default projects $q<br />";
    if (!mysql_query($q)) die(mysql_error());
  }

  function updateuserpermissions($id) {
    $existing=array();
    #echo "<br />Looking for permissions<br />";
    for ($j=1; isset($_POST['permission'.$j]); $j++) {
      $existing['p'.$_POST['permission'.$j]] = "insert-$j";
      #echo "$j=".$existing['p'.$_POST['permission'.$j]]. "<br />";
    }
    $q = "SELECT * "
        ."FROM permissions "
        ."WHERE userid='$id'";
    $sql = mysql_query($q);
    if (! $sql) die (mysql_error());
    while ($project = mysql_fetch_array($sql)) {
      #echo "existing permission". $project['instrid'].": '".$existing['p'.$project['instrid']]."' ";
      if (isset($existing['p'.$project[0]])) {
        $j=substr($existing['p'.$project['instrid']],7);
        $existing['p'.$project['instrid']] = "update-$j";
      } else {
        $existing['p'.$project['instrid']] = "remove";
      }
      #echo $existing['p'.$project['instrid']]. "<br />";
    }
    while (list($pinstr, $action) = each($existing)) {
      $instr=substr($pinstr,1);
      if ($instr != -1 && $action != "keep") {
        $q="";
        if (substr($action,0,6) == "insert") {
          $j=substr($action,7);
          $q="INSERT IGNORE INTO permissions "
            ."(userid,instrid,announce,unbook,haspriority,points,pointsrecharge)"
            ."VALUES ('"
            .$id ."','"
            .$instr ."','"
            .$_POST['permission'.$j.'-announce'] ."','"
            .$_POST['permission'.$j.'-unbook'] ."','"
            .$_POST['permission'.$j.'-haspriority'] ."','"
            .$_POST['permission'.$j.'-points'] ."','"
            .$_POST['permission'.$j.'-pointsrecharge'] ."'"
            .")";
        } elseif (substr($action,0,6) == "update") {
          $j=substr($action,7);
          $q="UPDATE permissions SET "
            ."announce='".$_POST['permission'.$j.'-announce'] ."',"
            ."unbook='".$_POST['permission'.$j.'-unbook'] ."',"
            ."haspriority='".$_POST['permission'.$j.'-haspriority'] ."',"
            ."points='".$_POST['permission'.$j.'-points'] ."',"
            ."pointsrecharge='".$_POST['permission'.$j.'-pointsrecharge'] ."'"
            ." WHERE userid='$id' AND instrid='$instr'";
        } elseif ($action == "remove") {
          $q="DELETE FROM permissions where userid='$id' AND instrid='$instrid'";
        }
        echo "<div class='sql'>Permissions: '$q' successful</div>";
        if (!mysql_query($q)) die(mysql_error());
      }
    }
    echo "<div class='sql'>Note that the UPDATE queries may not have been necessary</div>";
  }
?> 
