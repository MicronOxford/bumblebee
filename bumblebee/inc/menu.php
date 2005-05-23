<?php
# $Id$
# the main menu for an admin user

class UserMenu {
  var $menuPrologue   = '';
  var $menuEpilogue   = '';
  var $menuDivId      = 'menulist';
  var $menuStart      = '<ul>';
  var $menuStop       = '</ul>';
  var $itemStart      = '<li>';
  var $itemStop       = '</li>';
  var $masqDivId      = 'masquerade';
  var $headerStart    = '<h3>';
  var $headerStop     = '</h3>';
  var $mainMenuHeader = 'Main Menu';
  var $adminHeader    = 'Administration';
  
  var $showMenu       = true;
  var $_auth;
  var $_verb;
  
  function UserMenu($auth, $verb) {
    $this->_auth = $auth;
    $this->_verb = $verb;
  }
  
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
    $menu .= $this->menuStop;
    $menu .= '</div>';
    return $menu;
  }
  
  function _getUserMenu() {
    global $BASEURL;
    $t = '';
    if ($this->mainMenuHeader) {
      $t .= $this->headerStart.$this->mainMenuHeader.$this->headerStop;
    }
    $t .= $this->itemStart
          .'<a href="'.$BASEURL.'/">Main</a>'
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
  
  function _getMasqAlert() {  
    global $BASEURL;
    $t = '<div id="'.$this->masqDivId.'">'
             .'Mask: '.$this->_auth->eusername
             .' (<a href="'.$BASEURL.'/masquerade/-1">end</a>)'
        .'</div>';
    return $t;
  }
  
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
        array('a'=>'costs',             't'=>'Edit std costs'),
      //array('a'=>'specialcosts',      't'=>'Edit special costs'),
        array('a'=>'deletedbookings',   't'=>'Deleted bookings'),
       #array('a'=>'bookmeta',          't'=>'Points system'),
       #array('a'=>'adminconfirm',      't'=>'Confirmations'),
        array('a'=>'emaillist',        't'=>'Email lists'),
      //array('a'=>'report',            't'=>'Report usage'),
        array('a'=>'export',            't'=>'Export data'),
      //array('a'=>'billing',           't'=>'Billing'),
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
  
  
} // class UserMenu
?> 
