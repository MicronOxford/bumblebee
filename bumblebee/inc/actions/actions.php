<?php
/**
* List of all currently available actions
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Actions
*/

require_once 'inc/permissions.php';

/**
* An available action
*
* @package    Bumblebee
* @subpackage Actions
*/
class ActionData {
  /** @var string    name of the action in action= URL */
  var $name;
  /** @var string    title for the html page */
  var $title;
  /** @var string    name of the action for the menu */
  var $menu;
  /** @var string    name of the Action* class that should be instantiated */
  var $action_class;
  /** @var boolean   include the entry on the menu */
  var $menu_visible;
  /** @var integer   permissions required for this action */
  var $permissions;
  /** @var integer   menu order number (lowest numbers at top of menu) */
  var $menu_order = NULL;
  /** @var integer   name of the action that should follow next (defaults to $this->name) */
  var $next_action;

  /**
  *  Create a new ActionData object
  *
  * @param string $class          name of the class to be instantiated
  * @param string $file          name of the php file to be included
  * @param string $name   
  * @param string $title
  * @param string $menu
  * @param integer $permissions    from permissions.php (default: BBPERM_USER_ALL)
  * @param integer $menu_order     use negative number for not shown on menu, default: use in order instantiated into ActionListing
  * @param string $next_action
  */
  function ActionData($class, $file, $name, $title, $menu, $permissions=BBPERM_USER_ALL, 
                      $menu_order=NULL, $next_action=NULL) {
    $this->action_class = $class;
    $this->name         = $name;
    $this->file         = $file;
    $this->menu         = $menu;
    $this->title        = $title;
    $this->permissions  = $permissions;
    $this->menu_order   = $menu_order;
    $this->menu_visible = ($menu_order === NULL || $menu_order > 0);
    $this->next_action  = $next_action === NULL ? $name : $next_action;
  }
  
  function name() {
    return $this->name;
  }

  function title() {
    return $this->title;
  }

  function menu() {
    return $this->menu;
  }
  function permissions() {
    return $this->permissions;
  }

  function menu_order() {
    return $this->menu_order;
  }
  
  function menu_visible($permissions=-1) {
    return $this->menu_visible & $permissions;
  }
  
  function permitted($permissions) {
    return $this->permissions & $permissions;
  }
  
  function next_action($action=NULL) {
    if ($action === NULL)
      return $this->next_action;
    return $this->next_action = $action;
  }
  
  function action_class() {
    return $this->action_class;
  }
  
  function include_file() {
    return $this->file;
  }
  
}

/**
* List of all currently available actions
*
* Create data structures that can describe both the action-word to be acted
* on, as well as the title to be reflected in the HTML title tag.
*
* @todo this should be integrated with the Menu class
* @todo document the fixed up version
* @package    Bumblebee
* @subpackage Actions
*/
class ActionListing {
  /** @var array  list of ActionData objects */
  var $actions        = array();
  /** @var string name of default action when none is explicitly specified in the URL */
  var $default_action = 'view';
  
  function ActionListing() {
    $this->_populate();
    $this->_initialise();
  }

