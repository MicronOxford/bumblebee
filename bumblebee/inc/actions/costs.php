<?php
# $Id$
# edit the groups

include_once 'inc/costs.php';
include_once 'inc/dbforms/anchortablelist.php';

class ActionCosts extends ActionAction {

  function ActionCosts($auth, $pdata) {
    parent::ActionAction($auth, $pdata);
    $this->mungePathData();
  }

  function go() {
    global $BASEURL;
    if (! isset($this->PD['category'])) {
      $this->selectCost();
    } else {
      $this->editCost();
    }
    /* elseif (! isset($this->PD['updatecategory'])) {
      $this->editCost($_POST['category']);
    #} elseif ($_POST['delete'] == 1) {
    #  deletecost($_POST['category']);
    } else {
      $this->updatecost($_POST['updatecategory']);
    }*/
    echo "<br /><br /><a href='$BASEURL/costs'>Return to costs list</a>";
  }

  function selectcost() {
    global $BASEURL;
    $select = new AnchorTableList("Cost", "Select which user class to view usage costs");
    $select->connectDB("userclass", array("id", "name"));
    $select->list->prepend(array("-1","Create new user class"));
    $select->hrefbase = "$BASEURL/groups/";
    $select->setFormat("id", "%s", array("name"));
    #echo $groupselect->list->text_dump();
    echo $select->display();
  }

  function editCost() {
    $cost = new Cost($this->PD['id']);
    $cost->update($this->PD);
    $cost->checkValid();
    $cost->sync();
    #echo $group->text_dump();
    echo $cost->display();
    if ($cost->id < 0) {
      $submit = "Create new user class";
      $delete = "0";
    } else {
      $submit = "Update entry";
      $delete = "Delete entry";
    }
    echo "<input type='submit' name='submit' value='$submit' />";
    if ($delete) echo "<input type='submit' name='delete' value='$delete' />";
  }
  
  function editcostold($userclass) {
    $i=1;
    if ($userclass != "-1") {
      #$q = "SELECT stdrates.*,costs.*,instruments.name,instruments.longname "
      /*
      $q = "SELECT costs.id AS costid,instrumentclass.id AS instrclassid,"
              ."instrumentclass.name AS instrclassname, "
              ."cost_hour, cost_halfday,cost_fullday,"
              ."userclass.name AS userclassname "
          ."FROM costs "
          ."LEFT JOIN instrumentclass ON instrumentclass.id=costs.instrumentclass "
          ."LEFT JOIN userclass on userclass.id=costs.userclass "
          #."LEFT JOIN stdrates on stdrates.instrid=costs.id "
          #."LEFT JOIN instruments on stdrates.instrid=instruments.id "
          ."WHERE costs.userclass='$userclass' "
          ."ORDER BY instrumentclass.name";
          #."ORDER BY instruments.name";
      */
      $q = "SELECT costs.id AS costid,instrumentclass.id AS instrclassid,"
              ."instrumentclass.name AS instrclassname, "
              ."cost_hour, cost_halfday,cost_fullday,"
              ."userclass.name AS userclassname "
          ."FROM instrumentclass "
          #."LEFT JOIN instrumentclass ON instrumentclass.id=costs.instrumentclass "
          ."LEFT JOIN costs ON instrumentclass.id=costs.instrumentclass "
          ."LEFT JOIN userclass on userclass.id=costs.userclass "
          #."LEFT JOIN stdrates on stdrates.instrid=costs.id "
          #."LEFT JOIN instruments on stdrates.instrid=instruments.id "
          ."WHERE costs.userclass='$userclass' "
          ."ORDER BY instrumentclass.name";
          #."ORDER BY instruments.name";
      #echo $q;
      $sql = mysql_query($q);
      if (! $sql) die (mysql_error());
      $g = mysql_fetch_array($sql);
    } else {
      $q = "SELECT instrumentclass.id AS instrclassid,"
              ."instrumentclass.name AS instrclassname "
          ."FROM instrumentclass "
          ."ORDER BY instrumentclass.name";
      #echo $q;
      $sql = mysql_query($q);
      if (! $sql) die (mysql_error());
      $g = mysql_fetch_array($sql);
    }
    echo "<table>"
        ."<tr><th colspan='2'>Edit/Create User Charge Category</th></tr>";
    echo "<tr><td>Category name</td>
          <td><input type='text' name='userclassname' size='48' value='"
          .$g['userclassname']."' />"
          ."<input type='hidden' name='userclassid' value='"
          .$userclass."' /></td></tr>"
          ."</td></tr>";
    echo "<tr><th colspan='2'>Instruments classes</th></tr>";
    costsInstrumentStdRate($i,$g);
    $i++;
    while ($g = mysql_fetch_array($sql)) {
      costsInstrumentStdRate($i,$g);
      $i++;
    }
    echo <<<END
    <tr><td></td>
    <td>
      <button name='action' type='submit' value='costs'>
        Edit cost schedules
      </button>
      <input type='hidden' name='category' value='1' />
      <input type='hidden' name='updatecategory' value='1' />
    </td></tr>
    </table>
    <p>Looked up existing data using:</p><p>$q</p>
END;
  }

