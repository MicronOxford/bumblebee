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
            break;
        }
        $s->addElement($f);
      }
      $this->sections[] = $s;
    }

    $this->dumpheader = 'Settings object';
  }

  function display() {
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
    $q = "INSERT INTO $TABLEPREFIX{$this->table} "
        ."SET {$this->sectionColumn} = %s, {$this->parameterColumn} = %s, {$this->valueColumn} = %s ";
    $q = sprintf($q, qw($this->section), qw($this->parameter), qw($this->value));
    $sql_result = db_quiet($q, $this->fatal_sql);
    return $sql_result;
  }

  function delete() {
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

  function SettingsDescription($name, $description, $type=SETTING_TEXT) {
    $this->name = $name;
    $this->description = $description;
    $this->type = $type;
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
    $s->parameters[] = new SettingsDescription('BasePath', T_('Path to the base of the Bumblebee installation when viewed over your webserver (i.e. where is Bumblebee installed relative to your webserver\'s DocumentRoot?)  This is used to find the theme for images, css etc and to set the login cookie. If your installation is at http://www.dept.example.edu/equipment/ then use the value of "/equipment" here (this value must start with a slash, it should not have a trailing slash and should not include http:// or the server name)'));
    $s->parameters[] = new SettingsDescription('BaseURL', T_('Base url to prepend to all generated links. If your installation is at http://www.dept.example.edu/equipment/ then you would specify <code>/equipment/index.php</code> here.'));
    $s->parameters[] = new SettingsDescription('BaseRealPath', T_('Base path of this installation on your servers filesystem. This parameter is not normally needed unless you have a particularly complex theme that wants to include other files.'));
    $s->parameters[] = new SettingsDescription('ExtraIncludePath', T_('Additional path to be added to the PHP include path to find libraries like FPDF, Auth::RADIUS'));
    $this->sections[] = $s;

    $s = new SettingsDescriptionSection('error_handling', T_('Error handling'));
    $s->parameters[] = new SettingsDescription('VerboseSQL', T_('Show all SQL statements in the browser (for debugging and development purposes only).'), SETTING_BOOLEAN);
    $s->parameters[] = new SettingsDescription('VerboseData', T_('Show all user-submitted data in the browser (for debugging and development purposes only).'), SETTING_BOOLEAN);
    $s->parameters[] = new SettingsDescription('AllWarnings', T_('Show all PHP warnings and notices in the browser (for debugging and development purposes only).'), SETTING_BOOLEAN);
    $s->parameters[] = new SettingsDescription('UseLogFile', T_('Keep a log file of Bumblebee events (login, logout, modifying users etc).'), SETTING_BOOLEAN);
    $s->parameters[] = new SettingsDescription('LogFile', T_('File to be used for the log file. Relative paths are relative to the location of the Bumblebee index.php file. The file must be writable by the webserver process and should be rotated using cron or logrotate)'));
    $s->parameters[] = new SettingsDescription('LogLevel', T_('Log level determining how much should be logged. Integer from 0 to 10 where: <br /> 0 nothing<br /> 1 masquerading<br /> 2 modifying users, instruments etc<br /> 3 deleting bookings<br /> 4 login and logout events<br /> 5 making bookings<br />  9 miscellaneous notices, locale failures<br />  10 lots of debug noise.'));
    $this->sections[] = $s;

    $s = new SettingsDescriptionSection('language', T_('Regional'));
    $s->parameters[] = new SettingsDescription('VerboseSQL', T_('Locale to use for generating messages. The list of locales available can be seen in the locale/ directory within your installation or on the Bumblebee website. If you choose a locale that doesn\'t exist, you\'ll get the interface in English.'));
    $s->parameters[] = new SettingsDescription('translation_base', T_('Location on disk of Bumblebee\'s translation files (paths relative to index.php are OK).'));
    $s->parameters[] = new SettingsDescription('timezone', sprintf(T_('Timezone in which the instruments are located. (<a href="%s">list of acceptable values</a>)'), 'http://php.net/manual/en/timezones.php'));
    $s->parameters[] = new SettingsDescription('date_cal_short', T_('Short date format for display on the calendar (e.g. "16 May")'));
    $s->parameters[] = new SettingsDescription('date_long', T_('Long date format for display in tool tips and menus (e.g. "13:15, 16 May 2006")'));
    $s->parameters[] = new SettingsDescription('date_cal_shortnames', T_('Use short weekday names across the top of the calendar display (e.g. "Mon")'), SETTING_BOOLEAN);
    $s->parameters[] = new SettingsDescription('week_offset', T_('Day to start weekly calendar on (0 = Sunday, 1 = Monday, ..., 6 = Saturday)'));
    //$s->parameters[] = new SettingsDescription('decimal_separator', T_('Decimal separator for printing numbers.'));
    $s->parameters[] = new SettingsDescription('moneyFormat', T_('Format string for currency amounts -- include your currency symbol and the number of significant figures as an sprintf format string (e.g. <code>$%.2f</code> <code>&euro;%.2f</code> <code>&pound;%.2f</code> <code>&yen;%.0f</code>)'));
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



  }
}

