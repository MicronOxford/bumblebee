<?php
/**
* Upgrade the database to the latest version used by Bumblebee
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Installer
*/

function loadInstalledConfig() {
  global $NON_FATAL_CONFIG;
  $REBASE_INSTALL = '..'.DIRECTORY_SEPARATOR;
  set_include_path($REBASE_INSTALL.PATH_SEPARATOR.get_include_path());
  $NON_FATAL_CONFIG = true;
  require_once 'inc/config.php';
}

?>
