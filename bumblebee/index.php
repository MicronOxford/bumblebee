<?php
/**
* Bumblebee base file
*
* All HTTP calls go directly through this object and are then handled through
* the ActionFactory to work out what should be done.
*
* @author     Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
*/

// prevent output for the moment to permit session headers
ob_start();

/** Load in the user configuration data */
include_once 'config/config.php'; 
/** start the database session */
include_once 'inc/db.php'; 
/** check the user's credentials, create a session to record them */
include_once 'inc/bb/auth.php';
$auth = new BumbleBeeAuth();


/** Load the action factory to work out what should be done in this instance of the script */
include_once 'inc/actions/actionfactory.php';
$action = new ActionFactory($auth);
if ($action->ob_flush_ok()) {
  // some actions will dump back a file, so we might not actually want to output content so far.
  // all is ready to roll now, start the output again.
  ob_end_flush();
}

/** load the user and/or admin menu */
include_once 'inc/menu.php';
$usermenu = new UserMenu($auth, $action->_verb);
$usermenu ->showMenu = ($auth->isLoggedIn() && $action->_verb != 'logout');

// $pagetitle can be used in theme/pageheader.php 
$pagetitle  = $action->title . ' : ' . $CONFIG['main']['SiteTitle'];
$pageheader = $action->title;
$pageBaseRef = $BASEURL.'/'.$action->_verb.'/';
/** display the HTML header section */
include 'theme/pageheader.php';
/** display the start of the html content */
include 'theme/contentheader.php';

?>
  <div id="bumblebeecontent">
    <form method="post" action="<?php echo $action->nextaction ?>" id="bumblebeeform">
    <?php
      if (! $auth->isLoggedIn()) {
        echo $auth->loginError();
      }
      $action->go();
    ?>
    </form>
  </div>
<?

/** display the page footer and close off the html page */
include 'theme/pagefooter.php';

if (! $action->ob_flush_ok()) {
  // some actions will dump back a file, and we never want all the HTML guff to end up in it...
  ob_end_clean();
  $action->returnBufferedStream();
}

?>
