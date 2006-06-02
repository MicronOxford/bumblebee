<?php
/**
* Load an SQL stream into the database
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Installer
*/

/**
* Load the generated SQL into the database one command at a time
*/
function loadSQL($sql, $host, $username, $passwd) {
  #echo "Loading SQL";
  if ($connection = @ mysql_pconnect($host, $username, $passwd)) {
    // then we successfully logged on to the database
    $sqllist = preg_split('/;/', $sql);
    foreach ($sqllist as $q) {
      #echo "$q\n";
      if (preg_match('/^\s*$/', $q)) continue;
      $handle = mysql_query($q);
      if (! $handle) {
        return "ERROR: I had trouble executing SQL statement:"
              ."<blockquote>$q</blockquote>"
              ."MySQL said:<blockquote>"
              .mysql_error()
              ."</blockquote>";
      }
    }
    return "SQL file loaded correctly";
  } else {
    return "ERROR: Could not log on to database to load SQL file: ".mysql_error() ;
  }
}

?>
