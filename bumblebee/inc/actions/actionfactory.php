<?php
/**
* Create Action object that will do whatever category of work is required in this invocation 
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Actions
*/

// basic functions (user functions)
include_once 'login.php';
include_once 'logout.php';
include_once 'view.php';
include_once 'password.php';

// only some users can masquerade, but include this by default, 
// checking permissions is done by the Masquerade class itself
include_once 'masquerade.php';

//admin functions: only include these files if they are necessary (security + efficiency)
//these classes do not check permissions of the user -- this must be done by the instantiating code
if ($auth->isadmin) {
  include_once 'groups.php';
  include_once 'projects.php';
  include_once 'users.php';
  include_once 'instruments.php';
  include_once 'consumables.php';
  include_once 'consume.php';
  include_once 'deletedbookings.php';
  include_once 'costs.php';
  include_once 'specialcosts.php';
  include_once 'userclass.php';
  include_once 'instrumentclass.php';
  //include_once 'adminconfirm.php';
  include_once 'emaillist.php';
  // include_once 'report.php';
  include_once 'export.php';
  include_once 'billing.php';
  include_once 'backupdatabase.php';
}

include_once 'unknownaction.php';
include_once 'inc/typeinfo.php';
include_once 'actions.php';

/**
* Factory class for creating Action objects
*  
* An Action is a single operation requested by the user. What action is to be performed
* is determined by the action-triage mechanism in the class ActionFactory.
*
* Everything done by an application suite (e.g. edit/create a user) can be reduced to 
* broad categories of actions (e.g. edituser) which can be encapsulated within an 
* object framework in which every object has the same interface.
*
* The invoking code is thus boiled down to something quite simple:
* <code>
* $action = new ActionFactory($params);
* $action->go();
* </code>
* where ActionFactory does the work of deciding what is to be done on this
* invocation and instantiates the appropriate action object.
* 
* The action object then actually performs the tasks desired by the user.
*
* Here, we use the data from the browser (a PATH_INFO variable from the URL in the form
* index.php/user) to triage the transaction.
*/
class ActionFactory {
  /** 
  * the user-supplied "verb" (name of the action) e.g. "edituser"
  * @var string
  */
  var $_verb;
  /** 
  * the actual user-supplied verb... we may pretend to do something else due to permissions
  * @var string
  */
  var $_original_verb;
  /** 
  * Each action has a description associated with it that we will put into the HTML title tag
  * @var string
  */
  var $title;
  /** 
  * The action object (some descendent of the ActionAction class)
  * @var ActionAction
  */
  var $_action;
  /** 
  * The user's login credentials object
  * @var BumbleBeeAuth
  */
  var $_auth;
  /** 
  * user-supplied data from the PATH_INFO section of the URL
  * @var array
  */
  var $PDATA;
  /** 
  * The 'verb' that should follow this current action were a standard workflow being followed
  * @var string
  */
  var $nextaction;
  /** 
  * list of action verbs available
  * @var array
  */
  var $actionListing;
  /** 
  * list of action titles available
  * @var array
  */
  var $actionTitles;
  
  /** 
  * Constructor for the class
  *
  * - Parse the submitted data
  * - work out what action we are supposed to be performing
  * - set up the title tag for the browser
  * - create the ActionAction descendent object that will perform the task
  *
  * @param BumbleBeeAuth $auth  user login credentials object
  */
  function ActionFactory($auth) {
    global $BASEURL;
    $this->_auth = $auth;
    $this->PDATA = $this->_eatPathInfo();
    $list = new ActionListing();
    $this->actionListing = $list->listing;
    $this->actionTitles = $list->titles;
    $this->_verb = $this->_checkActions();
    $this->nextaction = $BASEURL.'/'.$this->_verb.'/';  // override this in _makeAction if needed.
    $this->title = $this->actionTitles[$this->_verb];
    $this->_action = $this->_makeAction();
  }
  
  /** 
  * Fire the action: make things actually happen now
  */
  function go() {
    $this->_action->go();
  }

  /** 
  * Determine what action should be performed.
  *
  * This is done by:
  * - checking that the user is logged in... if not, they *must* login
  * - looking for hints in the user-supplied data for what the correct action is
  * - checking that the user is an admin user if admin functions were requested
  *
  * @return string the name of the action (verb) to be undertaken
  */
  function _checkActions() {
    $action = '';
  
    # first, we need to determine if we are actually logged in or not
    # if we are not logged in, the the action *has* to be 'login'
    if (! $this->_auth->isLoggedIn()) return 'login';
  
    #We can have action verbs past to us in three different ways.
    # 1. first PATH_INFO
    # 2. explicit PATH_INFO action=
    # 3. action= form fields 
    # Later specifications are used in preference to earlier ones
  
    $explicitaction = issetSet($this->PDATA, 'forceaction');
    $pathaction = issetSet($this->PDATA, 'action');
    $formaction = issetSet($_POST, 'action');
    #$pathaction = $PDATA['action'];
    #$formaction = $_POST['action'];
  
    if ($explicitaction) $action = $explicitaction;
    if ($pathaction) $action = $pathaction;
    if ($formaction) $action = $formaction;
  
    $this->_original_verb = $action;
    
    // dump if unknown action
    if (! isset($this->actionListing[$action])) {
      return 'unknown';
    }
    // protect admin functions
    if ($this->actionListing[$action] > 999 && ! $this->_auth->isadmin) {
      return 'forbidden!';
    }
  
    # We also need to check to see if we are trying to change privileges
    #if (isset($_POST['changemasq']) && $_POST['changemasq']) return 'masquerade';
    
    return $action;
  }

