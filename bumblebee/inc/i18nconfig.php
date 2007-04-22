<?php
/**
* Configuration of Internationali[sz]ation of Bumblebee
*
* Loads the desired language pack for Bumblebee.
*
* @author     Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Misc
*
* path (bumblebee root)/inc/i18nconfig.php
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

require_once 'inc/bb/configreader.php';

/** ensure base tools have been initialised */
require_once 'inc/i18n.php';
/** logging routine */
require_once 'inc/logging.php';

$conf = ConfigReader::getInstance();

// As of PHP5.0, we *must* set the timezone for date calculations, otherwise
// many errors will be emitted. For a list of timezones, see
//     http://php.net/manual/en/timezones.php
$tz = $conf->value('language', 'timezone', 'Europe/London');
date_default_timezone_set($tz);

// this could be done per-user, but that would be more difficult...
//$locale = (isset($_GET['lang']))? $_GET['lang'] : DEFAULT_LOCALE;
$locale = $conf->value('language', 'locale');

// work out if php-gettext is installed on this system
if (function_exists('T_setlocale') && function_exists('T_')) {
  //it is
  $encoding = 'UTF-8';
  /** package name for i18n (mo name) */
  define('PACKAGE', 'bumblebee');


  // gettext setup
  T_setlocale(LC_MESSAGES, $locale);

  T_bindtextdomain(PACKAGE, $conf->value('language', 'translation_base'));
  T_bind_textdomain_codeset(PACKAGE, $encoding);
  T_textdomain(PACKAGE);

  if (!locale_emulation()) {
    logmsg(9, "using C gettext for locale '$locale'");
  }
  else {
    logmsg(9, "emulating gettext for locale '$locale'");
  }
} else {
  // php gettext not installed
  logmsg(9, "Cannot find php-gettext so ignoring request for locale '$locale'");
  function T_($s) { return $s; }
}

?>
