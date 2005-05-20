<?php
# $Id$
# work out what it was we were supposed to be doing
# $action is set based on what we are supposed to do
# this is then acted upon later in the page

// basic functions (user functions)
include_once 'login.php';
include_once 'logout.php';
include_once 'view.php';
include_once 'password.php';
#include_once 'book.php';

//admin functions
//FIXME: can thesee be selectively included?
include_once 'groups.php';
include_once 'projects.php';
include_once 'users.php';
include_once 'instruments.php';
include_once 'consumables.php';
include_once 'consume.php';
include_once 'deletedbookings.php';
include_once 'masquerade.php';
include_once 'costs.php';
include_once 'specialcosts.php';
//include_once 'adminconfirm.php';
include_once 'emaillist.php';
include_once 'report.php';
include_once 'export.php';
//include_once 'billing.php';
include_once 'backupdatabase.php';

include_once 'unknownaction.php';
include_once 'inc/typeinfo.php';
include_once 'actions.php';

class ActionFactory {
  var $_verb;
  var $_original_verb;
  var $title;
  var $_action;
  var $_auth;
  var $PDATA;
  var $nextaction;
  var $actionListing;
  var $actionTitles;
  
  function ActionFactory($auth) {
    global $BASEURL;
    $this->_auth = $auth;
    $this->PDATA = $this->_eatPathInfo();
    $list = new ActionListing();
    $this->actionListing = $list->listing;
    $this->actionTitles = $list->titles;
    $this->_verb = $this->_checkActions();
    $this->nextaction = $BASEURL.'/'.$this->_verb;  // override this in _makeAction if needed.
    $this->title = $this->actionTitles[$this->_verb];
    $this->_action = $this->_makeAction();
  }
  
  function go() {
    $this->_action->go();
  }

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

  function _actionRestart($auth, $newaction) {
    global $action;
    #$_POST['action']=$newaction;
    $this->_verb=$newaction;
    $this->_action = $this->_makeAction();
    $this->go();
  }
  
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
  
  function ob_flush_ok() {
    return $this->_action->ob_flush_ok;
  }
  
  function returnBufferedStream() {
    if (method_exists($this->_action, 'sendBufferedStream')) {
      $this->_action->sendBufferedStream();
    }
  }

  function _makeaction() {
    global $BASEURL;
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