  function costsInstrumentStdRate($i, $g) {
    $q="SELECT name FROM instruments WHERE class='".$g['instrclassid']."' "
       ."LIMIT 4";
    $sql = mysql_query($q);
    if (! $sql) die (mysql_error());
    $instrumentlist = array();
    while ($m = mysql_fetch_array($sql)) {
      $instrumentlist[] = $m['name'];
    }
    $instrlist = implode(", ",$instrumentlist);
      
    echo "\n";
    echo "<input type='hidden' name='class$i-costid' value='".$g['costid']."'>";
    echo "<input type='hidden' name='class$i-classid' value='".$g['instrclassid']."'>";
    echo "<tr><td>".$g['instrclassname']."</td><td>($instrlist)</td></tr>";
    echo "<tr><td>Hour:</td>"
          ."<td><input type='text' name='class$i-hour' size='16' value='".$g['cost_hour']."' /></td></tr>";
    echo "<tr><td>Half day:</td><td><input type='text' name='class$i-halfday' size='16' value='".$g['cost_halfday']."' /></td></tr>";
    echo "<tr><td>Full day:</td><td><input type='text' name='class$i-fullday' size='16' value='".$g['cost_fullday']."' /></td></tr>";
  }

  function updatecost($gpid) {
    if (isset($_POST["userclassid"]) && $_POST["userclassid"] != -1) {
      $q="UPDATE userclass SET name='".$_POST['userclassname']."' WHERE id='".$_POST['userclassid']."'";
      if (!mysql_query($q)) die(mysql_error());
      echoSQL($q);
    } else {
      $q="INSERT INTO userclass (name) VALUES ('".$_POST['userclassname']."')";
      if (!mysql_query($q)) die(mysql_error());
      $_POST["userclassid"]=mysql_insert_id();
      echoSQL($q);
      #echo "id=".$_POST["userclassid"]."\n";
    }
    for ($i=1; isset($_POST["class$i-classid"]); $i++) {
      if (isset($_POST["class$i-costid"]) && $_POST["class$i-costid"]!='') {
        #then it's an update not an insert
        updatesinglerate($i);
      } else {
        #then it's an insert
        insertsinglerate($i);
      }
    }
  }

  function updatesinglerate($i) {
    $q = "UPDATE costs SET "
        ."cost_hour='".$_POST["class$i-hour"]."',"
        ."cost_halfday='".$_POST["class$i-halfday"]."',"
        ."cost_fullday='".$_POST["class$i-fullday"]."' "
        ."WHERE id='".$_POST["class$i-costid"]."'";
    if (!mysql_query($q)) die(mysql_error());
    echoSQL($q, 1);
  }

  function insertsinglerate($i) {
    $q = "INSERT INTO costs "
        ."(instrumentclass,userclass,cost_hour,cost_halfday,cost_fullday) "
        ."VALUES "
        ."("
        ."'".$_POST["class$i-classid"]."','".$_POST["userclassid"]."','".$_POST["class$i-hour"]."','".$_POST["class$i-halfday"]."','".$_POST["class$i-fullday"]."'"
        .")";
    #echo "action: '$q' attempting";
    if (!mysql_query($q)) die(mysql_error());
    echoSQL($q);
  }
}

?> 
