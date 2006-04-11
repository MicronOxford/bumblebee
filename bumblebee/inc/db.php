<?php
/**
* Database connection script
*
* Parses the {@link db.ini } file and connects to the database. 
* If the db login doesn't work then die() as there is no point in continuing
* without a database connection.
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Misc
*/

$db_ini = parse_ini_file($CONFIGLOCATION.'db.ini');
$CONFIG['database']['dbhost']     = $db_ini['host'];
$CONFIG['database']['dbusername'] = $db_ini['username'];
$CONFIG['database']['dbpasswd']   = $db_ini['passwd'];
$CONFIG['database']['dbname']     = $db_ini['database'];

/**
* $TABLEPREFIX is added to the beginning of all SQL table names to allow database sharing
* @global string $TABLEPREFIX 
*/
$TABLEPREFIX = $db_ini['tableprefix'];

$dberrmsg = sprintf(T_('<p>Sorry, I couldn\'t connect to the database, so there\'s nothing I can presently do. This could be due to a booking system misconfiguration, or a failure of the database subsystem.</p><p>If this persists, please contact the <a href="mailto:%s">booking system administrator</a>.</p>'), $ADMINEMAIL);

$DB_CONNECT_DEBUG = 0;

$connection = mysql_pconnect($CONFIG['database']['dbhost'], 
                             $CONFIG['database']['dbusername'], 
                             $CONFIG['database']['dbpasswd'])
              or die ($dberrmsg.($DB_CONNECT_DEBUG ? mysql_error() : ''));
$db = mysql_select_db($CONFIG['database']['dbname'], $connection)
              or die ($dberrmsg.($DB_CONNECT_DEBUG ? mysql_error() : ''));

/**
* import SQL functions for database lookups
*/
require_once 'inc/formslib/sql.php';

?> 
