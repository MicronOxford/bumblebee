<?
# $Id$
# database connection scripts
$dbhost = 'localhost';
$dbusername = 'bumblebee';
$dbpasswd = 'BABSuser123';
$dbname = 'bumblebeedb';

$connection = mysql_pconnect($dbhost, $dbusername, $dbpasswd)
    or die ('Couldn\'t connect to server.');
$db = mysql_select_db($dbname, $connection)
    or die('Couldn\'t select database.');

include_once('inc/dbforms/sql.php');

?> 
