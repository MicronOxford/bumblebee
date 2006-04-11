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


require_once 'php-gettext/gettext.inc';
require_once 'inc/logging.php';

$encoding = 'UTF-8';
/** package name for i18n (mo name) */
define('PACKAGE', 'bumblebee'); 

#$locale = (isset($_GET['lang']))? $_GET['lang'] : DEFAULT_LOCALE;
$locale = $CONFIG['language']['locale'];

// gettext setup
T_setlocale(LC_MESSAGES, $locale);
// Set the text domain as 'messages'
$domain = 'messages';
T_bindtextdomain(PACKAGE, $CONFIG['language']['translation_base']);
T_bind_textdomain_codeset(PACKAGE, $encoding);
T_textdomain(PACKAGE);

if (!locale_emulation()) {
  logmsg(9, "using C gettext for locale '$locale'");
}
else {
  logmsg(9, "emulating gettext for locale '$locale'");
}

// if (function_exists('gettext')) {
//   /** package name for i18n (mo name) */
//   define('PACKAGE', 'bumblebee'); 
//   $lang = $CONFIG['language']['locale']; // define language: en_US, zh_CN, zh_TW 
//   putenv("LANG=$lang"); 
// //   $lang_found = setlocale(LC_TIME, $lang);
// //   $lang_found = setlocale(LC_MESSAGES, $lang);
//   /** @todo do we really want LC_ALL here? */
//   $lang_found = setlocale(LC_ALL, $lang);
//   if (empty($lang_found)) {
//     /** @todo provide an error for the user here? */
//     // FIXME: 
//     echo "Locale error";
//   } else {
// //     echo "Preferred language is '$lang_found'";
//   }
//   bindtextdomain(PACKAGE, $CONFIG['language']['translation_base']); 
//   textdomain(PACKAGE); 
// } else {
//   // if gettext isn't installed, then provide a fallback
//   function gettext($string) {
//     return $string;
//   }
// }

?> 
