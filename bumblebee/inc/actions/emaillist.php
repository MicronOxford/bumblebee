<?php
# $Id$
# return a list of the email addresses depending on what we've been asked
# for... e.g. per instrument for the "announce" list.

  function actionEmaillist()
  {
    if (! isset($_POST['selectlist'])) {
      selectlists();
    } else {
      returnlists();
    }
  }

  function selectlists() {
    echo "<h2>Please select the email lists to return</h2>";
    $q = "SELECT DISTINCT * "
        ."FROM instruments "
        ."ORDER BY name";
    $sql = mysql_query($q);
    if (! $sql) die (mysql_error());
    if (mysql_num_rows($sql)==0) {
      echo "<p>Sorry: no instrument contact lists found</p>";
    } else {
      echo "<table>";
      echo "<tr><th>Instrument</th><th>'Announce'</th><th>'Unbook'</th>"
          ."</tr>";
      $j = 0;
      while ($g = mysql_fetch_array($sql)) {
        $j++;
        echo "<tr>"
            ."<td>".$g['name']."</td>"
            ."<td><input type='checkbox' name='announce-$j' value='1' /></td>"
            ."<td><input type='checkbox' name='unbook-$j' value='1' /></td>"
            ."</tr>"
            ."<input type='hidden' name='instr-$j' value='".$g['id']."' />";
      }
      echo "
        <input type='hidden' name='selectlist' value='1' />
        <tr><td><button name='action' type='submit' value='emaillist'>
            Get lists
          </button>
        </td></tr>
      </table>
      ";
    }
  }

  function returnlists() {
    $q = "SELECT DISTINCT users.email "
        ."FROM permissions "
        ."LEFT JOIN users ON users.id=permissions.userid "
        ."WHERE 0 ";
        #."WHERE permissions.announce='1'";
    $where = "";
    for ($j=1; isset($_POST['instr-'.$j]); $j++) {
      $instr = $_POST['instr-'.$j];
      $announce = $_POST['announce-'.$j];
      $unbook = $_POST['unbook-'.$j];
      #echo "$j ($instr) => ($unbook, $announce)<br />";
      #$where .= "OR (permissions.announce='1' AND permissions.instrid='$instr') ";
      $where .= $announce ? "OR (permissions.instrid='$instr' AND permissions.announce='1') " : "";
      $where .= $unbook ? "OR (permissions.instrid='$instr' AND permissions.unbook='1') " : "";
    }
    $q .= $where;
    #echo "Gathering email addresses: $q<br />";
    echo "<br />";
    if (!$sql = mysql_query($q)) die(mysql_error());
    if (mysql_num_rows($sql)==0) {
      echo "<p>No email addresses found</p>";
    }
    while ($g = mysql_fetch_array($sql)) {
      echo $g['email'] ."<br />";
    }
    echo "<div class='sql'>action: '$q' successful</div>";
  }
?> 
