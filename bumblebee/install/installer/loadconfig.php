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
  mungeIncludePath($REBASE_INSTALL);
  $NON_FATAL_CONFIG = true;
  require_once 'inc/config.php';
}

function mungeIncludePath($path) {
  static $done = false;

  if ($done) return;

  $fullpath = array();
  foreach (explode(PATH_SEPARATOR, get_include_path()) as $dir) {
    if (substr($dir, 0, 1) == DIRECTORY_SEPARATOR) {
      # don't munge absolute paths
      $fullpath[] = $dir;
    } else {
      $fullpath[] = $path.$dir;
    }
  }
  set_include_path(join($fullpath, PATH_SEPARATOR));
  $done = true;
}

?>
