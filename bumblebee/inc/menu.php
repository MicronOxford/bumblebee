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

/**
* Main menu for admin and normal users
*
* @todo combine the data storage with action.php for cleaner implementation
*/
class UserMenu {
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
  var $menuHelp       = '<li class="last"><a href="http://bumblebeeman.sf.net/docs?section=__section__&amp;version=__version__">Help</a></li>';
  // Use local copy of the docs not the sf.net version:
  #var $menuHelp       = '<li class="last"><a href="/docs?section=__section__&amp;version=__version__">Help</a></li>';
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
  var $mainMenuHeader = 'Main Menu';
  /**
  * text to include at start of admin menu section
  * @var string
  */
  var $adminHeader    = 'Administration';
  
  /**
  * display the menu
  * @var boolean
  */
  var $showMenu       = true;
  
  /**
  * logged in user's credentials
  * @var BumbleBeeAuth
  */
  var $_auth;
  /**
  * currently selected action
  * @var string
  */
  var $_verb;
  
  /**
  * Constructor
  * @param BumbleBeeAuth $auth  user's credentials
  * @param string        $verb  current action
  */
  function UserMenu($auth, $verb) {
    $this->_auth = $auth;
    $this->_verb = $verb;
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
    $menu .= $this->_getUserMenu();
    if ($this->_auth->isadmin) 
          $menu .= $this->_getAdminMenu();
    if ($this->_auth->amMasqed() && $this->_verb != 'masquerade') 
          $menu .= $this->_getMasqAlert();
    $menu .= $this->_getHelpMenu();
    $menu .= $this->menuStop;
    $menu .= '</div>';
    return $menu;
  }
  
  /**
  * Generates an html representation of the ordinary user's section of the menu
  * @return string       menu in html format
  */
  function _getUserMenu() {
    global $BASEURL;
    $t = '';
    if ($this->mainMenuHeader) {
      $t .= $this->headerStart.$this->mainMenuHeader.$this->headerStop;
    }
    $t .= $this->itemStart
          .'<a href="'.$BASEURL.'/view/">Main</a>'
        .$this->itemStop;
    if ($this->_auth->localLogin) {
      $t .= $this->itemStart
              .'<a href="'.$BASEURL.'/passwd/">Change Password</a>'
            .$this->itemStop;
    }
    if ($this->_auth->masqPermitted()) {
      $t .= $this->itemStart
              .'<a href="'.$BASEURL.'/masquerade/">Masquerade</a>'
            .$this->itemStop;
    }
    $t .= $this->itemStart
            .'<a href="'.$BASEURL.'/logout/">Logout</a>'
          .$this->itemStop;
    return $t;
  }
  
  /**
  * Generates an html div to alert the user that masquerading is in action
  * @return string       menu in html format
  */
  function _getMasqAlert() {  
    global $BASEURL;
    $t = '<div id="'.$this->masqDivId.'">'
             .'Mask: '.$this->_auth->eusername
             .' (<a href="'.$BASEURL.'/masquerade/-1">end</a>)'
        .'</div>';
    return $t;
  }
  
  /**
  * Generates an html representation of the admin user's section of the menu
  * @return string       menu in html format
  */
  function _getAdminMenu() {
    global $BASEURL;
    $menu = array(
        array('a'=>'groups',            't'=>'Edit groups'),
        array('a'=>'projects',          't'=>'Edit projects'),
        array('a'=>'users',             't'=>'Edit users'),
        array('a'=>'instruments',       't'=>'Edit instruments'),
        array('a'=>'consumables',       't'=>'Edit consumables'),
        array('a'=>'consume',           't'=>'Use consumable'),
        array('a'=>'masquerade',        't'=>'Masquerade'),
        array('a'=>'costs',             't'=>'Edit costs'),
       #array('a'=>'specialcosts',      't'=>'Edit special costs'),
        array('a'=>'deletedbookings',   't'=>'Deleted bookings'),
       #array('a'=>'bookmeta',          't'=>'Points system'),
       #array('a'=>'adminconfirm',      't'=>'Confirmations'),
        array('a'=>'emaillist',        't'=>'Email lists'),
      //array('a'=>'report',            't'=>'Report usage'),
        array('a'=>'export',            't'=>'Export data'),
        array('a'=>'billing',           't'=>'Billing reports'),
        array('a'=>'backupdb',          't'=>'Backup database')
    );
    $t = '';
    if ($this->adminHeader) {
      $t .= $this->headerStart.$this->adminHeader.$this->headerStop;
    }
    foreach ($menu as $entry) {
      $t .= $this->itemStart
            .'<a href="'.$BASEURL.'/'.$entry['a'].'/">'.$entry['t'].'</a>'
          .$this->itemStop;
    }
    return $t;
  }
  
  /**
  * Generates an html snippet to for the link to the online help
  * @return string       menu in html format
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
?> 
