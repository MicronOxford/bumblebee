<?php
# edit the groups

  function actionGroup()
  {
    if (! isset($_POST['group'])) {
      selectgroup();
    } elseif (! isset($_POST['updategroup'])) {
      editgroup($_POST['group']);
    } elseif ($_POST['delete'] == 1) {
      deletegroup($_POST['group']);
    } elseif ($_POST['group'] == -1) {
      insertgroup();
    } else {
      updategroup($_POST['updategroup']);
    }
  }

  function selectgroup()
  {
    echo <<<END
    <table>
    <tr><th>Select group to view/edit</th></tr>
    <tr><td>
      <select name="group">
        <option value='-1'>--- Create new group</option>
END;
        $q = "SELECT id,name,longname "
            ."FROM groups "
            ."ORDER BY name";
        $sql = mysql_query($q);
        if (! $sql) die (mysql_error());
        while ($row = mysql_fetch_row($sql))
        {
          echo "<option value='$row[0]'>$row[1] ($row[2])</option>";
        }                                    
    echo <<<END
      </select>
    </td></tr>
    <tr><td>
      <button name="action" type="submit" value="groups">
        Edit/create group
      </button>
    </td></tr>
    </table>
END;
  }

  function editgroup($gpid)
  {
    if ($gpid > 0) {
      $q = "SELECT * "
          ."FROM groups "
          ."WHERE id='$gpid'";
      $sql = mysql_query($q);
      if (! $sql) die (mysql_error());
      $g = mysql_fetch_array($sql);
    }

    echo "<table>"
        ."<tr><th>Edit/Create Group</th></tr>";
    echo "<tr><td>Short name</td>
          <td><input type='text' name='name' size='48' value='".$g['name']."' /></td></tr>";
    echo "<tr><td>Long name</td>
          <td><input type='text' name='longname' size='48' value='".$g['longname']."' /></td></tr>"
        ."<tr><td>Address(1)</td>";
    echo "<td><input type='text' name='addr1' size='48' value='".$g['addr1']."' /></td>"
        ."</tr><tr><td>Address(2)</td>"
        ."<td><input type='text' name='addr2' size='48' value='".$g['addr2']."' /></td>"
        ."</tr> <tr>";
    echo "<td>Suburb</td>"
        ."<td><input type='text' name='suburb' size='48' value='".$g['suburb']."' /></td>
    </tr>
    <tr>
      <td>State, Code</td>
      <td><input type='text' name='state' size='16' value='".$g['state']."' />
        <input type='text' name='code' size='16' value='".$g['code']."' /></td>
    </tr>
    <tr>
      <td>Country</td>
      <td><input type='text' name='country' size='48' value='".$g['country']."' /></td>
    </tr>
    <tr>
      <td>Email</td>
      <td><input type='text' name='email' size='48' value='".$g['email']."' /></td>
    </tr>
    <tr>
      <td>Fax</td>
      <td><input type='text' name='fax' size='48' value='".$g['fax']."' /></td>
    </tr>
    <tr>
      <td>Account</td>
      <td><input type='text' name='account' size='48' value='".$g['account']."' /></td>
    </tr>
    <tr><td>
      <input type='checkbox' name='delete' value='1'> Delete group</input>
    </td>
    <td>
      <button name='action' type='submit' value='groups'>
        Edit/create group
      </button>
      <input type='hidden' name='group' value='$gpid' />
      <input type='hidden' name='updategroup' value='$gpid' />
    </td></tr>
    </table>
";
  }

  function deletegroup($gpid)
  {
    $q = "DELETE FROM groups WHERE id='$gpid'";
    if (!mysql_query($q)) die(mysql_error());
    echo "action: '$q' successful";
  }

  function insertgroup()
  {
    $q = "INSERT INTO groups "
        ."(name,longname,addr1,addr2,suburb,code,country,email,fax,account) "
        ."VALUES "
        ."("
        ."'".$_POST['name']."','".$_POST['longname']."','".$_POST['addr1']."','".$_POST['addr2']."','".$_POST['suburb']."','".$_POST['code']."','".$_POST['country']."','".$_POST['email']."','".$_POST['fax']."','".$_POST['account']."'"
        .")";
    if (!mysql_query($q)) die(mysql_error());
    echo "action: '$q' successful";
  }

  function updategroup($gpid)
  {
    $q = "UPDATE groups SET "
        ."name='".$_POST['name']."',"
        ."longname='".$_POST['longname']."',"
        ."addr1='".$_POST['addr1']."',"
        ."addr2='".$_POST['addr2']."',"
        ."suburb='".$_POST['suburb']."',"
        ."code='".$_POST['code']."',"
        ."country='".$_POST['country']."',"
        ."email='".$_POST['email']."',"
        ."fax='".$_POST['fax']."',"
        ."account='".$_POST['account']."' "
        ."WHERE id='$gpid'";
    
    #echo "SQL='$q'";
    #echo "<br />now run the query<br />";
    if (!mysql_query($q)) die(mysql_error());
    echo "action: '$q' successful";
  }

?> 
