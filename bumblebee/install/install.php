<?php
/**
* Simple Bumblebee installer -- creates an SQL and ini files from user input
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Installer
*/

$sqlSourceFile = 'setup-tables.sql';
$sqlSetupFilename = 'bumblebee.sql';
$iniSourceFile = 'db.ini';
$iniSetupFilename = 'db.ini';

$defaults['sqlTablePrefix'] = '';
$defaults['sqlDefaultHost'] = 'localhost';
$defaults['sqlHost']        = $defaults['sqlDefaultHost'];
$defaults['sqlDefaultDB']   = 'bumblebeedb';
$defaults['sqlDB']          = $defaults['sqlDefaultDB'];
$defaults['sqlDefaultUser'] = 'bumblebee';
$defaults['sqlUser']        = $defaults['sqlDefaultUser'];
$defaults['sqlDefaultPass'] = 'bumblebeepass';
$defaults['sqlPass']        = $defaults['sqlDefaultPass'];

$defaults['bbDefaultAdmin']     = 'BumblebeeAdmin';
$defaults['bbDefaultAdminName'] = 'Queen Bee';
$defaults['bbDefaultAdminPass'] = 'defaultpassword123';
$defaults['bbAdmin']            = $defaults['bbDefaultAdmin'];
$defaults['bbAdminName']        = $defaults['bbDefaultAdminName'];
$defaults['bbAdminPass']        = $defaults['bbDefaultAdminPass'];





if (! isset($_POST['submitsql']) && ! isset($_POST['submitini'])) {
  printUserForm($defaults);
} elseif (isset($_POST['submitini'])) {
  $userSubmitted = array_merge($defaults, $_POST);
  $s = constructini($iniSourceFile, $userSubmitted);
  outputTextFile($iniSetupFilename, $s);
} elseif (isset($_POST['submitsql'])) {
  $userSubmitted = array_merge($defaults, $_POST);
  $s = constructSQL($sqlSourceFile, $userSubmitted);
  outputTextFile($sqlSetupFilename, $s);
}



/**
* Work out an SQL load file from the defaults and the user input
*/
function constructSQL($source, $replacements) {
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
  $sql = preg_replace("/^(.+) DATABASE(.*) $sqlDefaultDB;/",
                      "\$1 DATABASE \$2 $sqlDB;", $sql);
  $sql = preg_replace("/USE $sqlDefaultDB;/",
                      "USE $sqlDB;", $sql);
  $sql = preg_replace("/DROP TABLE IF EXISTS (.+)?;/",
                      "DROP TABLE IF EXISTS $sqlTablePrefix\$1;", $sql);
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
  
  
  
  $settingComment = "-- date: ".date('r', time())."\n"
                  ."-- sourced from $sqlSourceFile\n"
                  ."-- database: $sqlDefaultDB => $sqlDB\n"
                  ."-- table prefix: $sqlTablePrefix\n"
                  ."-- database: $sqlDefaultDB => $sqlDB\n"
                  ."--\n"
                  ."-- Load this file using phpMyAdmin or on the command line:"
                  ."-- mysql -p --user someuser < tables.sql\n";

  array_unshift($sql, $settingComment);
  $stream = join($sql,'');
  return $stream;
}

/**
* Work out a db.ini from the defaults and the user input
*/
function constructini($source, $defaults) {
  $eol = "\n";
  $s = '[database]'.$eol
      .'host = "'.$defaults['sqlHost'].'"'.$eol
      .'username = "'.$defaults['sqlUser'].'"'.$eol
      .'passwd = "'.$defaults['sqlPass'].'"'.$eol
      .'database = "'.$defaults['sqlDB'].'"'.$eol
      .'tableprefix = "'.$defaults['sqlTablePrefix'].'"'.$eol;
  return $s;
}

/**
* Dump the generated file to the user to save and upload to the server
*/
function outputTextFile($filename, $stream) {
  // Output a text file
  header("Content-type: text/plain"); 
  header("Content-Disposition: attachment; filename=$filename");                    
  echo $stream;
}

/**
* Find out from the user what username and passwords to use for connecting to the database etc
*/
function printUserForm($defaults) {
  ?>
<html>
  <head>
    <title>Bumblebee setup</title>
  </head>
  <body>
  <h1>Bumblebee Setup Script</h1>
  <p>Please delete install.php after loading the generated SQL file into MySQL.</p>
  <form action='install.php' method='POST'>
  
  <table>
  <tr>
    <td>MySQL host</td>
    <td><input type='text' name='sqlHost' value='<?=$defaults[sqlDefaultHost]?>' /></td>
  </tr>
  <tr>
    <td>MySQL database</td>
    <td><input type='text' name='sqlDB' value='<?=$defaults[sqlDefaultDB]?>' /></td>
  </tr>
  <tr>
    <td>MySQL table prefix</td>
    <td><input type='text' name='sqlTablePrefix' value='' /></td>
  </tr>
  <tr>
    <td>MySQL username</td>
    <td><input type='text' name='sqlUser' value='<?=$defaults[sqlDefaultUser]?>' /></td>
  </tr>
  <tr>
    <td>MySQL user password</td>
    <td><input type='text' name='sqlPass' value='<?=$defaults[sqlDefaultPass]?>' /></td>
  </tr>
  <tr>
    <td>Bumblebee admin user</td>
    <td><input type='text' name='bbAdmin' value='<?=$defaults[bbDefaultAdmin]?>' /></td>
  </tr>
  <tr>
    <td>Bumblebee admin password</td>
    <td><input type='text' name='bbAdminPass' value='<?=$defaults[bbDefaultAdminPass]?>' /></td>
  </tr>
  <tr>
    <td>Bumblebee admin user's real name</td>
    <td><input type='text' name='bbAdminName' value='<?=$defaults[bbDefaultAdminName]?>' /></td>
  </tr>
  <tr>
    <td><input type='submit' name='submitsql' value='Generate SQL file' /></td>
    <td><input type='submit' name='submitini' value='Generate db.ini file' /></td>
  </tr>
  </table>
  
  </form>
  </body>
</html>

  <?php

}

?>
