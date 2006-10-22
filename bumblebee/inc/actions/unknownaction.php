<?php
/**
* Error handling class for unknown actions
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Actions
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

/** parent object */
require_once 'inc/actions/actionaction.php';

/**
* Error handling class for unknown actions
* @package    Bumblebee
* @subpackage Actions
*/
class ActionUnknown extends ActionAction {
  var $action;
  var $forbiden;

  /**
  * Initialising the class
  *
  * @param  string  $action requested action ('verb')
  * @param  boolean $forbidden  (optional)
  * @return void nothing
  */
  function ActionUnknown($action, $forbidden=0) {
    parent::ActionAction('','');
    $this->action = $action;
    $this->forbidden = $forbidden;
  }

  function go() {
    $conf = ConfigReader::getInstance();
    echo '<h2>'.T_('Error').'</h2><div class="msgerror">';
    if ($this->forbidden) {
      echo '<p>'
          .sprintf(T_('Sorry, you don\'t have permission to perform the action "%s".'), xssqw($this->action))
          .'</p>';
    } else {
      echo '<p>'
          .sprintf(T_('An unknown error occurred. I was asked to perform the action "%s", but I don\'t know how to do that.'), xssqw($this->action))
          .'</p>';
    }
    echo '<p>'
        .sprintf(T_('Please contact <a href="mailto:%s">the system administrator</a> for more information.'), $conf->AdminEmail)
        .'</p></div>';
  }



} //ActionUnknown
?>
