<?php
/**
* Upgrade the database to the latest version used by Bumblebee
*
* @author     Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Installer
*/

require_once 'loadconfig.php';
loadInstalledConfig();

function checkDatabaseVersion(&$data) {
  global $BUMBLEBEEVERSION;
  $data['old_db_version'] = getCurrentDBVersion();
  $data['old_version']    = $data['old_db_version'];
  $data['new_version']    = $BUMBLEBEEVERSION;
  $data['new_db_version'] = $data['new_version']; #substr($data['new_version'], 0, strrpos($data['new_version'], '.'));
  $data['db_upgrade'] = true;

//   return;

  if (version_compare($data['old_db_version'], $data['new_db_version']) == -1) {
    //then the db needs upgrading
    $data['db_upgrade'] = true;
  } else {
    $data["db_upgrade"] = false;
  }
}



// we will need auth rights to alter the structure of the table to actually perform these operations
// as per the installer, we give the option of making an SQL file for the user to make use of using
// either command line tools or phpMyAdmin, or we execute the statements for the user.

function getCurrentDBVersion() {
  global $TABLEPREFIX;
  global $BUMBLEBEEVERSION;
  // there is no defined way of working out what version of the database the installation
  // is using (at least not for anything in the 1.x series). We'll have to guess.
  $structure = db_get_single("SHOW CREATE TABLE {$TABLEPREFIX}users");
  #print "Apparent structure is ".$structure[1]."<br />";
  if (strpos($structure[1], 'utf8') === false) {
    return "1.0";
  }
  if (strpos($structure[1], '`passwd` varchar(50) NOT NULL') === false) {
    return "1.1.3";
  }
  if (strpos($structure[1], 'permissions') === false) {
    return "1.1.4";
  }
  // After v1.1.5, the database should contain the version information so
  // further guesses are not required.
  $conf = ConfigReader::getInstance();
  return $conf->value('meta', 'dbversion', '1.1.4');
}

function makeUpgradeSQL($initial) {
  $u = array();
  $u['1.1']   = "DB_upgrade_BB_1_1";
  $u['1.1.4'] = "DB_upgrade_BB_1_1_passwd";
  $u['1.1.5'] = "DB_upgrade_BB_1_1_permissions";
  #$u['1.2'] = "DB_upgrade_BB_1_2";
  #$u['1.3'] = "DB_upgrade_BB_1_3";
  $u['999.999'] = "DB_upgrade_always";
  $sql = '';
  $releasenotes = array();
  foreach ($u as $version => $function) {
    // print "Considering $version and $initial\n";
    if (version_compare($initial, $version) < 0) {
      // initial version is older than the current version so run the upgrade
      $upgrade = $function();
      $sql .= $upgrade[0];
      $releasenotes[] = $upgrade[1];
    }
  }

  $settingComment = "-- Bumblebee SQL load file for ".$_SERVER['SERVER_NAME']."\n"
                   ."-- date: ".gmdate('r', time())."\n"
                   ."--\n"
                   ."-- Load this file using phpMyAdmin or on the MySQL command line tools:\n"
                   ."--     mysql -p --user someuser < upgrade.sql\n"
                   ."--\n";

  return array($settingComment.$sql, join($releasenotes,'<br />'));
}

