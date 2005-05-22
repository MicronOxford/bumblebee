<?php
# $Id$
# database connection scripts

$db_ini = parse_ini_file($CONFIGLOCATION.'db.ini');
$CONFIG['database']['dbhost']     = $db_ini['host'];
$CONFIG['database']['dbusername'] = $db_ini['username'];
$CONFIG['database']['dbpasswd']   = $db_ini['passwd'];
$CONFIG['database']['dbname']     = $db_ini['database'];
$TABLEPREFIX = $db_ini['tableprefix'];

$dberrmsg = '<p>Sorry, I couldn\'t connect to the database, '
           .'so there\'s nothing I can presently do. '
           .'This could be due to a booking system misconfiguration, or a failure of '
           .'the database subsystem.</p>'
           .'<p>If this persists, please contact the '
           .'<a href="mailto:'.$ADMINEMAIL.'">booking system administrator</a>.</p>';

$DB_CONNECT_DEBUG = 0;

$connection = mysql_pconnect($CONFIG['database']['dbhost'], 
                             $CONFIG['database']['dbusername'], 
                             $CONFIG['database']['dbpasswd'])
              or die ($dberrmsg.($DB_CONNECT_DEBUG ? mysql_error() : ''));
$db = mysql_select_db($CONFIG['database']['dbname'], $connection)
              or die ($dberrmsg.($DB_CONNECT_DEBUG ? mysql_error() : ''));

include_once 'inc/formslib/sql.php';

?> 
