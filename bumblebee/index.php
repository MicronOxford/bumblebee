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

define('BUMBLEBEE', true);
/** Load ancillary functions */
require_once 'inc/typeinfo.php';

global $CONFIG;
/** Load in the user configuration data */
require_once 'inc/config.php';
/** Load in PHP4/5 compatability layer */
require_once 'inc/compat.php';
/** start the database session */
require_once 'inc/db.php';
/** check the user's credentials, create a session to record them */
require_once 'inc/bb/auth.php';
$auth = new BumblebeeAuth($_POST);

/** Load the action factory to work out what should be done in this instance of the script */
require_once 'inc/actions/actionfactory.php';
$action = new ActionFactory($auth);
if ($action->ob_flush_ok()) {
  // some actions will dump back a file, so we might not actually want to output content so far.
  // all is ready to roll now, start the output again.
  ob_end_flush();
}

/** load the user and/or admin menu */
require_once 'inc/menu.php';
$usermenu = new UserMenu($auth, $action->_verb);
$usermenu->showMenu = ($auth->isLoggedIn() && $action->_verb != 'logout');
$usermenu->actionListing = $action->actionListing;

// $pagetitle can be used in theme/pageheader.php
$pagetitle  = $action->title . ' : ' . $CONFIG['main']['SiteTitle'];
$pageheader = $action->title;
$pageBaseRef = makeURL($action->_verb);
/** display the HTML header section */
include 'theme/pageheader.php';
/** display the start of the html content */
include 'theme/contentheader.php';

?>
  <div id="bumblebeecontent">
    <form method="post"
      accept-charset="utf-8"
      action="<?php echo makeURL($action->nextaction); ?>"
      id="bumblebeeform"
      <?php
        if (! isset($CONFIG['display']['AllowAutocomplete']) || ! $CONFIG['display']['AllowAutocomplete'])
          echo "AUTOCOMPLETE='off'";
      ?>>
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
