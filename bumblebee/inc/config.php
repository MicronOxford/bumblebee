<?php
/**
* Load user's configuration settings
*
* Parses the {@link bumblebee.ini } file and sets appropriate globals for quick reference
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Misc
*/


/** location of config files
* 
* By default, config/ in the Bumblebee installation directory, but
* can be moved into /etc, /usr/share/bumblebee ...
* @global string $CONFIGLOCATION
*/
$CONFIGLOCATION = 'config/';

/**
* $CONFIG contains the parsed config options for this installation
* @global array $CONFIG
*/
$CONFIG = parse_ini_file($CONFIGLOCATION.'bumblebee.ini',1);

/**
* $ADMINEMAIL is used for generating links to the administrator for more information, help etc
* @global string $ADMINEMAIL
*/
$ADMINEMAIL = $CONFIG['main']['AdminEmail'];
/**
* $BASEPATH is the path to this installation so that CSS and image files can be found by the browser
* @global string $BASEPATH
*/
$BASEPATH   = $CONFIG['main']['BasePath'];
/**
* $BASEURL is prepended to all URLs generated by the system so that the links work
* @global string $BASEURL
*/
$BASEURL    = $CONFIG['main']['BaseURL'];

/**
* create a URL for an anchor
* @param string $action    action to be performed
* @param array  $list      (optional) key => value data to be added to the URL
* @return string URL
* @global string base URL for the installation
*/
function makeURL($action, $list=NULL) {
  global $BASEURL;
  $list = is_array($list) ? $list : array();
  $list['action'] = $action;
  $args = array();
  foreach ($list as $field => $value) {
    $args[] = $field.'='.urlencode($value);
  }
  return $BASEURL.'?'.join('&amp;', $args);
}

/**
* Copyright of generated output is attributed to $COPYRIGHTOWNER
* @global string $COPYRIGHTOWNER
*/
$COPYRIGHTOWNER = $CONFIG['main']['CopyrightOwner'];


/**
* If $VERBOSESQL is true, then all SQL statements will be dumped to the browser for debugging purposes
* @global boolean $VERBOSESQL
*/
$VERBOSESQL = $CONFIG['error_handling']['VerboseSQL'];

/**
* If $VERBOSEDATA is true then user data will be dumped to the browser for debugging purposes
* @global boolean $VERBOSEDATA
*/
$VERBOSEDATA = $CONFIG['error_handling']['VerboseData'];



ini_set("session.use_only_cookies",1); #don't permit ?PHPSESSID= stuff
#ini_set("session.cookie_lifetime",60*60*1); #login expires after x seconds

if ($CONFIG['error_handling']['AllWarnings']) {
  //this is nice for development but probably turn it off for production
  ini_set("error_reporting", E_ALL); #force all warnings to be echoed
  /** load all php files */
  define('LOAD_ALL_PHP_FILES', 1);
} else {
  ini_set("error_reporting", E_ERROR); #only errors should be echoed
  /** load only the php files required to fullfill this request) */
  define('LOAD_ALL_PHP_FILES', 0);
}
if (!empty($CONFIG['main']['ExtraIncludePath'])) {
  set_include_path($CONFIG['main']['ExtraIncludePath'].PATH_SEPARATOR.get_include_path());
}

/** load the language pack */
require_once 'i18n.php';

/**
* $BUMBLEBEEVERSION is the installed version of the software
* @global string $BUMBLEBEEVERSION
*/
$BUMBLEBEEVERSION = '1.2.0-cvs';

?> 
