<?php
/**
* Thank the user for using the system.
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Actions
*/

include_once 'inc/actions/actionaction.php';

/**
* Thank the user for using the system.
*  
* Destruction of login credentials is undertaken by the class BumbleBeeAuth
*/
class ActionLogout extends ActionAction {

  function ActionLogout($auth, $pdata) {
    parent::ActionAction($auth, $pdata);
  }

  function go() {
    global $BASEURL;
    //$this->auth->logout();
    echo "
      <h2>Successfully logged out</h2>
      <p>Thank you for using Bumblebee!</p>
      <p>(<a href='$BASEURL/'>login</a>)</p>
    ";
  }
}

?> 
