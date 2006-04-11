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

function logmsg($priority, $message) {
  global $auth, $action, $CONFIG;
  if (! $CONFIG['error_handling']['UseLogFile'] ||
      $priority >= $CONFIG['error_handling']['LogLevel']) return;

  // Log line format:
  // IP username (uid) [DD/Mon/YYYY:HH:MM:SS TZ] "action" "Message"\n

  $date     = gmdate("d/M/Y:H:i:s O");
  $ip       = is_object($auth) ? $auth->getRemoteIP() : '-';
  $username = is_object($auth) ? $auth->username      : '-';
  $uid      = is_object($auth) ? $auth->uid           : '-';
  $verb     = is_object($auth) ? $action->_verb       : '-';

  $logstring = "$ip $username ($uid) [$date] \"$verb\" \"$message\"\n";

  error_log($logstring, 3, $CONFIG['error_handling']['LogFile']);
}
?> 
