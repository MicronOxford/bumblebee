<?php
# edit the groups

  function actionConsume()
  {
    if (! isset($_POST['consumable']) || $_POST['consumable'] == -1
        || ! isset($_POST['userid'])) {
      selectconsumable();
    } elseif (! isset($_POST['updateconsume'])) {
      edituse($_POST['consumable']);
    } else {
      insertuse();
    }
  }

  function edituse($gpid)
  {
    global $USERNAME;
    $q = "SELECT * "
        ."FROM consumables "
        ."WHERE id='$gpid'";
    $sql = mysql_query($q);
    if (! $sql) die (mysql_error());
    $g = mysql_fetch_array($sql);
    $userid = $_POST['userid'];
    $q = "SELECT * "
        ."FROM users "
        ."WHERE id='$userid'";
    $sql = mysql_query($q);
    if (! $sql) die (mysql_error());
    $u = mysql_fetch_array($sql);

    echo "<table>"
        ."<tr><th>Record consumable use</th></tr>";
    echo "<tr><td>Name</td><td>".$g['name']."</td></tr>";
    echo "<tr><td></td><td>".$g['longname']."</td></tr>";
    echo "<tr><td>Cost</td><td>".sprintf("$%01.2f ea",$g['cost'])."</td></tr>";
    echo "<tr><td>Date</td>"
        ."<td><input type='text' name='usewhen' size='48' value='"
        .date("Y-m-d")."' /></td></tr>";
    echo "<tr><td>Added by</td>"
        ."<td>$USERNAME</td></tr>";
    echo "<tr><td>Consumed by</td>"
        ."<td>"
        .$u['name'] ." (". $u['username'] .")"
        ."</td></tr>";
    echo "<td>Project</td><td>";
    projectselectbox('projectid','select project');
    echo "</td></tr>";
    echo "<tr><td>Quantity</td>"
        ."<td><input type='text' name='quantity' size='48' value='1'/></td></tr>";
    echo "<tr><td>Comments</td>"
        ."<td><input type='text' name='comments' size='48'/></td></tr>";
    echo "<tr><td>Log</td>"
        ."<td><input type='text' name='log' size='48'/></td></tr>";
    echo "<tr><td>
      <button name='action' type='submit' value='consume'>
        Create record
      </button>
      <input type='hidden' name='userid' value='$userid' />
      <input type='hidden' name='consumable' value='$gpid' />
      <input type='hidden' name='updateconsume' value='$gpid' />
    </td></tr>
    </table>";
  }

  function insertuse()
  { 
    global $UID;
    $ip = (getenv('HTTP_X_FORWARDED_FOR'))
        ?  getenv('HTTP_X_FORWARDED_FOR')
        :  getenv('REMOTE_ADDR');
    $q = "INSERT INTO consumables_use "
        ."(usewhen,consumable,quantity,addedby,userid,projectid,ip,comments,log) "
        ."VALUES "
        ."("
        ."'".$_POST['usewhen']."','".$_POST['consumable']."','".$_POST['quantity']."','".$UID."','".$_POST['userid']."','".$_POST['projectid']."','".$ip."','".$_POST['comments']."','".$_POST['log']."'"
        .")";
    if (!mysql_query($q)) die(mysql_error());
    echo "action: '$q' successful";
  }

?> 
