<?php
# $Id$
# the main menu for a user

function actionMain()
{
  echo "FIXME: DEAD CODE IS NOT DEAD. LONG LIVE THE CODE!";
  global $ISADMIN, $UID;

  echo <<<END
  <h1>Welcome</h1>
  <table>
  <tr><th>Select instrument to view</th></tr>
  <tr><td>
    <select name="instrument">
END;

      $q = "SELECT instruments.id,instruments.name "
          ."FROM instruments "
          ."LEFT JOIN permissions ON instruments.id=permissions.instrid "
          ."WHERE userid='".$UID."'";
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
    <button name="action" type="submit" value="view">
      Select instrument
    </button>
  </td></tr>
  </table>

END;
}
?> 
