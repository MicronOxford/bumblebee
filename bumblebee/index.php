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
*
* path (bumblebee root)/index.php
*/

// prevent output for the moment to permit session headers
ob_start();

define('BUMBLEBEE', true);
/** Load ancillary functions */
require_once 'inc/typeinfo.php';


/** Load in PHP4/5 compatability layer */
require_once 'inc/compat.php';

/** Load in the user configuration data */
require_once 'inc/config.php';

/** start the database session */
require_once 'inc/db.php';

$conf = & ConfigReader::getInstance();
$conf->MergeDatabaseTable();
$conf->ParseConfig();


/** load the language pack */
require_once 'inc/i18n.php';


$auth = null;
if (! $conf->status->offline) {
  /** check the user's credentials, create a session to record them */
  require_once 'inc/bb/auth.php';
  $auth = new BumblebeeAuth($_POST);
}

/** Load the action factory to work out what should be done in this instance of the script */
require_once 'inc/actions/actionfactory.php';
$action = new ActionFactory($auth, $conf->status->offline ? "offline" : null);
if ($action->ob_flush_ok()) {
  // some actions will dump back a file, so we might not actually want to output content so far.
  // all is ready to roll now, start the output again.
  ob_end_flush();
}

makeMainPage($action, $auth);

if (! $action->ob_flush_ok()) {
  // some actions will dump back a file, and we never want all the HTML guff to end up in it...
  ob_end_clean();
  $action->returnBufferedStream();
}

#################################################################################################

/**
* Create the start of a form
*
* @param string   $url       URL that the form should be submitted to
* @param string   $magicTag  form validation tag to show that the form was not spoofed
* @param string   $id        xml ID of the form element
* @param boolean  $showAutocomplete    include a (non-standard) comment about form Autocompletion
*/
function formStart($url, $magicTag, $id='bumblebeeform', $showAutocomplete=true) {
  $conf = ConfigReader::getInstance();
  $autocomplete = "";
  if ($showAutocomplete &&
        ( $conf->value('display', 'AllowAutocomplete') === null ||
          ! $conf->value('display', 'AllowAutocomplete'))
     ) {
    $autocomplete = "AUTOCOMPLETE='off'";
  }

  return "
    <form method='post'
      accept-charset='utf-8'
      action='$url'
      id='$id'
      $autocomplete >
    "
    .sprintf('<input type="hidden" name="magicTag" value="%s" />', xssqw($magicTag));
}

/**
* Finish a form
*
*/
function formEnd() {
  return '</form>';
}

function pageStart($action, $auth) {
  $conf = ConfigReader::getInstance();

  // $usermenu variable is used inside the template
  /** load the user and/or admin menu */
  require_once 'inc/menu.php';
  $usermenu = new UserMenu($auth, $action->verb());
  $usermenu->showMenu = (! $conf->status->offline &&
                            $auth->isLoggedIn() &&
                            $action->verb() != 'logout');
  $usermenu->actionListing = $action->actionListing;

  // $page* variables can be used in theme/pageheader.php
  $pagetitle  = $action->title . ' : ' . $conf->value('main', 'SiteTitle');
  $pageheader = $action->title;
  $pageBaseRef = makeURL($action->verb());

  /** display the HTML header section */
  include 'theme/pageheader.php';
  /** display the start of the html content */
  include 'theme/contentheader.php';
  /** popup information control */
  include 'inc/popups.php';
}


function pageShowErrors($auth) {
  $conf = ConfigReader::getInstance();

  // Login Errors
  if (! $auth->isLoggedIn() && $err = $auth->loginError()) {
    echo '<div class="error">' . $err . '</div>';
  }

  // Installer still present error
  if ($auth->isSystemAdmin() && file_exists('install') && ! $conf->value('error_handling', 'ignore_installer', false)) {
    printf('<div class="error">%s</div>',
        T_('The installer still exists. This is a security risk. Please delete it.'));
  }
}

function pageStop() {
  global $BUMBLEBEEVERSION;
  /** display the page footer and close off the html page */
  include 'theme/pagefooter.php';
}


function makeMainPage($action, $auth) {
  $conf = ConfigReader::getInstance();

  pageStart($action, $auth);

  echo '<div id="bumblebeecontent">';

  if (! $conf->status->offline) {
    echo formStart(makeURL($action->nextaction), $auth->makeValidationTag());
    pageShowErrors($auth);
  } else {
    echo formStart(makeURL($action->nextaction), '');
  }

  $action->go();

  echo formEnd();
  echo '</div>';

  pageStop();
}

?>
