<?
# $Id$
# database connection scripts

$db_ini = parse_ini_file('db.ini');
$dbhost = $db_ini['host'];
$dbusername = $db_ini['username'];
$dbpasswd = $db_ini['passwd'];
$dbname = $db_ini['database'];

$connection = mysql_pconnect($dbhost, $dbusername, $dbpasswd)
    or die ('Couldn\'t connect to server.');
$db = mysql_select_db($dbname, $connection)
    or die('Couldn\'t select database.');

include_once('inc/dbforms/sql.php');

?> 