  /** @todo: conditionally initialise these -- at least limit the number of extraneous T_() calls for non admin users */
  function _populate() {
    $this->actions[] = new ActionData('ActionUnknown', 'unknownaction.php', 
        'unknown',    T_('Oops! I cannot do that!'), T_('Unknown'), BBPERM_USER_ALL, -1);
    $this->actions[] = new ActionData('ActionUnknown', 'unknownaction.php', 
        'forbidden!', T_('No, you cannot do that'), T_('Forbidden'), BBPERM_USER_ALL, -1);
    
    $this->actions[] = new ActionData('ActionView', 'view.php', 
        'view',       T_('View/edit instrument bookings'), T_('Main'), BBPERM_USER_ALL);
## JR ## Remove Change Password option
#    $this->actions[] = new ActionData('ActionPassword', 'password.php',
#        'passwd',     T_('Change password'), T_('Change password'),    BBPERM_USER_PASSWD);
    $this->actions[] = new ActionData('ActionPrintLoginForm', 'login.php', 
        'login',      T_('Login'), T_('Login'), BBPERM_USER_ALL, -1, 'view');
    $this->actions[] = new ActionData('ActionMasquerade', 'masquerade.php', 
        'masquerade', T_('Masquerade as another user'), T_('Masquerade'), BBPERM_MASQ);
    $this->actions[] = new ActionData('ActionLogout', 'logout.php', 
        'logout',     T_('Logout'), T_('Logout'), BBPERM_USER_ALL);
    
    $this->actions[] = new ActionData('ActionGroups', 'groups.php', 
        'groups',     T_('Manage groups'), T_('Edit groups'), BBPERM_ADMIN_GROUPS);
    $this->actions[] = new ActionData('ActionProjects', 'projects.php', 
        'projects',   T_('Manage projects'), T_('Edit projects'), BBPERM_ADMIN_PROJECTS);
    $this->actions[] = new ActionData('ActionUsers', 'users.php', 
        'users',      T_('Manage users'), T_('Edit users'), BBPERM_ADMIN_USERS);
    $this->actions[] = new ActionData('ActionInstruments', 'instruments.php', 
        'instruments', T_('Manage instruments'), T_('Edit instruments'), BBPERM_ADMIN_INSTRUMENTS);
    $this->actions[] = new ActionData('ActionConsumables', 'consumables.php', 
        'consumables', T_('Manage consumables'), T_('Edit consumables'), BBPERM_ADMIN_CONSUMABLES);
    $this->actions[] = new ActionData('ActionConsume', 'consume.php', 
        'consume',    T_('Record consumable usage'), T_('Use consumable'), BBPERM_ADMIN_CONSUME);
    $this->actions[] = new ActionData('ActionMasquerade', 'masquerade.php', 
        'masquerade', T_('Masquerade as another user'), T_('Masquerade'), BBPERM_MASQ);
    $this->actions[] = new ActionData('ActionCosts', 'costs.php', 
        'costs',      T_('Edit standard costs'), T_('Edit costs'), BBPERM_ADMIN_COSTS);
    $this->actions[] = new ActionData('ActionSpecialCosts', 'specialcosts.php', 
        'specialcosts',    T_('Edit or create special charges'), T_('Edit special costs'), BBPERM_ADMIN_COSTS, -1);
    $this->actions[] = new ActionData('ActionUserClass', 'userclass.php', 
        'userclass',  T_('Edit or create user class'), T_('Edit user class'), BBPERM_ADMIN_COSTS, -1);
    $this->actions[] = new ActionData('ActionInstrumentClass', 'instrumentclass.php', 
        'instrumentclass', T_('Edit or create instrument class'), T_('Edit instrument class'), BBPERM_ADMIN_COSTS, -1);
    $this->actions[] = new ActionData('ActionDeletedBookings', 'deletedbookings.php', 
        'deletedbookings', T_('View deleted bookings'), T_('Deleted bookings'), BBPERM_ADMIN_DELETEDBOOKINGS);
    $this->actions[] = new ActionData('ActionEmaillist', 'emaillist.php', 
        'emaillist',  T_('Email lists'), T_('Email lists'), BBPERM_ADMIN_EMAILLIST);
    $this->actions[] = new ActionData('ActionExport', 'export.php', 
        'export',     T_('Export data'), T_('Export data'), BBPERM_ADMIN_EXPORT);
    $this->actions[] = new ActionData('ActionBilling', 'billing.php', 
        'billing',    T_('Prepare billing summaries'), T_('Billing reports'), BBPERM_ADMIN_BILLING);
    $this->actions[] = new ActionData('ActionBackupDB', 'backupdatabase.php', 
        'backupdb',   T_('Backup database'), T_('Backup database'), BBPERM_ADMIN_BACKUPDB);

  }
  
  function _initialise() { 
    $actions = $this->actions;
    $this->actions = array();
    foreach ($actions as $action) {
      $this->actions[$action->name()] = $action;
    }
    $this->actions[''] = clone($this->actions[$this->default_action]);
    $this->actions['']->menu_visible = false;
  }
  
  function action_exists($action) {
    return isset($this->actions[$action]);
  }
  
  function permitted($action, $user_permissions) {
    return $this->actions[$action]->permitted($user_permissions);
  }

} //ActionListing

?> 
