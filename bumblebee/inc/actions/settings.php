<?php
/**
* Change the system settings
*
* @author     Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Actions
*
* path (bumblebee root)/inc/actions/settings.php
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

/** Settings object */
require_once 'inc/bb/settings.php';

/**
* Change the system settings
* @package    Bumblebee
* @subpackage Actions
*/
class ActionSettings extends ActionAction {

  /**
  * Initialising the class
  *
  * @param  BumblebeeAuth $auth  Authorisation object
  * @param  array $pdata   extra state data from the call path
  * @return void nothing
  */
  function ActionSettings($auth, $pdata) {
    parent::ActionAction($auth, $pdata);
    $this->mungeInputData();
  }

  function go() {
    $this->editConfig();
  }

  /**
  * Display the current config and accept changes
  */
  function editConfig() {
    $set = new Settings();
    $set->update($this->PD);
    $set->checkValid();
    echo $this->reportAction($set->sync(),
          array(
              STATUS_OK =>   T_('Settings updated'),
              STATUS_ERR =>  T_('Settings could not be changed:').' '.$set->errorMessage
          )
        );
    echo $set->display();
    $submit = T_('Update configuration');
    echo "<input type='submit' name='submit' value='$submit' />";
  }


}

?>