//
// [auth]
// ; This regular expression validates the username before any further authentication tests are done
// ;
// ; By default, just require that the username is at least one character long:
// validUserRegexp = "/^.+$/"
// ;
// ; If you are just wanting to enforce a minimum length for usernames, then
// ; this will require usernames to be at least 4 characters long:
// ;validUserRegexp = "/^.{4,}$/"
// ;
// ; Alternatively, you may wish to restrict usernames to fairly standard
// ; unix- and windows-like usernames.
// ; Note that this will reject any non-English (accented) characters
// ;validUserRegexp = "/^[a-z0-9][a-z0-9@\-_.+]+$/i"
// ;
// ; Use the fine-grained permissions model rather than just isAdmin tests.
// ; Note: you need to upgrade your database structure for this to work.
// permissionsModel = true
// ; ---- authentication methods ----
// ; Select the authorisation methods you want to use.
// ; Multiple ones can be selected, magic password keys in the SQL table 'users' are used to
// ; establish which users are to be authenticated by non-local methods. These keys should
// ; never be able to be generated by the LocalPassToken method (usually an md5 hash)
// ;
// ; should local users be permitted
// useLocal = 1
// ; method by which the password will be encoded for storage in the db.
// ; valid values are "des", "md5", "md5_compat", "md5_emulated", "sha1"
// ; md5_compat is the method used by default in Bumblebee v1.0.x
// LocalPassToken = "md5"
// ; when a user logs in, change their password in the db to the hashing method above if it is
// ; not already in that format
// convertEntries = false
// ;
// ; use a radius server if users are set up for that (see RadiusPassToken), config in radius.ini
// useRadius = 1
// RadiusPassToken = "--radius-auth-user"
// ;
// ; use an LDAP server if users are set up for that (see LDAPPassToken), config in ldap.ini
// useLDAP = 1
// LDAPPassToken = "--ldap-auth-user"
// ;
// ; ========== ADVANCED FUNCTIONS ==============
// ; Turn on these advanced functions ONLY if you need them to debug authentication problems
// ; or to recover a lost admin password.
// authAdvancedSecurityHole = true
// ;
// ; Include verbose error messages as to why the login failed (e.g. "Login failed: username unknown")
// ; rather than just the generic "Login failed" message. Note that turning on these messages reveals
// ; more about your internal setup and so should not be done in a production environment.
// verboseFailure = true
// ; Force all login attempts to be successful regardless of whether the correct password was given
// ; (allows forgotten admin passwords to be retrieved). Note that you NEVER want to turn this on in
// ; a production environment!
// recoverAdminPassword = false
//
// [instruments]
// ; ---- calendar controls ----
// ; Default times used in instrument edit pages for when the calendar.
// ; Each instrument is configurable individually in the Edit Instruments form.
// ; these are just the defaults for creating new instruments.
// usualopen = "08:00"
// usualclose = "18:00"
// ; default time included in each booking row (display is rounded to this time). Specified in seconds.
// usualprecision = 900
// usualtimemarks = 4
// ; defaults for the monthly view -- how many weeks should the calendar include,
// ; how many full weeks history and how far in the future can the calendar go?
// usualcallength = 4
// usualcalhistory = 1
// usualcalfuture = 365
// ; default timeslot picture for a new instrument
// usualtimeslotpicture = "[0-6]<08:00-18:00/2;18:00-32:00/1,Overnight booking>"
// ; default length of a "half day" and "full day" for when calculating booking costs
// usualhalfdaylength = 5
// usualfulldaylength = 8
// ; default minimum notice (in hours) that should be given for booking changes
// usualmindatechange = 24
// ; ---- booking notification templates ---
// ; These settings are shared across all instruments. They control
// ; what the emails sent to notify instrument supervisors look like.
// ; The Subject: line of the email (the name of the instrument will be prepended)
// emailSubject = "Instrument booking notification"
// ; The From name to be used with the system email address (the instrument name will be included)
// emailFromName = "Bumblebee System Notification"
// ; a text file with various tokens in it for the name, username etc details of the booking
// emailTemplate = "theme/export/emailnotificationtemplate.txt"
// ; ----- booking request for anonymous users ------
// ; The Subject: line of the email (the name of the instrument will be prepended)
// emailRequestSubject = "requested instrument booking"
// ; a text file with various tokens in it for the name, username etc details of the booking
// emailRequestTemplate = "theme/export/emailrequesttemplate.txt"
//
// [calendar]
// ; CSS styles used in the calendar view
// todaystyle = caltoday
// monthstyle = monodd/moneven
// ; show phone numbers on calendar (true or false)
// showphone = false
// ; show instrument notes at the bottom of the page (true) or at the top (false)
// notesbottom = true
//
// [export]
// ; set the base filename to use for saving
// ; substituted patterns are: __action__ __what__ and __date__
// filename = bumblebee-__action__-__what__-__date__
// enablePDF = 1
// ; FIXME does this embedded constant still work when running without Warnings turned on?
// defaultFormat = EXPORT_FORMAT_VIEW
// htmlWrapperFile = "theme/export/exporttemplate.html"
//
// [pdfexport]
// ; paper size and orientation. See the FPDF documentation for details about supported page sizes
// size = "A4"
// orientation = "L"
// ; page heights, widths, sizes in mm.
// ; Ensure that the size and orientation specified above agrees (e.g. A4 Landscape) with the
// ; pageHeight and pageWidth here
// pageWidth   = 297
// pageHeight  = 210
// leftMargin  = 15
// rightMargin = 15
// topMargin   = 15
// bottomMargin= 15
// ; margin added to auto calc'd column widths
// minAutoMargin = 4
// ; orientation in the header L, C, R
// tableHeaderAlignment = "L"
// ; lines between rows, use "T"
// rowLines = ""
// ; lines around header rows
// headerLines = "TB"
//
// ; line widths in mm
// normalLineHeight        = 5
// headerLineHeight        = 6
// footerLineHeight        = 4
// sectionHeaderLineHeight = 8
// doubleLineWidth         = 0.2
// singleLineWidth         = 0.3
// singleCellTopMargin     = 1
//
// ; colors, lines and fonts
// ; format for colours is r,g,b where the values are 0-255. "0,0,0" is black, "255,255,255" is white.
// normalFillColor = "224,235,255"
// normalDrawColor = "0,0,0"
// normalTextColor = "0,0,0"
// normalFont      = "Arial,,12"
//
// sectionHeaderFillColor = "255,255,255"
// sectionHeaderDrawColor = "0,0,0"
// sectionHeaderTextColor = "0,0,0"
// sectionHeaderFont      = "Arial,B,14"
//
// tableHeaderFillColor = "0,0,128"
// tableHeaderDrawColor = "0,0,0"
// tableHeaderTextColor = "255,255,255"
// tableHeaderFont      = "Arial,B,12"
//
// tableFooterFillColor = "0,0,128"
// tableFooterDrawColor = "0,0,0"
// tableFooterTextColor = "255,255,255"
// tableFooterFont      = "Arial,,9"
//
// tableTotalFillColor = "224,235,255"
// tableTotalDrawColor = "0,0,0"
// tableTotalTextColor = "0,0,0"
// tableTotalFont      = "Arial,,12"
//
//
// [billing]
// ;filename = "/tmp/bumblebee-invoice-__who__-__date__.pdf"
// filename = "bumblebee-invoice-__who__-__date__.pdf"
// emailFromName = "Bumblebee System Reports"
// emailSubject = "Instrument usage summary"
// emailTemplate = "theme/export/emailbillingtemplate.txt"
//
// [sqldump]
// ; options for generating the SQL backups of the database
// ; Executable to use for generating backup and path to it if it's not in the execution path
// ; you can use something other than mysqldump for this if you want, but it should understand
// ; the host, user, password and database syntax that mysqldump uses.
// ; mysqldump=/usr/bin/mysqldump
// mysqldump=mysqldump
// ; extra mysqldump options
// ; e.g. --complete-insert --no-create-info are useful for moving data from one db to another
// ; see man mysqldump or http://dev.mysql.com/doc/mysql/en/mysqldump.html
// ;options="--complete-insert --no-create-info --lock-tables"
// options="--complete-insert --single-transaction"
// ;options=
//
// [email]
// ; These options are only used when running PHP under windows to send email reports.
// ; If your php.ini file is correctly set up with these values then you should leave these
// ; variables blank and it will work just fine.
// ;
// ; hostname or IP address of the server to use for sending outgoing email
// ;smtp_server = "localhost"
// ;smtp_server = "mailhub.example.edu"
// smtp_server = ""
// ; port to connect to on the above server
// ;smtp_port = 25
// smtp_port = ""
//
//
// }