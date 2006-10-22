<?php
/**
* Load system-wide configuration settings
*
* Set up a config object that parses the { @link bumblebee.ini } file.
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

$REBASE_INSTALL = isset($REBASE_INSTALL) ? $REBASE_INSTALL : '';
$NON_FATAL_CONFIG = isset($NON_FATAL_CONFIG) ? $NON_FATAL_CONFIG : false;

/*
* Set the location of config files
*
* By default, config/ in the Bumblebee installation directory, but
* can be moved into /etc, /usr/share/bumblebee ...
*/
$configLocation = $REBASE_INSTALL.'config/';

new ConfigReader();
$conf = & ConfigReader::getInstance();
$conf->SetFileLocation($configLocation);
$conf->LoadFile('bumblebee.ini');
$conf->ParseConfig();

/**
* Copyright of generated output is attributed to $COPYRIGHTOWNER
* @global string $COPYRIGHTOWNER
*/
global $COPYRIGHTOWNER;
$COPYRIGHTOWNER = $conf->value('main', 'CopyrightOwner');


/**
* If $VERBOSESQL is true, then all SQL statements will be dumped to the browser for debugging purposes
* @global boolean $VERBOSESQL
*/
global $VERBOSESQL;
$VERBOSESQL = $conf->value('error_handling', 'VerboseSQL');

/**
* If $VERBOSEDATA is true then user data will be dumped to the browser for debugging purposes
* @global boolean $VERBOSEDATA
*/
global $VERBOSEDATA;
$VERBOSEDATA = $conf->value('error_handling', 'VerboseData');

ini_set("session.use_only_cookies",1); #don't permit ?PHPSESSID= stuff
#ini_set("session.cookie_lifetime",60*60*1); #login expires after x seconds


if ($conf->value('main', 'ExtraIncludePath', false)) {
  set_include_path($REBASE_INSTALL.($conf->value('main','ExtraIncludePath')).PATH_SEPARATOR.get_include_path());
}

if ($conf->value('error_handling', 'UseDBug', false) && ! $NON_FATAL_CONFIG) {
  // include the dBug pretty printer for error and debugging dumps
  // http://dbug.ospinto.com/
  include_once 'dBug.php';
}

if (! $NON_FATAL_CONFIG) {
  /** load the language pack */
  require_once 'i18n.php';

  // As of PHP5.0, we *must* set the timezone for date calculations, otherwise
  // many errors will be emitted. For a list of timezones, see
  //     http://php.net/manual/en/timezones.php
  $tz = $conf->value('language', 'timezone', 'Europe/London');
  date_default_timezone_set($tz);
}

/**
* $BUMBLEBEEVERSION is the installed version of the software
* @global string $BUMBLEBEEVERSION
*/
global $BUMBLEBEEVERSION;
$BUMBLEBEEVERSION = '1.1.5';

?>
