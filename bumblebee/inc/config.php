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

ini_set("session.use_only_cookies",1); #don't permit ?PHPSESSID= stuff
#ini_set("session.cookie_lifetime",60*60*1); #login expires after x seconds


if ($conf->value('main', 'ExtraIncludePath', false)) {
  set_include_path($REBASE_INSTALL.($conf->value('main','ExtraIncludePath')).PATH_SEPARATOR.get_include_path());
}

/**
* $BUMBLEBEEVERSION is the installed version of the software
* @global string $BUMBLEBEEVERSION
*/
global $BUMBLEBEEVERSION;
$BUMBLEBEEVERSION = '1.1.5';

?>
