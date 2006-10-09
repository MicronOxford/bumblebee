<?php
/**
* Upgrade the database to the latest version used by Bumblebee
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Installer
*/


function getSetupDefaults() {
  $defaults = array();
  $defaults['sqlTablePrefix'] = '';
  $defaults['sqlDefaultHost'] = 'localhost';
  $defaults['sqlHost']        = $defaults['sqlDefaultHost'];
  $defaults['sqlDefaultDB']   = 'bumblebeedb';
  $defaults['sqlDB']          = $defaults['sqlDefaultDB'];
  $defaults['sqlDefaultUser'] = 'bumblebee';
  $defaults['sqlUser']        = $defaults['sqlDefaultUser'];
  $defaults['sqlDefaultPass'] = 'bumblebeepass';
  $defaults['sqlPass']        = $defaults['sqlDefaultPass'];
  $defaults['sqlUseDropTable'] = '1';
  
  $defaults['bbDefaultAdmin']     = 'BumblebeeAdmin';
  $defaults['bbDefaultAdminName'] = 'Queen Bee';
  $defaults['bbDefaultAdminPass'] = 'defaultpassword123';
  $defaults['bbAdmin']            = $defaults['bbDefaultAdmin'];
  $defaults['bbAdminName']        = $defaults['bbDefaultAdminName'];
  $defaults['bbAdminPass']        = $defaults['bbDefaultAdminPass'];

  return $defaults;
}

/**
* Work out an SQL load file from the defaults and the user input
*/
function constructSQL($source, $replacements, $includeAdmin) {
  $sqlSourceFile = $source;
    
  $sqlTablePrefix       = $replacements['sqlTablePrefix'];
  $sqlDefaultHost       = $replacements['sqlDefaultHost'];
  $sqlHost              = $replacements['sqlHost'];
  $sqlDefaultDB         = $replacements['sqlDefaultDB'];
  $sqlDB                = $replacements['sqlDB'];
  $sqlDefaultUser       = $replacements['sqlDefaultUser'];
  $sqlUser              = $replacements['sqlUser'];
  $sqlDefaultPass       = $replacements['sqlDefaultPass'];
  $sqlPass              = $replacements['sqlPass'];
  $sqlUseDropTable      = $replacements['sqlUseDropTable'];
  $bbDefaultAdmin       = $replacements['bbDefaultAdmin'];
  $bbDefaultAdminName   = $replacements['bbDefaultAdminName'];
  $bbDefaultAdminPass   = $replacements['bbDefaultAdminPass'];
  $bbAdmin              = $replacements['bbAdmin'];
  $bbAdminName          = $replacements['bbAdminName'];
  $bbAdminPass          = $replacements['bbAdminPass'];

  $sql = file($sqlSourceFile);
  
  $sql = preg_replace("/(DELETE .+ WHERE User=')$sqlDefaultUser';/",
                      "$1$sqlUser';", $sql);
  $sql = preg_replace("/(INSERT INTO user .+)'$sqlDefaultHost','$sqlDefaultUser',\s*PASS.+\)(.+);/",
                      "$1'$sqlHost','$sqlUser',PASSWORD('$sqlPass')\$2;", $sql);
  // GRANT OR REVOKE PRIVS
  $sql = preg_replace("/(.+ ON) $sqlDefaultDB\.\* (TO|FROM) $sqlDefaultUser@$sqlDefaultHost;/",
                      "\$1 $sqlDB.* \$2 $sqlUser@$sqlHost;", $sql);
  // REVOKE ALL PRIVILEGES ON *.* FROM bumblebee;
  // REVOKE GRANT OPTION ON *.* FROM bumblebee;
  $sql = preg_replace("/(REVOKE .+ FROM) $sqlDefaultUser;/",
                      "\$1 $sqlUser;", $sql);
  // CREATE OR DROP DATABASE                     
  $sql = preg_replace("/^(.+) DATABASE(.*) $sqlDefaultDB/",
                      "\$1 DATABASE\$2 $sqlDB", $sql);
  $sql = preg_replace("/USE $sqlDefaultDB;/",
                      "USE $sqlDB;", $sql);

  if($sqlUseDropTable == '1') {
        $sql = preg_replace("/DROP TABLE IF EXISTS (.+)?;/",
                      "DROP TABLE IF EXISTS $sqlTablePrefix\$1;", $sql);
  } else {
        $sql = preg_replace("/DROP TABLE IF EXISTS (.+)?;/i", '', $sql);
        $sql = preg_replace("/DROP DATABASE IF EXISTS (.+)?;/i", '', $sql);
  }

  $sql = preg_replace("/CREATE TABLE (.+)? /",
                      "CREATE TABLE $sqlTablePrefix\$1 ", $sql);
  // make the admin user
  $sql = preg_replace("/INSERT INTO (users)/",
                      "INSERT INTO $sqlTablePrefix\$1", $sql);
  
  $sql = preg_replace("/\('$bbDefaultAdmin','$bbDefaultAdminName',MD5\('$bbDefaultAdminPass'\),1\)/",
                      "('$bbAdmin','$bbAdminName','".md5($bbAdminPass)."',1);", $sql);
  $sql = preg_replace('/^(.*?)--.*$/',
                      '$1', $sql);
  $sql = preg_grep('/^\s*$/', $sql, PREG_GREP_INVERT);
  
  $stream = join($sql,'');
  if (! $includeAdmin) {
    $stream = substr($stream, strpos($stream, "USE $sqlDB"));
    $stream = "-- SQL user and database creation code removed as per user request.\n".$stream;
  }
  
  $settingComment = "-- Bumblebee SQL load file for ".$_SERVER['SERVER_NAME']."\n"
                   ."-- date: ".date('r', time())."\n"
                   ."-- sourced from $sqlSourceFile\n"
                   ."-- database: $sqlDefaultDB => $sqlDB\n"
                   ."-- table prefix: $sqlTablePrefix\n"
                   ."--\n"
                   ."-- Load this file using phpMyAdmin or on the MySQL command line tools:\n"
                   ."--     mysql -p --user someuser < tables.sql\n"
                   ."--\n";

  return $settingComment.$stream;
}

?>