function DB_upgrade_BB_1_1() {
  $conf = ConfigReader::getInstance();
  global $TABLEPREFIX;
  $s = "ALTER DATABASE ".$conf->value('database', 'database')." DEFAULT CHARACTER SET utf8;\n";
  $s .= "USE ".$conf->value('database', 'database').";\n";
  $list = array(
          'users'           => array('username', 'name', 'passwd', 'email', 'phone'),
          'projects'        => array('name', 'longname'),
          'groups'          => array('name', 'longname', 'addr1', 'addr2', 'suburb',
                                     'state', 'code', 'country', 'email', 'fax', 'account'),
          'instruments'     => array('name', 'longname', 'location', 'timeslotpicture', 'supervisors'),
          'instrumentclass' => array('name'),
          'userclass'       => array('name'),
          'bookings'        => array('ip', 'comment', 'log'),
          'consumables'     => array('name', 'longname'),
          'consumables_use' => array('ip', 'comment', 'log'),
          'costs'           => '',
          'permissions'     => '',
          'projectgroups'   => '',
          'projectrates'    => '',
          'userprojects'    => '',
          );
  foreach($list as $table => $columns) {
    $s .= "ALTER TABLE $TABLEPREFIX$table DEFAULT CHARACTER SET utf8;\n";
    $s .= "ALTER TABLE $TABLEPREFIX$table CONVERT TO CHARACTER SET utf8;\n";
/*    foreach ($columns as $column) {
      // go via blob to prevent mysql doing a charset conversion
      // http://dev.mysql.com/doc/refman/5.0/en/alter-table.html
      // note that this conversion loses all the column lengths that were defined
      // in the original table but it at least gets users UTF-8 compliant.
      $s .= "ALTER TABLE $TABLEPREFIX$table CHANGE $column $column BLOB;\n"
      $s .= "ALTER TABLE $TABLEPREFIX$table CHANGE $column $column TEXT;\n" */
  }
  $notes = "Note: if you have (somehow) managed to insert non-ASCII data (i.e. characters other than those found on a standard US keyboard) into your database this operation will probably corrupt it. Since previous versions of Bumblebee couldn't do this for you, you would have had to work pretty hard to achieve this.";
  return array($s, $notes);
}

function DB_upgrade_BB_1_1_passwd() {
  $conf = ConfigReader::getInstance();
  global $TABLEPREFIX;
  $s = "USE ".$conf->value('database', 'database').";\n";
  $s .= "ALTER TABLE {$TABLEPREFIX}users CHANGE passwd passwd VARCHAR(50) NOT NULL;\n";
  $notes = "The password field is made larger to accommodate more secure methods for storing encode passwords.";
  return array($s, $notes);
}

function DB_upgrade_BB_1_1_permissions() {
  $conf = ConfigReader::getInstance();
  global $TABLEPREFIX;
  require_once 'inc/permissions.php';
  $default = BBPERM_USER_BASIC | BBPERM_USER_PASSWD;
  $admin = BBPERM_ADMIN_ALL;
  $s = "USE {$conf->value('database', 'database')};\n";
  $s .= "ALTER TABLE {$TABLEPREFIX}users ADD permissions INTEGER UNSIGNED NOT NULL DEFAULT '{$default}';\n";
  $s .= "UPDATE {$TABLEPREFIX}users SET permissions = permissions | {$admin} where isadmin=1;\n";
  $default = BBPERM_INSTR_BASIC;
  $admin = BBPERM_INSTR_ALL;
  $s .= "ALTER TABLE {$TABLEPREFIX}permissions ADD `permissions` INTEGER UNSIGNED NOT NULL DEFAULT '{$default}';\n";
  $s .= "UPDATE {$TABLEPREFIX}permissions SET permissions = permissions | {$admin} where isadmin=1;\n";
  $notes = "The fine-grained permissions system (required for anonymous viewing of the calendars) requires additional database storage.";

  $s .= "CREATE TABLE {$TABLEPREFIX}settings ("
          ."section VARCHAR(64) CHARACTER SET utf8 NOT NULL, "
          ."parameter VARCHAR(64) CHARACTER SET utf8 NOT NULL, "
          ."value TEXT"
       .") DEFAULT CHARACTER SET utf8;\n";
  $notes .= " The new configuration system can store most of your settings in the database to make configuration easier.";

  return array($s, $notes);
}



function DB_upgrade_always() {
  global $BUMBLEBEEVERSION;

  $s .= "DELETE FROM {$TABLEPREFIX}settings WHERE section='meta' AND parameter='dbversion';\n";
  $s .= "DELETE FROM {$TABLEPREFIX}settings WHERE section='meta' AND parameter='configuredversion';\n";
  $s .= "INSERT INTO {$TABLEPREFIX}settings VALUES ('meta', 'dbversion', '$BUMBLEBEEVERSION');\n";
  $s .= "INSERT INTO {$TABLEPREFIX}settings VALUES ('meta', 'configuredversion', '$BUMBLEBEEVERSION');\n";

  $notes = "Update the database meta-data.";

  return array($s, $notes);
}

?>
