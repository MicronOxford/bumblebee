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

function checkConfigured() {
  global $BUMBLEBEEVERSION;
  $conf = & ConfigReader::getInstance();
  if (version_compare($conf->value('meta', 'configuredversion', $BUMBLEBEEVERSION),
                      $BUMBLEBEEVERSION
                     ) === -1
      ||
      version_compare($conf->value('meta', 'dbversion',         $BUMBLEBEEVERSION),
                      $BUMBLEBEEVERSION
                     ) === -1
      ) {
    // old version found
    $conf->status->offline = true;
    if (($conf->value('meta', 'configuredversion', $BUMBLEBEEVERSION) == '0.0.0')
        && file_exists('install')
        ) {
      // version: needs initial installation
      $conf->status->installRequired = true;
      $conf->status->messages[] = sprintf(T_('This appears to be a new installation and requires configuring. Please use the installation pages to finish either <a href="%s">the installation process</a> or <a href="%s">the upgrade process</a>.'),
                makeAbsURL('/install/install.php'),
                makeAbsURL('/install/upgrade.php'));
    } else {
      // old version: needs upgrading
      $conf->status->upgradeRequired = true;
      $conf->status->messages[] = sprintf(T_('The system is unavailable while the software is being upgraded. If you are the Bumblebee administrator, please go to the <a href="%s">installation pages</a> to finish the upgrade process'),
                makeAbsURL('/install/upgrade.php'));
    }
    #$conf->status->messages[] = "Software: $BUMBLEBEEVERSION, db: ". $conf->value('meta', 'dbversion', '2.0'). " config: ". $conf->value('meta', 'configuredversion', '2.0');
    #$conf->status->messages[] = file_exists('install') ? "Installer found" : "no installer";
  }
}

/**
* $BUMBLEBEEVERSION is the installed version of the software
* @global string $BUMBLEBEEVERSION
*/
global $BUMBLEBEEVERSION;
$BUMBLEBEEVERSION = '1.1.5';

?>
