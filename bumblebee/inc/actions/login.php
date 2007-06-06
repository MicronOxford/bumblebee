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
*
* path (bumblebee root)/inc/actions/login.php
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

require_once 'inc/bb/configreader.php';

/** parent object */
require_once 'inc/actions/actionaction.php';
/** Data reflector object */
require_once 'inc/formslib/datareflector.php';

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
  function ActionPrintLoginForm($auth, $PDATA) {
    parent::ActionAction($auth, $PDATA);
    $this->mungeInputData();
  }

  function go() {
    $conf = ConfigReader::getInstance();
    if ($conf->value('display', 'LoginPage') !== null && $conf->value('display', 'LoginPage') !== "") {
      echo $conf->value('display', 'LoginPage');
    }
    echo '<h2>' . T_('Login required').'</h2>';
    echo '<p>'  . T_('Please login to view or book instrument usage') . '</p>';
    $this->printLoginForm();
    if (! $this->readOnly) {
      $this->printDataReflectionForm($this->PD, 'reflection_');
    }
  }

  function printLoginForm() {
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
    if ($this->auth->changeUser()) {
      print "<input name='changeuser' type='hidden' value='1' />";
    }
  }

  function printDataReflectionForm($data, $basename='') {
    // save the rest of the query string for later use
    $reflector = new DataReflector($basename);
    $reflector->excludeLogin();
    #$reflector->exclude('changeuser');
/*    $reflector->addLimit(array('action', 'forceaction', $basename.'action', $basename.'forceaction'),
                         array('view', 'calendar'));*/
    echo $reflector->display($this->PD);
  }

}

?>
