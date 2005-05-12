<?
# $Id$
# database connection scripts

$db_ini = parse_ini_file($CONFIGLOCATION.'db.ini');
$CONFIG['database']['dbhost']     = $db_ini['host'];
$CONFIG['database']['dbusername'] = $db_ini['username'];
$CONFIG['database']['dbpasswd']   = $db_ini['passwd'];
$CONFIG['database']['dbname']     = $db_ini['database'];
$TABLEPREFIX = $db_ini['tableprefix'];

$connection = mysql_pconnect($CONFIG['database']['dbhost'], 
                             $CONFIG['database']['dbusername'], 
                             $CONFIG['database']['dbpasswd'])
    or die ('Couldn\'t connect to server.');
$db = mysql_select_db($CONFIG['database']['dbname'], $connection)
    or die('Couldn\'t select database.');

include_once 'inc/formslib/sql.php';

?> 
