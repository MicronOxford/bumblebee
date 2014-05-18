<?php
/**
* Configure some settings for the user
*
* @author     Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Installer
*/


function updateUserSettings($values) {
  $NON_FATAL_DB = true;
  $DB_CONNECT_DEBUG = true;
  require_once 'inc/db.php';
  require_once 'inc/passwords.php';

  $settings = new SettingsCustomise($values);

  $conf = ConfigReader::getInstance();
  $conf->MergeDatabaseTable();
  $conf->ParseConfig();
  $settings->conf = $conf;

  $settings->customise();


  return parseTests($settings->status_message);
}

class SettingsCustomise {
  
  var $_db_message;
  var $_values;

  var $conf;
  var $status_message;
  

  function SettingsCustomise($values) {
    $this->_values = $values;
  }
  
  function customise() {
    $this->change_settings();
  }

  function change_settings() {
    $results = array();
    if (isset($this->_values['makeAnonymous']) && $this->_values['makeAnonymous']) {
      $results[] = $this->make_anonymous();
    }
    $results[] = $this->update_db_setting($this->_values, 'locale',   'language', 'locale');
    $results[] = $this->update_db_setting($this->_values, 'timezone', 'language', 'timezone');
  
    $this->status_message = $results;
  }
  
  function update_db_setting($data, $key, $section, $param) {
    // check the user entered data that is different to the current setting
    if (isset($data[$key]) && $this->conf->value($section, $param) != $data[$key]) {
      $sql = $this->make_setting($section, $param, $data[$key]);
      $status = $this->apply_db_changes($sql);
      if ($status == STATUS_NOOP) return;
      if ($status & STATUS_ERR) {
        return "ERROR: Could not update setting for $section.$param: "
                  .$this->_db_message;
      } else {
        return "GOOD: $section.$param updated successfully.";
      }
    }
  }
  
  function make_setting($section, $param, $value) {
    $del = 'DELETE FROM settings WHERE '
              .'section='.qw($section).' AND parameter='.qw($param);
    $ins = 'INSERT INTO settings SET '
              .'section='.qw($section).','
              .'parameter='.qw($param).','
              .'value='.qw($value);
    return array($del, $ins);
  }
  
  function make_anonymous() {
    $username = 'anonymous@local';
    $name     = 'Anonymous browsing user';
    $password = md5(uniqid(rand()).time().microtime());    # hash together a few things to make a random password
  
    $email    = $this->conf->value('main', 'AdminEmail');
    $phone    = '0987654321';
  
    $sql = array();
    $sql[] = 'INSERT INTO users SET '
                .'username='.qw($username).','
                .'name='.qw($name).','
                .'passwd='.qw(makePasswordHash($password)).','
                .'email='.qw($email).','
                .'phone='.qw($phone);
    $sql[] = $this->make_setting('display', 'AnonymousUsername', $username);
    $sql[] = $this->make_setting('display', 'AnonymousPassword', $password);
    $sql[] = $this->make_setting('display', 'LoginPage', 
        "<div style='float:right; margin-top:2em; padding: 1em; width:14em; border:1px solid black'>"
        ."Alternatively, you can "
        ."<a href='?anonymous'>browse instruments and calendars</a></div>");

    $status = $this->apply_db_changes($sql);
    if ($status == STATUS_NOOP) return;
    if ($status & STATUS_ERR) {
      return "ERROR: Could not create anonymous browsing user: "
                .$this->_db_message;
    } else {
      return "GOOD: Anonymous browsing user created successfully.";
    }

  }
  
  function apply_db_changes($sql) {
    if (! is_array($sql)) {
      $sql = array($sql);
    }
    return $this->apply_db_changes_array($sql);
  }
  
  function apply_db_changes_array($sql) {
    $status = STATUS_NOOP;
    foreach ($sql as $s) {
      if (is_array($s)) {
        $status = $this->apply_db_changes_array($s) | $status;
      } else {
        // empty sql is a NOOP
        if (! $sql) return STATUS_NOOP;

        $thisstatus = db_quiet($s);
        if ($thisstatus != STATUS_OK) {
          $this->_db_message = db_last_error();
        }
        $status |= $thisstatus;
      }
    }
    return $status;
  }
  
} // class SettingsCustomise
?>