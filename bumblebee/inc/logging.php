<?php
/**
* Flat file logging of Bumblebee events
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

function logmsg($priority, $message) {
  global $auth, $action;
  $conf = ConfigReader::getInstance();
  if (! $conf->value('error_handling', 'UseLogFile') ||
      $priority >= $conf->value('error_handling', 'LogLevel')) return;

  // Log line format:
  // IP username (uid) [DD/Mon/YYYY:HH:MM:SS TZ] "action" "Message"\n

  $date     = gmdate("d/M/Y:H:i:s O");
  $ip       = is_object($auth)   ? $auth->getRemoteIP() : '-';
  $username = is_object($auth)   ? $auth->username      : '-';
  $uid      = is_object($auth)   ? $auth->uid           : '-';
  $verb     = is_object($action) ? $action->_verb       : '-';

  $logstring = "$ip $username ($uid) [$date] \"$verb\" \"$message\"\n";

  error_log($logstring, 3, $conf->value('error_handling', 'LogFile'));
}
?>
