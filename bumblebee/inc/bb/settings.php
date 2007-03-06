<?php
/**
* Settings editing object
*
* @author     Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage DBObjects
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

/** parent object */
require_once 'inc/formslib/nondbrow.php';
require_once 'inc/formslib/textfield.php';
require_once 'inc/formslib/textarea.php';
require_once 'inc/formslib/checkbox.php';
require_once 'inc/formslib/dummyfield.php';

/**
* Group editing object
*
* @package    Bumblebee
* @subpackage DBObjects
*/
class Settings extends NonDBRow {

  var $sections = array();
  var $descriptions;

  function Settings() {
    //$this->DEBUG=10;
    $this->nonDBRow('settings', T_('System configuration'), T_('Edit system settings'));
    $this->editable = 1;

    $this->headings = array(T_('Parameter'), T_('Value')); //, T_('Notes'));
    $this->descriptions = new SettingsDescriptionList();

    $config = ConfigReader::getInstance();
    $attrs = array('size' => '48');

    foreach ($this->descriptions->sections as $section) {
      $s = new nonDBRow($section->name, $section->description, '');
      $s->headings = $this->headings;
      $s->namebase = $section->name;
      $d = new DummyField('dummy');
      $d->value = 1;
      $s->addElement($d);
      foreach ($section->parameters as $parameter) {
        $f = null;
        $description = "<b>{$parameter->name}</b><br />".$parameter->description;
        switch ($parameter->type) {
          case SETTING_TEXT:
            $f = new TextField($parameter->name, $description);
            $f->value = $config->value($section->name, $parameter->name);
            $f->setAttr($attrs);
            break;
          case SETTING_TEXTAREA:
            $f = new TextArea($parameter->name, $description);
            $f->value = $config->value($section->name, $parameter->name);
            break;
          case SETTING_BOOLEAN:
            $f = new Checkbox($parameter->name, $description);
            $f->value = $config->value($section->name, $parameter->name);
            break;
          case SETTING_CHOICELIST:
            $f = new DropList($parameter->name, $description);
            $f->setValuesArray($parameter->list, 'id', 'iv');
            $f->setFormat('id', '%s', array('iv'));
            $f->value = $config->value($section->name, $parameter->name);
            break;
        }
        $s->addElement($f);
      }
      $this->sections[] = $s;
    }

    $this->dumpheader = 'Settings object';
  }

  function display($data=NULL) {
    $t = '';
    foreach($this->sections as $s) {
      $t .= $s->displayInTable();
    }
    return $t;
  }

  function update($data) {
    $t = false;
    for ($i=0; $i<count($this->sections); $i++) {
      $t = $this->sections[$i]->update($data) || $t;
    }
    $this->changed = $t;
    return $t;
  }

  function sync() {
    if ($this->changed) {
      $list = array();
      foreach($this->sections as $s) {
        foreach($s->fields as $f) {
          if ($f->name != 'dummy') {
            $value = $f->value === "" ? null : $f->value;
            $list[] = new SettingsEntry($s->namebase, $f->name, $value);
          }
        }
      }
      $model = new SettingsModel();
      $model->UpdateConfig($list);
      $model->WriteConfig();
    }
  }

} //class Settings

class SettingsModel {

  var $config;
  var $changedConfig;

  function SettingsModel() {
    $this->config = & ConfigReader::getInstance();
  }

  function WriteConfig() {
    foreach ($this->changedConfig as $entry) {
      if ($entry->value === null) {
        $entry->delete();
      } else {
        $entry->update();
      }
    }
  }

  function UpdateConfig($newConfig) {
    $this->changedConfig = array();
    foreach ($newConfig as $entry) {
      if ($entry->value !== $this->config->value($entry->section, $entry->parameter)) {
        $this->changedConfig[] = $entry;
      }
    }
  }

} //class SettingsModel

class SettingsEntry {

  var $section;
  var $parameter;
  var $value;

  var $table           = 'settings';
  var $sectionColumn   = 'section';
  var $parameterColumn = 'parameter';
  var $valueColumn     = 'value';
  var $fatal_sql = false;

  function SettingsEntry($section, $parameter, $value) {
    $this->section   = $section;
    $this->parameter = $parameter;
    $this->value     = $value;
  }

