<?php
/**
* Internationali[sz]ation of Bumblebee
*
* Loads language pack for Bumblebee and tools for translating strings.
* Makes use of PHP's gettext() functions if they are available
* and the locale is installed on the server and can be found.
* Since this is rarely the case, particularly on shared servers,
* the fallback has to be a good one.
*
* The php-gettext project provides the base functionality required.
*
* See 
*        http://savannah.nongnu.org/projects/php-gettext/
*
* (tested with php-gettext version 1.0.7)
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
require_once 'inc/bb/configreader.php';
checkValidInclude();

/** php-gettext base file for gettext emulation */
include_once 'php-gettext/gettext.inc';
/** logging routine */
require_once 'inc/logging.php';

$conf = ConfigReader::getInstance();

// get PHP to send a UTF-8 header for the content-type charset rather
// than the PHP4 default iso8859-1
ini_set('default_charset', 'utf-8');

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

//FIXME? this is not exactly oop but the current sigleton does not provide for it
if ($conf->value('language', 'moneyFormat') === null) $conf->data['language']['moneyFormat'] = "$%.2f";


?> 
