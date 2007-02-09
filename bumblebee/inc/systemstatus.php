<?php
/**
* Maintain information about the status of the installation
*
* @author     Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Misc
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

/**
* Keep tabs on what is and what isn't operational within the system
*
* @package    Bumblebee
* @subpackage Misc
*/
class SystemStatus {
  /** @var boolean   the db subsystem is working */
  var $database = false;

  var $offline = false;

  var $messages = array();
}

?>
