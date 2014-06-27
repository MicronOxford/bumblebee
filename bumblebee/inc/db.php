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

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

require_once 'inc/bb/configreader.php';
require_once 'inc/menu.php';

$conf = & ConfigReader::getInstance();
$conf->MergeFile('db.ini');

/**
* $TABLEPREFIX is added to the beginning of all SQL table names to allow
* database sharing.
* @global string $TABLEPREFIX
*/
$TABLEPREFIX = $conf->value('database', 'tableprefix');

/**
* import SQL functions for database lookups
*/
require_once 'inc/formslib/sql.php';

$DB_CONNECT_DEBUG = isset($DB_CONNECT_DEBUG) ? $DB_CONNECT_DEBUG : false;
$NON_FATAL_DB     = isset($NON_FATAL_DB)     ? $NON_FATAL_DB     : false;

$dns = 'mysql:'
     . 'host=' . $conf->value('database', 'host') . ';'
     . 'dbname=' . $conf->value('database', 'database') . ';'
     . 'charset=utf8';

try
{
  $DBH = new PDO(
      $dns,
      $conf->value('database', 'username'),
      $conf->value('database', 'passwd'),
      array(PDO::ATTR_PERSISTENT => true)
  );
  $conf->status->database = true;
}
catch (PDOException $e)
{
  $i_errmsg = <<<'END'
<p>Sorry, I couldn't connect to the database, so there's nothing I can
presently do. This could be due to a booking system misconfiguration, or a
failure of the database subsystem.</p><p>If this persists, please contact
the <a href="mailto:%s">booking system administrator</a>.</p>
END;
  $errmsg = sprintf(T_($i_errmsg), $conf->AdminEmail);

  if ($DB_CONNECT_DEBUG)
    {
      $errmsg .= mysql_error()
               . '<br />Connected using parameters <pre>'
               . print_r($conf->getSection('database'), true) . '</pre>';
    }
  $conf->status->database = false;
  $conf->status->offline = true;

  $conf->status->messages[] = $errmsg;

  if ($NON_FATAL_DB)
    trigger_error($errmsg, E_USER_NOTICE);
}

?>
