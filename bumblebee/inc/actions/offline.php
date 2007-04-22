<?php
/**
* System offline message
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Actions
*
* path (bumblebee root)/inc/actions/login.php
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

/** parent object */
require_once 'inc/actions/actionaction.php';

/**
* Print a polite message to inform the user that the system is offline
*
* Authentication is undertaken by the class BumblebeeAuth
* @package    Bumblebee
* @subpackage Actions
*/
class ActionOffline extends ActionAction {

  /**
  * Initialising the class
  *
  * @return void nothing
  */
  function ActionOffline($auth, $PDATA) {
    parent::ActionAction($auth, $PDATA);
    $this->mungeInputData();
  }

  function go() {
    $conf = ConfigReader::getInstance();

    echo T_('Sorry, this service is not currently available. Please try again later.');

    echo '<div class="error">'
            .join($conf->status->messages, "\n\n")
          .'</div>';

    // Cause the page to reload back to the start page periodically to see if things
    // are alive again.
    $url = makeURL();
    $interval = 60*1000;   // refresh interval in milliseconds
    echo "
      <script type='text/javascript'>
        setTimeout('window.document.location.href=\"$url\"', $interval);
      </script>
      ";
  }

}

?>
