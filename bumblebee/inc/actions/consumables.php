<?php
# edit the groups

  function actionConsumables()
  {
    if (! isset($_POST['consumable'])) {
      selectconsumable();
    } elseif (! isset($_POST['updateconsumable'])) {
      editconsumable($_POST['consumable']);
    } elseif ($_POST['delete'] == 1) {
      deleteconsumable($_POST['consumable']);
    } elseif ($_POST['consumable'] == -1) {
      insertconsumable();
    } else {
      updateconsumable($_POST['updateconsumable']);
    }
  }

  function selectconsumable()
  {
    echo <<<END
    <table>
    <tr><th colspan='2'>Select consumable to view/edit or consume</th></tr>
    <tr><td colspan='2'>
      <select name="consumable">
        <option value='-1'>--- Create new consumable</option>
END;
        $q = "SELECT id,name,longname "
            ."FROM consumables "
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
      <button name="action" type="submit" value="consumables">
        Edit/create consumable
      </button>
    </td></tr>
    <tr><td>... or select a user as well to report usage</td></tr>
    <tr><td>User: 
END;
    userselectbox('userid','select user');
    echo <<<END
    </td></tr>
    <tr>
    <td>
      <button name="action" type="submit" value="consume">
        Use consumable
      </button>
    </td></tr>
    </table>
END;
  }

  function editconsumable($gpid)
  {
    if ($gpid > 0) {
      $q = "SELECT * "
          ."FROM consumables "
          ."WHERE id='$gpid'";
      $sql = mysql_query($q);
      if (! $sql) die (mysql_error());
      $g = mysql_fetch_array($sql);
    }

    echo "<table>"
        ."<tr><th>Edit/Create Consumable</th></tr>";
    echo "<tr><td>Short name</td>
          <td><input type='text' name='name' size='48' value='".$g['name']."' /></td></tr>";
    echo "<tr><td>Long name</td>
          <td><input type='text' name='longname' size='48' value='".$g['longname']."' /></td></tr>";
    echo "<tr><td>Cost</td>"
        ."<td><input type='text' name='cost' size='48' value='".$g['cost']."' /></td></tr>"
    ."<tr><td>
      <input type='checkbox' name='delete' value='1'> Delete consumable</input>
    </td>
    <td>
      <button name='action' type='submit' value='consumables'>
        Edit/create consumable
      </button>
      <input type='hidden' name='consumable' value='$gpid' />
      <input type='hidden' name='updateconsumable' value='$gpid' />
    </td></tr>
    </table>
";
  }

  function deleteconsumable($gpid)
  {
    $q = "DELETE FROM consumables WHERE id='$gpid'";
    if (!mysql_query($q)) die(mysql_error());
    echo "action: '$q' successful";
  }

  function insertconsumable()
  {
    $q = "INSERT INTO consumables "
        ."(name,longname,cost) "
        ."VALUES "
        ."("
        ."'".$_POST['name']."','".$_POST['longname']."','".$_POST['cost']."'"
        .")";
    if (!mysql_query($q)) die(mysql_error());
    echo "action: '$q' successful";
  }

  function updateconsumable($gpid)
  {
    $q = "UPDATE consumables SET "
        ."name='".$_POST['name']."',"
        ."longname='".$_POST['longname']."',"
        ."cost='".$_POST['cost']."' "
        ."WHERE id='$gpid'";
    
    #echo "SQL='$q'";
    #echo "<br />now run the query<br />";
    if (!mysql_query($q)) die(mysql_error());
    echo "action: '$q' successful";
  }

?> 
