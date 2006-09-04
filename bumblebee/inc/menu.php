<?php
/**
* Main menu for admin and normal users
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Misc
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

/** permission codes */
require_once 'inc/permissions.php';

/**
* Main menu for admin and normal users
*
* @package    Bumblebee
* @subpackage Misc
* @todo combine the data storage with action.php for cleaner implementation
*/
class UserMenu {
  /**
  * list of available actions
  * @var ActionListing
  */
  var $actionListing;
  /**
  * text output before start of menu block
  * @var string
  */
  var $menuPrologue   = '';
  /**
  * text output after end of menu block
  * @var string
  */
  var $menuEpilogue   = '';
  /**
  * html id for the enclosing DIV
  * @var string
  */
  var $menuDivId      = 'menulist';
  /**
  * html start-tag for the menu section
  * @var string
  */
  var $menuStart      = '<ul>';
  /**
  * html menu entry (complete!) for the link to the online help
  * @var string
  */
  var $menuHelp;
  /**
  * html stop-tag for the menu section
  * @var string
  */
  var $menuStop       = '</ul>';
  /**
  * html start-tag for each menu entry
  * @var string
  */
  var $itemStart      = '<li>';
  /**
  * html stop-tag for each menu entry
  * @var string
  */
  var $itemStop       = '</li>';
  /**
  * html id for div that alerts to current Masquerade setting
  * @var string
  */
  var $masqDivId      = 'masquerade';
  /**
  * html start-tag for each menu section
  * @var string
  */
  var $headerStart    = '<li class="menuSection">';
  /**
  * html stop-tag for each menu section
  * @var string
  */
  var $headerStop     = '</li>';
  /**
  * text to include at start of main menu section
  * @var string
  */
  var $mainMenuHeader;
  /**
  * text to include at start of admin menu section
  * @var string
  */
  var $adminHeader;
  /**
  * tag to use for the masq alert style (be careful of using div in an ul!)
  * @var string
  */
  var $masqAlertTag  = 'li';
  /**
  * display the menu
  * @var boolean
  */
  var $showMenu       = true;
  
  /**
  * logged in user's credentials
  * @var BumblebeeAuth
  */
  var $_auth;
  /**
  * currently selected action
  * @var string
  */
  var $_verb;
  
  /**
  * Constructor
  * @param BumblebeeAuth $auth  user's credentials
  * @param string        $verb  current action
  */
  function UserMenu($auth, $verb) {
    $this->_auth = $auth;
    $this->_verb = $verb;
    $this->menuHelp       = '<li class="last"><a href="http://bumblebeeman.sf.net/docs?section=__section__&amp;version=__version__">' . T_('Help') . '</a></li>';
    $this->mainMenuHeader = T_('Main Menu');
    $this->adminHeader    = T_('Administration');
  }
  
  /**
  * Generates an html representation of the menu
  * @return string       menu in html format
  */
  function getMenu() {
    if (! $this->showMenu) {
      return '';
    }
    $menu  = '<div'.($this->menuDivId ? ' id="'.$this->menuDivId.'"' :'' ).'>';
    $menu .= $this->menuStart;
    $menu .= $this->_constructMenuEntries();
    if ($this->_auth->amMasqed() && $this->_verb != 'masquerade') 
          $menu .= $this->_getMasqAlert();
    $menu .= $this->_getHelpMenu();
    $menu .= $this->menuStop;
    $menu .= '</div>';
    return $menu;
  }
  
  /**
  * Generates an html representation of the menu according to the current user's permissions
  * @return string       menu in html format
  */
  function _constructMenuEntries() {
    $t = '';
    if ($this->mainMenuHeader) {
      $t .= $this->headerStart.$this->mainMenuHeader.$this->headerStop;
    }
    $first_admin = true;
    #preDump($this->actionListing->actions);
    foreach ($this->actionListing->actions as $action) {
      #print $action->name(). " required=".$action->permissions();
      if ($action->menu_visible() && $this->_auth->permitted($action->permissions())) {
        #print " visible";
        if ($first_admin && $action->permitted(BBPERM_ADMIN)) {
          #print " admin header";
          $first_admin = false;
          if ($this->adminHeader) {
            $t .= $this->headerStart.$this->adminHeader.$this->headerStop;
          }
        }
        $t .= $this->itemStart
              .'<a href="'.makeURL($action->name()).'">'.$action->menu().'</a>'
            .$this->itemStop;
      }
      #print "<br/>";
    }
    return $t;
  }
  
  /**
  * Generates an html div to alert the user that masquerading is in action
  * @return string       menu in html format
  */
  function _getMasqAlert() {  
    $t = '<'.$this->masqAlertTag.' id="'.$this->masqDivId.'">'
             .'Mask: '.xssqw($this->_auth->eusername)
             .' (<a href="'.makeURL('masquerade', array('id'=>-1)).'">end</a>)'
        .'</'.$this->masqAlertTag.'>';
    return $t;
  }
    
  /**
  * Generates an html snippet to for the link to the online help
  * @return string       menu in html format
  * @global string       version of the Bumblebee installation (can serve different versions if necessary)
  */
  function _getHelpMenu() {
    global $BUMBLEBEEVERSION;
    $help = $this->menuHelp;
    $help = preg_replace(array('/__version__/',   '/__section__/'), 
                         array($BUMBLEBEEVERSION, $this->_verb), 
                         $help);
    return $help;
  }
  
} // class UserMenu
  

/**
* create a URL for an anchor
* @param string $action    action to be performed
* @param array  $list      (optional) key => value data to be added to the URL
* @return string URL
* @global string base URL for the installation
*/
function makeURL($action, $list=NULL) {
  global $BASEURL;
  $list = is_array($list) ? $list : array();
  $list['action'] = $action;
  $args = array();
  foreach ($list as $field => $value) {
    $args[] = $field.'='.urlencode($value);
  }
  return $BASEURL.'?'.join('&amp;', $args);
}

?> 
