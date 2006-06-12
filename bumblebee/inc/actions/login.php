<?php
/**
* Print a polite login form
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
* Print a polite login form
*  
* Authentication is undertaken by the class BumblebeeAuth
* @package    Bumblebee
* @subpackage Actions
*/
class ActionPrintLoginForm extends ActionAction {
  
  /**
  * Initialising the class 
  * 
  * @return void nothing
  */
  function ActionPrintLoginForm() {
  }

  function go() {
    global $CONFIG;
    if (isset($CONFIG['display']['LoginPage']) && ! empty($CONFIG['display']['LoginPage'])) {
      echo $CONFIG['display']['LoginPage'];
    }
    echo '<h2>' . T_('Login required').'</h2>';
    echo '<p>'  . T_('Please login to view or book instrument usage') . '</p>';
    printf('
      <table>
      <tr>
        <td>%s</td>
        <td><input name="username" type="text" size="16" /></td>
      </tr>
      <tr>
        <td>%s</td>
        <td><input name="pass" type="password" size="16" /></td>
      </tr>
      <tr>
        <td></td>
        <td><input name="submit" type="submit" value="%s" /></td>
      </tr>
      </table>',
      T_('Username:'),
      T_('Password:'),
      T_('login')  );
  }
}

?> 