  function insert() {
    global $TABLEPREFIX;
    $q = "INSERT INTO $TABLEPREFIX{$this->table} "
        ."SET {$this->sectionColumn} = %s, {$this->parameterColumn} = %s, {$this->valueColumn} = %s ";
    $q = sprintf($q, qw($this->section), qw($this->parameter), qw($this->value));
    $sql_result = db_quiet($q, $this->fatal_sql);
    return $sql_result;
  }

  function delete() {
    global $TABLEPREFIX;
    $q = "DELETE FROM $TABLEPREFIX{$this->table} "
        ."WHERE {$this->sectionColumn} = %s AND {$this->parameterColumn} = %s "
        ."LIMIT 1";
    $q = sprintf($q, qw($this->section), qw($this->parameter));
    $sql_result = db_quiet($q, $this->fatal_sql);
    return $sql_result;
  }

  function update() {
    $this->delete();
    $this->insert();
  }


}

define('SETTING_TEXT',       1);
define('SETTING_TEXTAREA',   2);
define('SETTING_BOOLEAN',    3);
define('SETTING_CHOICELIST', 4);

class SettingsDescription {
  var $name;
  var $description;
  var $type;
  var $list;

  function SettingsDescription($name, $description, $type=SETTING_TEXT, $list=null) {
    $this->name = $name;
    $this->description = $description;
    $this->type = $type;
    if (is_array($list)) {
      $this->list = array();
      foreach ($list as $value) {
        $this->list[$value] = $value;
      }
    }
  }
}

class SettingsDescriptionSection {
  var $name;
  var $description;
  var $parameters = array();

  function SettingsDescriptionSection($name, $description) {
    $this->name = $name;
    $this->description = $description;
  }
}

class SettingsDescriptionList {

  var $sections = array();