  /** 
  * Trigger a restart of the action or a new action
  *
  * Sometimes, an action may need to be restarted or the action changed (e.g. logout => login)
  *
  * @param string $newaction  new verb for the new action
  */
  function _actionRestart($newaction) {
    $this->_verb=$newaction;
    $this->_action = $this->_makeAction();
    $this->go();
  }
  
  /** 
  * Parse the user-supplied data in PATH_INFO part of URL
  *
  * @returns array  (key => $data)
  */
  function _eatPathInfo() {
    $pd = array();
    $pathinfo = issetSet($_SERVER, 'PATH_INFO');
    if ($pathinfo) {
      $path = explode('/', $pathinfo);
      $pd['action'] = $path[1];
      $actions = preg_grep("/^action=/", $path);
      $forceaction = array_keys($actions);
      if (isset($forceaction[0])) {
        preg_match("/^action=(.+)/",$path[$forceaction[0]],$m);
        $pd['forceaction'] = $m[1];
        $max = $forceaction[0];
      } else {
        $max = count($path);
      }
      for($i=2; $i<$max; $i++) {
        $pd[$i-1] = $path[$i];
      }
    }
    return $pd;
  }
  
  /** 
  * Is it ok to allow the HTML template to dump to the browser from the output buffer?
  *
  * (see BufferedAction descendents)
  *
  * @returns boolean  ok to dump to browser
  */
  function ob_flush_ok() {
    return $this->_action->ob_flush_ok;
  }
  
  /** 
  * Cause buffered actions to output their data to the browser
  *
  * (see BufferedAction descendents)
  */
  function returnBufferedStream() {
    if (method_exists($this->_action, 'sendBufferedStream')) {
      $this->_action->sendBufferedStream();
    }
  }

  /** 
  * create the action object (a descendent of ActionAction) for the user-defined verb
  */
  function _makeaction() {
    global $BASEURL;        // allow for overriding of actions
    $act = $this->actionListing;
    switch ($act[$this->_verb]) {
      case $act['login']:
        $this->nextaction = $BASEURL.'/view';
        return new ActionPrintLoginForm($this->_auth, $this->PDATA);
      case $act['logout']:
        $this->_auth->logout();
        return new ActionLogout($this->_auth, $this->PDATA);
      case $act['view']:
        return new ActionView($this->_auth, $this->PDATA);
      case $act['passwd']:
        return new ActionPassword($this->_auth, $this->PDATA);
      // instrument-admin only
      case $act['masquerade']:
        return new ActionMasquerade($this->_auth, $this->PDATA);
      // admin only
      case $act['groups']:
        return new ActionGroup($this->_auth, $this->PDATA);
      case $act['projects']:
        return new ActionProjects($this->_auth, $this->PDATA);
      case $act['users']:
        return new ActionUsers($this->_auth, $this->PDATA);
      case $act['instruments']:
        return new ActionInstruments($this->_auth, $this->PDATA);
      case $act['consumables']:
        return new ActionConsumables($this->_auth, $this->PDATA);
      case $act['consume']:
        return new ActionConsume($this->_auth, $this->PDATA);
      case $act['deletedbookings']:
        return new ActionDeletedBookings($this->_auth, $this->PDATA);
      case $act['costs']:
        return new ActionCosts($this->_auth, $this->PDATA);
      case $act['specialcosts']:
        return new ActionSpecialCosts($this->_auth, $this->PDATA);
      case $act['instrumentclass']:
        return new ActionInstrumentClass($this->_auth, $this->PDATA);
      case $act['userclass']:
        return new ActionUserClass($this->_auth, $this->PDATA);
      /*case $act['bookmeta']:
        return new ActionBookmeta();
      case $act['adminconfirm']:
        return new ActionAdminconfirm();*/
      case $act['emaillist']:
        return new ActionEmaillist($this->_auth, $this->PDATA);
      case $act['backupdb']:
        return new ActionBackupDB($this->_auth, $this->PDATA);
      case $act['report']:
        return new ActionReport($this->_auth, $this->PDATA);
      case $act['export']:
        return new ActionExport($this->_auth, $this->PDATA);
      case $act['billing']:
        return new ActionBilling($this->_auth, $this->PDATA);
      case $act['forbidden!']:
        return new ActionUnknown($this->_original_verb, 1);
      default:
        return new ActionUnknown($this->_original_verb);
    }
  }
     
}
 //class ActionFactory
 
?>
