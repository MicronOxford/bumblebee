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
require_once 'inc/actions/actionaction.php';

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
    $this->auth->logout();
  }

  function go() {
    $url = makeURL('');
    echo '<h2>'._('Successfully logged out').'</h2>'
        .'<p>'._('Thank you for using Bumblebee!').'</p>'
        ."<p>(<a href='$url'>" . _('login') . '</a>)</p>';
    echo "<script type='text/javascript'>self.setTimeout('window.document.location.href=\"$url\"', 2000);</script>";
  }
}

?> 
