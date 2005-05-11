<?php
# $Id$
# Group object (extends dbo)

include_once 'inc/formslib/dbrow.php';
include_once 'inc/formslib/textfield.php';

class SpecialCost extends DBRow {
  
  function SpecialCost($id) {
  }

  
  function editSpecialCost($proj) {
    $q = "SELECT projects.name AS projname,longname,"
               ."userclass.id AS class,userclass.name AS classname "
               ."FROM projects "
               ."LEFT JOIN userclass ON projects.defaultclass=userclass.id "
               ."WHERE projects.id='$proj'";
    $sql = mysql_query($q);
    if (! $sql) die (mysql_error());
    $g = mysql_fetch_array($sql);
    $class=$g['class'];

    echo "<table>"
        ."<tr><td>Project:</td><td>".$g['projname']." (".$g['longname'].")</td></tr>";
    $qc = "SELECT instrumentclass.name AS iname, "
               ."cost_hour, cost_halfday, cost_fullday "
               ."FROM costs "
               ."LEFT JOIN instrumentclass ON costs.instrumentclass=instrumentclass.id "
               ."WHERE userclass='$class'";
    $sqlc = mysql_query($qc);
    if (! $sqlc) die (mysql_error());
    echo "<tr><th colspan='2'>Default category</th></tr>";
    echo "<tr><td>Category name</td><td>".$g['classname']."</td></tr>";
    echo "<tr><th>Instrument class</th>"
        ."<th>hourly cost</th><th>half-day cost</th><th>full-day cost</th></tr>";
    while ($gc = mysql_fetch_array($sqlc)) {
      echo "<tr><td>".$gc['iname']."</td>"
          ."<td>".$gc['cost_hour']."</td>"
          ."<td>".$gc['cost_halfday']."</td>"
          ."<td>".$gc['cost_fullday']."</td>"
          ."</tr>";
    }

    $qs = "SELECT instruments.name AS iname, instruments.id AS iid, "
               ."rate, "
               ."cost_hour, cost_halfday, cost_fullday "
               ."FROM projectrates "
               ."LEFT JOIN instruments ON instruments.id=projectrates.instrid "
               ."LEFT JOIN costs ON costs.id=projectrates.rate "
               ."WHERE projectrates.projectid='$proj'";
    $sqls = mysql_query($qs);
    if (! $sqls) die (mysql_error());
    echo "<tr><th colspan='2'>Special charging rates</th></tr>";
    echo "<tr><th>Instrument</th>"
        ."<th>hourly cost</th><th>half-day cost</th><th>full-day cost</th></tr>";
    $i=0;
    while ($gs = mysql_fetch_array($sqls)) {
      $i++;
      specialCostListing($i, $gs);
    }
    specialCostListing(++$i, array());
    specialCostListing(++$i, array());
    echo "</table>";


    echo <<<END
    <tr><td></td>
    <td>
      <button name='action' type='submit' value='specialcosts'>
        Update cost schedules
      </button>
      <input type='hidden' name='updatespecialcost' value='$proj' />
      <input type='hidden' name='project' value='$proj' />
    </td></tr>
    </table>
END;
    echoSQL($q);
    echoSQL($qc);
    echoSQL($qs);
  }

  function specialCostListing($i, $gs) {
    echo "<tr><td><select name='cost$i-iid'>".getUserOptions('instruments',$gs['iid'])."</select></td>"
        ."<td><input type='text' name='cost$i-ch' value='".$gs['cost_hour']."' size='10' /></td>"
        ."<td><input type='text' name='cost$i-chd' value='".$gs['cost_halfday']."' size='10' /></td>"
        ."<td><input type='text' name='cost$i-cfd' value='".$gs['cost_fullday']."' size='10' /></td>"
        ."<input type='hidden' name='cost$i' value='".$gs['rate']."' />"
        ."</tr>";
  }


  function updateSpecialCost($proj) {
    for ($j=1; isset($_POST['cost'.$j]); $j++) {
      if ($_POST['cost'.$j]>0) {
        updatespecialsinglerate($proj,$j);
      } elseif ($_POST["cost$j-iid"] > 0) {
        insertspecialsinglerate($proj,$j);
      } elseif ($_POST["cost$j"] > 0) {
        deletespecialsinglerate($proj,$j);
      }
    }
      
  }
  
  function updatespecialsinglerate($proj,$i) {
    $qc = "UPDATE costs SET "
        ."cost_hour='".$_POST["cost$i-ch"]."',"
        ."cost_halfday='".$_POST["cost$i-chd"]."',"
        ."cost_fullday='".$_POST["cost$i-cfd"]."' "
        ."WHERE id='".$_POST["cost$i"]."'";
    $qpr = "REPLACE INTO projectrates SET "
        ."rate='".$_POST["cost$i"]."', "
        ."projectid='$proj', "
        ."instrid='".$_POST["cost$i-iid"]."'";
        #."WHERE projectid='$proj' AND instrid='".$_POST["cost$i-iid"]."'";
    if (!mysql_query($qc)) die(mysql_error());
    echoSQL($qc, 1);
    if (!mysql_query($qpr)) die(mysql_error());
    echoSQL($qpr, 1);
  }

  function insertspecialsinglerate($proj,$i) {
    $qc = "INSERT INTO costs "
        ."(cost_hour,cost_halfday,cost_fullday) "
        ."VALUES "
        ."("
        ."'".$_POST["cost$i-ch"]."','".$_POST["cost$i-chd"]."','".$_POST["cost$i-cfd"]."'"
        .")";
    if (!mysql_query($qc)) die(mysql_error());
    echoSQL($qc, 1);
    $cost=mysql_insert_id();
    $qpr = "INSERT INTO projectrates SET "
        ."rate='$cost', "
        ."projectid='$proj', "
        ."instrid='".$_POST["cost$i-iid"]."'";
    if (!mysql_query($qpr)) die(mysql_error());
    echoSQL($qpr, 1);
  }

  function deletespecialsinglerate($proj,$i) {
    $q = "DELETE FROM stdrates WHERE category='$gpid'";
    if (!mysql_query($q)) die(mysql_error());
    echoSQL($q, 1);
  }


  
  
  
  
  function display() {
    return $this->displayAsTable();
  }

} //class SpecialCost
