<?php
# $Id$
# edit the groups

  function actionCosts()
  {
    if (! isset($_POST['category'])) {
      selectcost();
    } elseif (! isset($_POST['updatecategory'])) {
      editcost($_POST['category']);
    #} elseif ($_POST['delete'] == 1) {
    #  deletecost($_POST['category']);
    } else {
      updatecost($_POST['updatecategory']);
    }
  }

  function selectcost()
  {
    echo <<<END
    <table>
    <tr><th>Select cost category to view/edit</th></tr>
    <tr><td>
      <select name="category">
        <option value='-1'>--- Create new category</option>
END;
        $q = "SELECT id,name "
            ."FROM userclass "
            #."LEFT JOIN costs ON (costs.id=stdrates.costid) "
            ."ORDER BY name ";
        $sql = mysql_query($q);
        if (! $sql) die (mysql_error());
        while ($row = mysql_fetch_row($sql))
        {
          echo "<option value='$row[0]'>$row[1]</option>";
        }                                    
    echo <<<END
      </select>
    </td></tr>
    <tr><td>
      <button name="action" type="submit" value="costs">
        Edit/create category
      </button>
    </td></tr>
    </table>
END;
  }

  function editcost($userclass)
  {
    /*
    $q = "SELECT name FROM userclass WHERE id='$userclass'";
    $sql = mysql_query($q);
    if (! $sql) die (mysql_error());
    $g = mysql_fetch_array($sql);
    $userclassname = $g['name'];
    */
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
    #while ($gpid != "-1" && ($g = mysql_fetch_array($sql))) {
    while ($g = mysql_fetch_array($sql)) {
      costsInstrumentStdRate($i,$g);
      $i++;
    }
    /*
    for ($j=0; $j<2; $j++) {
      costsInstrumentStdRate($i,array());
      $i++;
    }*/

    /*
    <tr><td>
      <input type='checkbox' name='delete' value='1'> Delete category</input>
    </td>
    */
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
      
    #if (isset($g['instrclassname'])) {
      echo "\n";
      echo "<input type='hidden' name='class$i-costid' value='".$g['costid']."'>";
      echo "<input type='hidden' name='class$i-classid' value='".$g['instrclassid']."'>";
      #echo "<tr><td>".$g['instrclassname']."</td><td>".$g['longname']."</td></tr>";
      echo "<tr><td>".$g['instrclassname']."</td><td>($instrlist)</td></tr>";
    #} else {
      #echo "<tr><td>Select:</td><td>";
      ##instrumentSelectBox("instr$i-instrid","select instrument");
      #echo "</td></tr>";
    #}
    echo "<tr><td>Hour:</td>"
          ."<td><input type='text' name='class$i-hour' size='16' value='".$g['cost_hour']."' /></td></tr>";
    echo "<tr><td>Half day:</td><td><input type='text' name='class$i-halfday' size='16' value='".$g['cost_halfday']."' /></td></tr>";
    echo "<tr><td>Full day:</td><td><input type='text' name='class$i-fullday' size='16' value='".$g['cost_fullday']."' /></td></tr>";
    /*if (isset($g['name'])) {
      echo "<tr><td></td><td>"
          ."<input type='checkbox' name='class$i-delete' value='1'> Delete this rate</input>"
          ."</td></tr>";
    }*/
  }

/*
  function deletecosts($gpid)
  {
    $q = "DELETE FROM stdrates WHERE category='$gpid'";
    if (!mysql_query($q)) die(mysql_error());
    echo "action: '$q' successful";
  }
  */

  function updatecost($gpid) {
    if (isset($_POST["userclassid"]) && $_POST["userclassid"] != -1) {
      $q="UPDATE userclass SET name='".$_POST['userclassname']."' WHERE id='".$_POST['userclassid']."'";
      if (!mysql_query($q)) die(mysql_error());
      echo "<div class='sql'>$q</div>";
    } else {
      $q="INSERT INTO userclass (name) VALUES ('".$_POST['userclassname']."')";
      if (!mysql_query($q)) die(mysql_error());
      $_POST["userclassid"]=mysql_insert_id();
      echo "<div class='sql'>$q</div>";
      echo "id=".$_POST["userclassid"]."\n";
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

  /*
  function deletesinglerate($i) {
    $q = "DELETE FROM stdrates WHERE id='".$_POST["class$i-id"]."'";
    if (!mysql_query($q)) die(mysql_error());
    echo "action: '$q' successful";
  }*/
  
  function updatesinglerate($i) {
    $q = "UPDATE costs SET "
        ."cost_hour='".$_POST["class$i-hour"]."',"
        ."cost_halfday='".$_POST["class$i-halfday"]."',"
        ."cost_fullday='".$_POST["class$i-fullday"]."' "
        ."WHERE id='".$_POST["class$i-costid"]."'";
    if (!mysql_query($q)) die(mysql_error());
    echo "<div class='sql'>action: '$q' successful</div>";
  }

  function insertsinglerate($i)
  {
    $q = "INSERT INTO costs "
        ."(instrumentclass,userclass,cost_hour,cost_halfday,cost_fullday) "
        ."VALUES "
        ."("
        ."'".$_POST["class$i-classid"]."','".$_POST["userclassid"]."','".$_POST["class$i-hour"]."','".$_POST["class$i-halfday"]."','".$_POST["class$i-fullday"]."'"
        .")";
    #echo "action: '$q' attempting";
    if (!mysql_query($q)) die(mysql_error());
    echo "<div class='sql'>action: '$q' successful</div>";
  }

?> 
