<?php
/**
* Permit a local user to change their password
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Actions
*
* path (bumblebee root)/inc/actions/password.php
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

/** parent object */
require_once 'inc/actions/actionaction.php';
/** user editing object */
require_once 'inc/bb/user.php';

/**
* Permit a local user to change their password
*
* @package    Bumblebee
* @subpackage Actions
* @todo //TODO: extend form to include current password for auth
*/
class ActionPassword extends ActionAction {

  /**
  * Initialising the class
  *
  * @param  BumblebeeAuth $auth  Authorisation object
  * @param  array $pdata   extra state data from the call path
  * @return void nothing
  */
  function ActionPassword($auth, $pdata) {
    parent::ActionAction($auth, $pdata);
    $this->mungeInputData();
  }

  function go() {
    if ($this->readOnly) $this->_dataCleanse('id');
    $this->edit();
    echo "<br /><br /><a href='".makeURL('')."'>".T_('Return to main menu')."</a>";
  }

  function edit() {
    $user = new User($this->auth, $this->auth->uid, true);
    $user->update($this->PD);
    #$project->fields['defaultclass']->invalid = 1;
    $user->checkValid();
    echo $this->reportAction($user->sync(),
          array(
              STATUS_OK =>   T_('Password changed successfully.'),
              STATUS_ERR =>  T_('Password could not be changed: ').$user->errorMessage
          )
        );
    echo $user->display();
    echo "<input type='submit' name='submit' value='".T_('Change password')."' />";
  }
}

?>
