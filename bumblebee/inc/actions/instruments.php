<?php
# $Id$
# edit the instruments

function actionInstruments()
{
  if (! isset($_POST['editinstrument'])) {
    selectInstruments();
  } elseif (! isset($_POST['updateinstrument'])) {
    editInstruments($_POST['editinstrument']);
  } elseif ($_POST['delete'] == 1) {
    deleteInstruments($_POST['editinstrument']);
  } elseif ($_POST['editinstrument'] == -1) {
    insertInstruments();
  } else {
    updateInstruments($_POST['updateinstrument']);
  }
}

function selectInstruments()
{
  echo <<<END
  <table>
  <tr><th>Select instrument to view/edit</th></tr>
  <tr><td>
END;
  instrumentSelectBox("editinstrument","create new instrument");
  echo <<<END
  </td></tr>
  <tr><td>
    <button name="action" type="submit" value="instruments">
      Edit/create instruments
    </button>
  </td></tr>
  </table>
END;
}

function instrumentSelectBox($name,$firstline) {
  echo "<select name='$name'><option value='-1'>--- $firstline</option>";
  $q = "SELECT id,name,longname "
      ."FROM instruments "
      ."ORDER BY name";
  $sql = mysql_query($q);
  if (! $sql) die (mysql_error());
  while ($row = mysql_fetch_row($sql))
  {
    echo "<option value='$row[0]'>$row[1] ($row[2])</option>";
  }                                    
  echo "</select>";
}


function editInstruments($gpid) {
  if ($gpid > 0) {
    $q = "SELECT * "
        ."FROM instruments "
        ."WHERE id='$gpid'";
    $sql = mysql_query($q);
    if (! $sql) die (mysql_error());
    $g = mysql_fetch_array($sql);
  }

  echo "<table>"
      ."<tr><th>Edit/Create Instruments</th></tr>";
  echo "<tr><td>Short name</td>
        <td><input type='text' name='name' size='48' value='".$g['name']."' /></td></tr>";
  echo "<tr><td>Long name</td>
        <td><input type='text' name='longname' size='48' value='".$g['longname']."' /></td></tr>"
      ."<tr><td>Location</td>";
  echo "<td><input type='text' name='location' size='48' value='".$g['location']."' /></td></tr>\n";

  echo "<tr><td>Instrument class</td><td>";
  $qc = "SELECT id,name FROM instrumentclass ORDER BY name";
  $sqlc = mysql_query($qc);
  if (! $sqlc) die (mysql_error());
  while ($gc = mysql_fetch_array($sqlc)) {
    $class = $gc['id'];
    $qi = "SELECT name FROM instruments WHERE class='$class' ORDER BY name LIMIT 4";
    $sqli = mysql_query($qi);
    if (! $sqli) die (mysql_error());
    $instrs=array();
    while ($gi = mysql_fetch_array($sqli)) {
      $instrs[]=$gi['name'];
    }
    $instrlist = implode(", ", $instrs);
    echo "<label><input type='radio' name='class' value='$class' />"
        .$gc['name'] ." ($instrlist)</label><br />\n";
  }
  echo "<label><input type='radio' name='class' value='-1' checked='checked' />"
      ."Create new class</label> "
      ."<input type='text' name='newclassname' size='24' />\n";
  echo "</td></tr>\n";

  echo "<tr><td>
    <input type='checkbox' name='delete' value='1'> Delete instrument</input>
  </td>
  <td>
    <button name='action' type='submit' value='instruments'>
      Edit/create instrument
    </button>
    <input type='hidden' name='editinstrument' value='$gpid' />
    <input type='hidden' name='updateinstrument' value='$gpid' />
  </td></tr>
  </table>
";
}

function deleteInstruments($gpid) {
  $q = "DELETE FROM instruments WHERE id='$gpid'";
  if (!mysql_query($q)) die(mysql_error());
  echo "action: '$q' successful";
}

function insertInstruments() {
  $class = checkClassInfo();
  $q = "INSERT INTO instruments "
      ."(name,longname,location,class) "
      ."VALUES "
      ."("
      ."'".$_POST['name']."','".$_POST['longname']."','".$_POST['location']."','$class'"
      .")";
  if (!mysql_query($q)) die(mysql_error());
  echoSQL($q, 1);
}

function updateInstruments($gpid) {
  $class = checkClassInfo();
  $q = "UPDATE instruments SET "
      ."name='".$_POST['name']."',"
      ."longname='".$_POST['longname']."',"
      ."location='".$_POST['location']."', "
      ."class='".$class."' "
      ."WHERE id='$gpid'";
  if (!mysql_query($q)) die(mysql_error());
  echoSQL($q, 1);
}

function checkClassInfo() {
  if ($_POST['class'] == -1) {
    $class = createNewInstrumentClass();
    #getNewClassInfo($class);
  } else {
    $class = $_POST['class'];
  }
  return $class;
}

function createNewInstrumentClass() {
  $q = "INSERT INTO instrumentclass (name) VALUES ('".$_POST['newclassname']."')";
  if (!mysql_query($q)) die(mysql_error());
  echoSQL($q, 1);
  $class = mysql_insert_id();
  return $class;
}

?> 
