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

/** parent object */
include_once 'inc/actions/actionaction.php';

/**
* Thank the user for using the system.
*  
* Destruction of login credentials is undertaken by the class BumbleBeeAuth
* @package    Bumblebee
* @subpackage Actions
*/
class ActionLogout extends ActionAction {

  /**
  * Initialising the class 
  * 
  * @param  BumbleBeeAuth $auth  Authorisation object
  * @param  array $pdata   extra state data from the call path
  * @return void nothing
  */
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
