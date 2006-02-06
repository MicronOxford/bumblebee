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

  function _populate() {
    $this->actions[] = new ActionData('ActionUnknown', 'unknownaction.php', 
        'unknown',    _('Oops! I cannot do that!'), _('Unknown'), BBPERM_USER_ALL, -1);
    $this->actions[] = new ActionData('ActionUnknown', 'unknownaction.php', 
        'forbidden!', _('No, you cannot do that'), _('Forbidden'), BBPERM_USER_ALL, -1);
    
    $this->actions[] = new ActionData('ActionView', 'view.php', 
        'view',       _('View/edit instrument bookings'), _('Main'), BBPERM_USER_ALL);
    $this->actions[] = new ActionData('ActionPassword', 'password.php', 
        'passwd',     _('Change password'), _('Change password'),    BBPERM_USER_PASSWD);
    $this->actions[] = new ActionData('ActionPrintLoginForm', 'login.php', 
        'login',      _('Login'), _('Login'), BBPERM_USER_ALL, -1, 'view');
    $this->actions[] = new ActionData('ActionMasquerade', 'masquerade.php', 
        'masquerade', _('Masquerade as another user'), _('Masquerade'), BBPERM_MASQ);
    $this->actions[] = new ActionData('ActionLogout', 'logout.php', 
        'logout',     _('Logout'), _('Logout'), BBPERM_USER_ALL);
    
    $this->actions[] = new ActionData('ActionGroups', 'groups.php', 
        'groups',     _('Manage groups'), _('Edit groups'), BBPERM_ADMIN_GROUPS);
    $this->actions[] = new ActionData('ActionProjects', 'projects.php', 
        'projects',   _('Manage projects'), _('Edit projects'), BBPERM_ADMIN_PROJECTS);
    $this->actions[] = new ActionData('ActionUsers', 'users.php', 
        'users',      _('Manage users'), _('Edit users'), BBPERM_ADMIN_USERS);
    $this->actions[] = new ActionData('ActionInstruments', 'instruments.php', 
        'instruments', _('Manage instruments'), _('Edit instruments'), BBPERM_ADMIN_INSTRUMENTS);
    $this->actions[] = new ActionData('ActionConsumables', 'consumables.php', 
        'consumables', _('Manage consumables'), _('Edit consumables'), BBPERM_ADMIN_CONSUMABLES);
    $this->actions[] = new ActionData('ActionConsume', 'consume.php', 
        'consume',    _('Record consumable usage'), _('Use consumable'), BBPERM_ADMIN_CONSUME);
    $this->actions[] = new ActionData('ActionMasquerade', 'masquerade.php', 
        'masquerade', _('Masquerade as another user'), _('Masquerade'), BBPERM_MASQ);
    $this->actions[] = new ActionData('ActionCosts', 'costs.php', 
        'costs',      _('Edit standard costs'), _('Edit costs'), BBPERM_ADMIN_COSTS);
    $this->actions[] = new ActionData('ActionSpecialCosts', 'specialcosts.php', 
        'specialcosts',    _('Edit or create special charges'), _('Edit special costs'), BBPERM_ADMIN_COSTS, -1);
    $this->actions[] = new ActionData('ActionUserClass', 'userclass.php', 
        'userclass',  _('Edit or create user class'), _('Edit user class'), BBPERM_ADMIN_COSTS, -1);
    $this->actions[] = new ActionData('ActionInstrumentClass', 'instrumentclass.php', 
        'instrumentclass', _('Edit or create instrument class'), _('Edit instrument class'), BBPERM_ADMIN_COSTS, -1);
    $this->actions[] = new ActionData('ActionDeletedBookings', 'deletedbookings.php', 
        'deletedbookings', _('View deleted bookings'), _('Deleted bookings'), BBPERM_ADMIN_DELETEDBOOKINGS);
    $this->actions[] = new ActionData('ActionEmaillist', 'emaillist.php', 
        'emaillist',  _('Email lists'), _('Email lists'), BBPERM_ADMIN_EMAILLIST);
    $this->actions[] = new ActionData('ActionExport', 'export.php', 
        'export',     _('Export data'), _('Export data'), BBPERM_ADMIN_EXPORT);
    $this->actions[] = new ActionData('ActionBilling', 'billing.php', 
        'billing',    _('Prepare billing summaries'), _('Billing reports'), BBPERM_ADMIN_BILLING);
    $this->actions[] = new ActionData('ActionBackupDB', 'backupdatabase.php', 
        'backupdb',   _('Backup database'), _('Backup database'), BBPERM_ADMIN_BACKUPDB);

  }
  
  function _initialise() { 
    $actions = $this->actions;
    $this->actions = array();
    foreach ($actions as $action) {
      $this->actions[$action->name()] = $action;
    }
    $this->actions[''] = $this->actions[$this->default_action];
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
