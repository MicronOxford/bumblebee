<?
# $Id$
# database connection scripts
$dbhost = 'localhost';
$dbusername = 'babs';
$dbpasswd = 'BABSuser123';
$dbname = 'db_babs';

$connection = mysql_pconnect("$dbhost","$dbusername","$dbpasswd")
    or die ("Couldn't connect to server.");
$db = mysql_select_db("$dbname", $connection)
    or die("Couldn't select database.");

function echoSQL($echo, $success=0) {
  global $VERBOSESQL;
  if ($VERBOSESQL) {
    echo "<div class='sql'>$echo "
        .($success ? "<div>(successful)</div>" : "")
        ."</div>";
  }
}
  
?> 
