<?php
/**
* Collate some stats on the current usage of the system (number of bookings etc)
*
* @author     Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Misc
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

/**
* Collate some stats on the current usage of the system (number of bookings etc)
*
* @package    Bumblebee
* @subpackage Misc
*/
class SystemStats {
  /**
  * numbers of rows in each table ($table => $num_rows)
  * @var array
  */
  var $stats;
  /**
  * tables on which stats should be compiled
  * @var array
  */
  var $tables;

  /**
  * Constructor: load up the stats
  */
  function SystemStats() {
    $tables = array('users', 'projects', 'instruments', 'bookings');
    foreach ($tables as $t) {
      $this->stats[$t]       = $this->countEntries($t);
    }
  }

  /**
  * Runs the SQL count(*) query to find out how many rows in the table
  * @param string $table the table to query
  */
  function countEntries($table) {
    $row = quickSQLSelect($table, '', '', 0, 1);
    return $row[0];
  }

  /**
  * Return the stats for the designated table
  * @param string $table the table to query
  */
  function get($table) {
    return $this->stats[$table];
  }
}

/**
* @return string name of the server software
*/
function webserver_get_name() {
  if (! isset($_SERVER['SERVER_SOFTWARE'])) return "";
  return substr($_SERVER['SERVER_SOFTWARE'], 0, strpos($_SERVER['SERVER_SOFTWARE'], '/'));
}

/**
* @return string version of the server software
*/
function webserver_get_version() {
  if (! isset($_SERVER['SERVER_SOFTWARE'])) return "";
  $slash   = strpos($_SERVER['SERVER_SOFTWARE'], '/');
  $version = substr($_SERVER['SERVER_SOFTWARE'], $slash+1);
  $space   = strpos($version, ' ');
  if ($space !== false) $version = substr($version, 0, $space);
  return $version;
}
?>
