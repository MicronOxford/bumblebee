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

/**
* List of all currently available actions
*
* Create data structures that can describe both the action-word to be acted
* on, as well as the title to be reflected in the HTML title tag.
*
* @todo this is ugly as sin. 
* @todo it should have a much cleaner implementation than this -- use a config file perhaps?
* @todo this should be integrated with the Menu class
* @todo document the fixed up version
* @package    Bumblebee
* @subpackage Actions
*/
class ActionListing {

  var $userfunctions;
  var $adminfunctions;
  var $listing;
  var $titles;
  
  function ActionListing() {
    $this->_createUserFunctions();
    $this->_createAdminFunctions();
    $this->_initialise();
  }

  function _createUserFunctions() {
    $this->userfunctions = array(
      'view=View instrument bookings',
      'passwd=Change password',
      'login=Login',
      'logout=Logout',
      //permit this as a user function, but it has its own permission checks within
      'masquerade=Masquerade as another user',
      'unknown=Oops. I cannot do that',
      'forbidden!=No, you cannot do that!'
    );
  }

  function _createAdminFunctions() {
    #admin only functions
    $this->adminfunctions = array(
      'groups=Manage groups',
      'projects=Manage projects',
      'users=Manage users and permissions',
      'instruments=Manage instruments',
      'consumables=Manage consumables',
      'consume=Record consumable usage',
      'costs=Edit standard costs',
      'specialcosts=Edit or create special charges',
      'instrumentclass=Edit or create instrument class',
      'userclass=Edit or create user class',
      'deletedbookings=View deleted bookings',
      'report=Report usage',
      #'bookmeta=Points system and booking controls',
      #'adminconfirm=Booking confirmation',
      'emaillist=Email lists',
      'export=Export data',
      'billing=Prepare billing summaries',
      'backupdb=Backup database'
    );
  }

  function _initialise() { 
    $this->listing = array();
    $this->titles = array();
    $i=1;
    $this->_createDefaultAction ($this->userfunctions);
    $this->_createActionTranslate ($this->userfunctions, $i);
    $i=1000;
    $this->_createActionTranslate ($this->adminfunctions, $i);
  }

  function _createActionTranslate($fns, $i) {
    foreach ($fns as $fn) {
      preg_match("/(.+?)=(.+)/", $fn, $m);
      #echo "<!-- $m[1], $m[2] -->\n";
      $this->listing[$m[1]]=$i++;
      $this->titles[$m[1]]=$m[2];
    }
  }

  function _createDefaultAction ($fns) {
    $fn=$fns[0];
    preg_match("/(.+?)=(.+)/", $fn, $m);
    #echo "<!-- $m[1], $m[2] -->\n";
    $this->listing[""]=1;
    $this->titles[""]=$m[2];
    #echo $fn;
  }
} //ActionListing

?> 