  function SettingsDescriptionList() {

    $conf = ConfigReader::getInstance();


    $list['error_handling'] = T_('Error handling');
    $list['language']       = T_('Regional');
    $list['display']        = T_('Display');
    $list['auth']           = T_('Authentication');
    $list['instruments']    = T_('Instrument defaults');
    $list['calendar']       = T_('Calendar');
    $list['billing']        = T_('Billing');
    $list['export']         = T_('Data export');
    $list['sqldump']        = T_('Backups');
    $list['email']          = T_('Mail servers');
    $list['pdfexport']      = T_('PDF');

    $s = new SettingsDescriptionSection('main', T_('General settings'));
    $s->parameters[] = new SettingsDescription('SiteTitle', T_('Name of your site, appended to the title tag of every page generated.'));
    $s->parameters[] = new SettingsDescription('CopyrightOwner', T_('Copyright owner of your site, included in the footer of every page generated.'));
    $s->parameters[] = new SettingsDescription('AdminEmail', T_('Administrative contact email for this installation'));
    $s->parameters[] = new SettingsDescription('SystemEmail', T_('"From" address used in emails sent by Bumblebee'));
    $s->parameters[] = new SettingsDescription('BasePath', T_('Path to the base of the Bumblebee installation when viewed over your webserver (i.e. where is Bumblebee installed relative to your webserver\'s DocumentRoot?)  This is used to find the theme for images, css etc and to set the login cookie. If your installation is at <code>http://www.dept.example.edu/equipment/</code> then use the value of <code>/equipment</code> here (this value must start with a slash, it should not have a trailing slash and should not include <code>http://</code> or the server name). If this value is not set then Bumblebee will take a good guess at the correct value and will normally get it right.'));
    $s->parameters[] = new SettingsDescription('BaseURL', T_('Base url to prepend to all generated links. If your installation is at <code>http://www.dept.example.edu/equipment/</code> then you would specify <code>/equipment/index.php</code> here.  If this value is not set then Bumblebee will take a good guess at the correct value and will normally get it right.'));
    $s->parameters[] = new SettingsDescription('BaseRealPath', T_('Base path of this installation on your servers filesystem. This parameter is not normally needed unless you have a particularly complex theme that wants to include other files.'));
    $s->parameters[] = new SettingsDescription('ExtraIncludePath', T_('Additional path to be added to the PHP include path to find libraries like TCPDF, Auth::RADIUS'));
    $this->sections[] = $s;

    $s = new SettingsDescriptionSection('error_handling', T_('Error handling'));
    $s->parameters[] = new SettingsDescription('VerboseSQL', T_('Show all SQL statements in the browser (for debugging and development purposes only).'), SETTING_BOOLEAN);
    $s->parameters[] = new SettingsDescription('VerboseData', T_('Show all user-submitted data in the browser (for debugging and development purposes only).'), SETTING_BOOLEAN);
    $s->parameters[] = new SettingsDescription('AllWarnings', T_('Show all PHP warnings and notices in the browser (for debugging and development purposes only).'), SETTING_BOOLEAN);
    $s->parameters[] = new SettingsDescription('UseLogFile', T_('Keep a log file of Bumblebee events (login, logout, modifying users etc).'), SETTING_BOOLEAN);
    $s->parameters[] = new SettingsDescription('LogFile', T_('File to be used for the log file. Relative paths are relative to the location of the Bumblebee index.php file. The file must be writable by the webserver process and should be rotated using cron or logrotate)'));
    $s->parameters[] = new SettingsDescription('LogLevel', T_('Log level determining how much should be logged. Integer from 0 to 10 where: <br /> 0 nothing<br /> 1 masquerading<br /> 2 modifying users, instruments etc<br /> 3 deleting bookings<br /> 4 login and logout events<br /> 5 making bookings<br />  9 miscellaneous notices, locale failures<br />  10 lots of debug noise.'));
    $this->sections[] = $s;

    $s = new SettingsDescriptionSection('language', T_('Regional settings'));
    $s->parameters[] = new SettingsDescription('locale', T_('Locale to use for generating messages. The list of locales available can be seen in the locale/ directory within your installation or on the Bumblebee website. If you choose a locale that doesn\'t exist, you\'ll get the interface in English.'));
    $s->parameters[] = new SettingsDescription('translation_base', T_('Location on disk of Bumblebee\'s translation files (paths relative to index.php are OK).'));
    $s->parameters[] = new SettingsDescription('timezone', sprintf(T_('Timezone in which the instruments are located. (<a href="%s">list of acceptable values</a>)'), 'http://php.net/manual/en/timezones.php'));
    $s->parameters[] = new SettingsDescription('date_shortdate', sprintf(T_('Format string for showing dates in short form. (<a href="%s">list of acceptable format codes</a>)'), 'http://php.net/date'));
    $s->parameters[] = new SettingsDescription('date_longdate', sprintf(T_('Format string for showing dates in long form. (<a href="%s">list of acceptable format codes</a>)'), 'http://php.net/date'));
    $s->parameters[] = new SettingsDescription('date_shorttime', sprintf(T_('Format string for showing times in short form. (<a href="%s">list of acceptable format codes</a>)'), 'http://php.net/date'));
    $s->parameters[] = new SettingsDescription('date_longtime', sprintf(T_('Format string for showing times in long form. (<a href="%s">list of acceptable format codes</a>)'), 'http://php.net/date'));
    $s->parameters[] = new SettingsDescription('date_shortdatetime', sprintf(T_('Format string for showing date-time in short form. (<a href="%s">list of acceptable format codes</a>)'), 'http://php.net/date'));
    $s->parameters[] = new SettingsDescription('date_longdatetime', sprintf(T_('Format string for showing date-time in long form. (<a href="%s">list of acceptable format codes</a>)'), 'http://php.net/date'));

    $s->parameters[] = new SettingsDescription('week_offset', T_('Day to start weekly calendar on (0 = Sunday, 1 = Monday, ..., 6 = Saturday)'));
    $s->parameters[] = new SettingsDescription('decimal_separator', T_('Decimal separator for printing numbers (decimal point "." or decimal comma ",").'));
    $s->parameters[] = new SettingsDescription('thousands_separator', T_('Thousands separator for printing numbers (",", "" or ".").'));
    $s->parameters[] = new SettingsDescription('use_comma_floats', T_('Accept data using the European input convention of using a decimal comma rather than a decimal point (e.g. 1.234,67) in numeric input fields. You can use decimal commas at the same time as decimal points as long as you don\'t also want to use thousands separators at all. (Note: the values are always stored using internally decimal points so you can change this setting safely.)'));
    $s->parameters[] = new SettingsDescription('money_format', T_('Format string for currency amounts -- include your currency symbol and %s to indicate where the amount should go (e.g. <code>$%s</code> <code>%s&euro;</code> <code>&pound;%s</code> <code>&yen;%s</code>)'));
    $s->parameters[] = new SettingsDescription('money_decimal_places', T_('Number of decimal places to show money amounts to.'));
    $s->parameters[] = new SettingsDescription('removedCurrencySymbols', T_('Currency symbols that should be stripped off the beginning or end of a currency amount to make it into a pure number for storing it in the database.'));
    $this->sections[] = $s;

    $s = new SettingsDescriptionSection('display', T_('User Interface'));
    $s->parameters[] = new SettingsDescription('AllowAutocomplete', T_('Permit browsers to use the autocomplete features (it is recommended that this be turned off -- allowing form autocompletion can mess with the Edit User form with various popular browsers).'), SETTING_BOOLEAN);
    $s->parameters[] = new SettingsDescription('server_signature', T_('Include webserver, PHP and database version in the footer of pages using the default theme.'), SETTING_BOOLEAN);
    $s->parameters[] = new SettingsDescription('LoginPage', T_('Extra information (HTML format) to display on the login page next to the username and password boxes (e.g. "secured by XYZ", "check the certificate").'), SETTING_TEXTAREA);
    $s->parameters[] = new SettingsDescription('AnonymousAllowed', sprintf(T_('Permit users who are not logged in to see the calendars of selected instruments (once enabled, you can use <a href="%s">this URL</a> to provide anonymous access).'), $conf->BaseURL.'?anonymous'), SETTING_BOOLEAN);
    $s->parameters[] = new SettingsDescription('AnonymousUsername', T_('Username that will be used to look up permissions for the anonymous user'));
    $s->parameters[] = new SettingsDescription('AnonymousPassword', T_('Password that will be used to simulate a login for the anonymous user.'));
    $this->sections[] = $s;

    $s = new SettingsDescriptionSection('auth', T_('User Authentication'));
    $s->parameters[] = new SettingsDescription('validUserRegexp', T_('Regular expression that defines what is an acceptable username on your installation. Examples:<br />Allow anything: <code>/^.+$/</code><br />Require unix-like login names <code>/^[a-z0-9][a-z0-9@\-_.+]+$/i</code>'));
    $s->parameters[] = new SettingsDescription('permissionsModel', T_('Use fine-grained permissions model for managing user access.'), SETTING_BOOLEAN);
    $s->parameters[] = new SettingsDescription('useLocal', T_('Permit local logins (user passwords are encoded in the users table of the database.'), SETTING_BOOLEAN);
    $s->parameters[] = new SettingsDescription('LocalPassToken', T_('Method by which passwords are encoded in the database: des md5 md5_compat md5_emulated sha1 (md5_compat is the method used by Bumblebee 1.0)'), SETTING_CHOICELIST, array('des', 'md5', 'md5_compat', 'md5_emulated', 'sha1'));
    $s->parameters[] = new SettingsDescription('convertEntries', T_('Convert encoded passwords to the selected encoding when users login.'), SETTING_BOOLEAN);
    $s->parameters[] = new SettingsDescription('useRadius', T_('Use RADIUS authentication for users against servers specified in the radius.ini file.'), SETTING_BOOLEAN);
    $s->parameters[] = new SettingsDescription('useLDAP', T_('Use LDAP authentication for users against servers specified in the ldap.ini file.'), SETTING_BOOLEAN);
    $this->sections[] = $s;

    $s = new SettingsDescriptionSection('instruments', T_('Instruments'));
    $s->parameters[] = new SettingsDescription('usualopen', T_('Default opening time for instruments.'));
    $s->parameters[] = new SettingsDescription('usualclose', T_('Default closing time for instruments.'));
    $s->parameters[] = new SettingsDescription('usualprecision', T_('Default precision to which the calendar is displayed (in seconds).'));
    $s->parameters[] = new SettingsDescription('usualtimemarks', T_('Default number of boxes between time marks (in units of <i>usualprecision</i>).'));
    $s->parameters[] = new SettingsDescription('usualcallength', T_('Default length of the calendar (in weeks).'));
    $s->parameters[] = new SettingsDescription('usualcalhistory', T_('Default period to show into the past on the calendar (in weeks).'));
    $s->parameters[] = new SettingsDescription('usualclose', T_('Default period of time to permit users to view into the future (in days).'));
    $s->parameters[] = new SettingsDescription('usualtimeslotpicture', T_('Default timeslotrule for describing instrument time slots.'));
    $s->parameters[] = new SettingsDescription('usualhalfdaylength', T_('Default length of a "half day" on the instruments (in hours).'));
    $s->parameters[] = new SettingsDescription('usualfulldaylength', T_('Default length of a "full day" on the instruments (in hours).'));
    $s->parameters[] = new SettingsDescription('usualmindatechange', T_('Default minimum notice required to change or delete a booking (in hours).'));
    $s->parameters[] = new SettingsDescription('emailSubject', T_('Subject to use for emails sent to instrument supervisors to notify them that the instrument has been booked (instrument name is prepended).'));
    $s->parameters[] = new SettingsDescription('emailFromName', T_('Name that booking notification emails will be shown as coming from.'));
    ///TODO
    //$s->parameters[] = new SettingsDescription('emailTemplate', T_('Email text that will be included in the email. The following tokens are replaced: '), SETTING_TEXTAREA);
    $s->parameters[] = new SettingsDescription('emailRequestSubject', T_('Subject to use for emails sent to instrument supervisors to request bookings by anonymous users (instrument name is prepended).'));
    ///TODO
    //$s->parameters[] = new SettingsDescription('emailRequestTemplate', T_('Email text that will be included in the email. The following tokens are replaced: '), SETTING_TEXTAREA);
    $this->sections[] = $s;

    $s = new SettingsDescriptionSection('calendar', T_('Calendar'));
    $s->parameters[] = new SettingsDescription('showphone', T_('Show users\' phone numbers on the calendar.'), SETTING_BOOLEAN);
    $s->parameters[] = new SettingsDescription('notesbottom', T_('Show instrument notes at the bottom of the page (otherwise shown at the top).'), SETTING_BOOLEAN);
    $this->sections[] = $s;
    $s->parameters[] = new SettingsDescription('shortdaynames', T_('Use short weekday names across the top of the calendar display (e.g. "Mon")'), SETTING_BOOLEAN);

    $s = new SettingsDescriptionSection('export', T_('Export'));
    $s->parameters[] = new SettingsDescription('filename', T_('Filename template for creating export files. The following tokens are replaced: __action__ __what__ __date__'));
    $s->parameters[] = new SettingsDescription('enablePDF', T_('Enable PDF export (make sure your PDF library is installed as per the Bumblebee installation instructions).'), SETTING_BOOLEAN);
    ///TODO
    //$s->parameters[] = new SettingsDescription('htmlWrapperFile', T_('HTML file used as a wrapper around the HTML export'), SETTING_TEXTAREA);
    $this->sections[] = $s;

    $s = new SettingsDescriptionSection('pdfexport', T_('PDF'));
    $s->parameters[] = new SettingsDescription('size', T_('Paper size (A4, Letter, Legal etc)'));
    $s->parameters[] = new SettingsDescription('orientation', T_('Paper orientation (Landscape or Portait)'), SETTING_CHOICELIST, array('L', 'P'));
    $s->parameters[] = new SettingsDescription('pageWidth', T_('Width of the page with the specified orientation (in millimetres)'));
    $s->parameters[] = new SettingsDescription('pageHeight', T_('Heoght of the page with the specified orientation (in millimetres)'));
    $this->sections[] = $s;

    $s = new SettingsDescriptionSection('billing', T_('Billing'));
    $s->parameters[] = new SettingsDescription('filename', T_('Filename to use for exporting the data.'));
    $s->parameters[] = new SettingsDescription('emailFromName', T_('Name to use as the sender of the emails.'));
    $s->parameters[] = new SettingsDescription('emailSubject', T_('Subject to use on the emails.'));
    ///TODO
    //$s->parameters[] = new SettingsDescription('emailTemplate', T_('Template for the email text.'), SETTING_TEXTAREA);
    $this->sections[] = $s;

    $s = new SettingsDescriptionSection('sqldump', T_('Backup'));
    $s->parameters[] = new SettingsDescription('mysqldump', T_('Command to issue to start the backup process (e.g. <code>mysqldump</code> or <code>/usr/bin/mysqldump</code>.'));
    $s->parameters[] = new SettingsDescription('options', sprintf(T_('Commandline options to to give the backup program (e.g. <code>--complete-insert --no-create-info</code> or <code>--complete-insert --single-transaction</code>). See <a href="%s">mysqldump manual</a> for more details'), 'http://dev.mysql.com/doc/mysql/en/mysqldump.html'));
    $this->sections[] = $s;

    $s = new SettingsDescriptionSection('email', T_('Email Server'));
    $s->parameters[] = new SettingsDescription('smtp_server', T_('Hostname or IP address of the server to use for sending outgoing email (not required if properly configured in your php.ini file).'));
    $s->parameters[] = new SettingsDescription('smtp_port', T_('Port to connnect to for sending outgoing email (not required if properly configured in your php.ini file).'));
    $this->sections[] = $s;

  }
}
