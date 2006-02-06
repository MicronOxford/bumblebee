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
* Authentication is undertaken by the class BumbleBeeAuth
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
    echo '<h2>' . _('Login required').'</h2>';
    echo '<p>'  . _('Please login to view or book instrument usage') . '</p>';
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
      _('Username:'),
      _('Password:'),
      _('login')  );
  }
}

?> 
