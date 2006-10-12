<?php
/**
* Simple Bumblebee installer -- creates an SQL and ini files from user input
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Installer
*/

define('BUMBLEBEE', true);

$sqlSourceFile = 'setup-tables.sql';
$sqlSetupFilename = 'bumblebee.sql';
$iniSourceFile = 'db.ini';
$iniSetupFilename = 'db.ini';


error_reporting(E_ALL);

require_once 'installer/forms.php';
require_once 'installer/fileoutput.php';
require_once 'installer/sqlload.php';
require_once 'installer/checks.php';
require_once 'installer/createdatabase.php';
require_once 'installer/constructini.php';

require_once 'installer/installstep.php';

$steps = new InstallStepCollection();
$steps->addStep(new InstallStep('Usernames',          'do_userdata'));
$steps->addStep(new InstallStep('Pre-install check',  'do_preinst'));
$steps->addStep(new InstallStep('Create database',    'do_database'));
$steps->addStep(new InstallStep('Copy db.ini',        'do_dbini'));
$steps->addStep(new InstallStep('Customise',          'do_customise'));
$steps->addStep(new InstallStep('Post-install check', 'do_postinst'));
$steps->addStep(new InstallStep('Clean-up',           'do_cleanup'));

$steps->setCurrent(1);
$userSubmitted = array_merge(getSetupDefaults(), $_POST);

if (! isset($_POST['havedata']) || isset($_POST['do_userdata'])) {
  printStepUserForm($userSubmitted, $steps);
  exit;
}

$steps->increment();
if (isset($_POST['do_preinst'])) {
  $userSubmitted['preinst-results'] = check_preinst($userSubmitted);
  printStepPreInst($userSubmitted, $steps);
  exit;
}

$steps->increment();
if (isset($_POST['do_database'])) {
  printStepDatabase($userSubmitted, $steps);
  exit;
}
// do the database setup parts
if (isset($_POST['submitsql'])) {
  $includeAdmin = isset($_POST['includeAdmin']) ? $_POST['includeAdmin'] : false;
  $s = constructSQL($sqlSourceFile, $userSubmitted, $includeAdmin);
  outputTextFile($sqlSetupFilename, $s);
  exit;
}
if (isset($_POST['submitsqlload'])) {
  $s = constructSQL($sqlSourceFile, $userSubmitted, $_POST['includeAdmin']);
  $userSubmitted['loadsql-results'] = loadSQL($s, $_POST['sqlHost'], $_POST['sqlAdminUsername'],
                                                  $_POST['sqlAdminPassword']);
  printStepDatabase($userSubmitted, $steps);
  exit;
}

$steps->increment();
if (isset($_POST['do_dbini'])) {
  printStepDBini($userSubmitted, $steps);
  exit;
}
if (isset($_POST['submitini'])) {
  $s = constructini($iniSourceFile, $userSubmitted);
  outputTextFile($iniSetupFilename, $s);
  exit;
}

$steps->increment();
if (isset($_POST['do_customise'])) {
  printStepCustomise($userSubmitted, $steps);
  exit;
}

$steps->increment();
if (isset($_POST['do_postinst'])) {
  $userSubmitted['preinst-results']  = check_preinst($userSubmitted);
  $userSubmitted['postinst-results'] = check_postinst($userSubmitted);
  printStepPostInst($userSubmitted, $steps);
  exit;
}

$steps->increment();
if (isset($_POST['do_cleanup'])) {
  require_once 'installer/loadconfig.php';
  loadInstalledConfig();
  $userSubmitted['BASEURL'] = $BASEURL;
  printStepCleanup($userSubmitted, $steps);
  exit;
}

printErrorMessage("I can't work out what you want me to do");
exit;





/**
* Find out from the user what username and passwords to use for connecting to the database etc
*/
function printStepUserForm($values, $steps) {
  startHTML_install($values, $steps);
  ?>
    <fieldset>
      <legend>Input data</legend>
      <table>
        <?php
          printInstallFormFields($values, false);
        ?>
      </table>
    </fieldset>
    <div id='buttonbar'>
      <?php print $steps->getPrevNextButtons(); ?>
    </div>
  <?php
  endHTML();
}


function reflectUserData($values, $visible=true) {
  if ($visible) {
    ?>
    <fieldset>
      <legend>Input data</legend>
      <table>
        <?php printInstallFormFields($values, true);?>
      </table>
      <input type='submit' name='do_userdata' value='&laquo; Go back and change data' />
    </fieldset>
    <?php
  } else {
    printInstallFormFields($values, true, true);
  }
}

function printStepPreInst($values, $steps) {
  startHTML_install($values, $steps);
  reflectUserData($values);
  ?>
    <fieldset>
      <legend>Pre-installation check</legend>
      <p>The setup script is having a look around your system to see if everything is OK for Bumblebee...</p>
      <blockquote>
        <?php print $values['preinst-results']; ?>
      </blockquote>
      <p>If you're happy with the results of the pre-install check, then proceed to the next step.</p>
    </fieldset>
    <div id='buttonbar'>
      <?php print $steps->getPrevReloadNextButtons(NULL, 'Rerun test', NULL); ?>
    </div>
  <?php
  endHTML();
}

function printStepDatabase($values, $steps) {
  startHTML_install($values, $steps);
  reflectUserData($values, false);
  printDatabaseSetupForm($values);
  if (isset($values['loadsql-results'])) {
    ?>
      <fieldset>
        <legend>Loading SQL into Database</legend>
        <p>The setup script tried running the database upgrade and this is what MySQL said:</p>
        <blockquote>
          <?php print $values['loadsql-results']; ?>
        </blockquote>
        <p>If it all went well, then proceed to the next step. Otherwise, try to fix any
        errors (wrong username and password, perhaps) in the forms and have another go using
        the script or try to fix it up using phpMyAdmin.</p>
      </fieldset>
    <?php
  }
  ?>
    <div id='buttonbar'>
      <?php print $steps->getPrevNextButtons(); ?>
    </div>
  <?php
  endHTML();
}

function printStepDBini($values, $steps) {
  startHTML_install($values, $steps);
  reflectUserData($values, false);
  ?>
    <fieldset id='dbini'>
      <legend>Config file generation</legend>
      Bumblebee needs to know what username and password to use for connecting to your database.
      Download the <code>db.ini</code> file (which will contain the values specified above)
      and save it into your Bumblebee installation on the webserver as <code>config/db.ini</code>.<br />
      <input type='submit' name='submitini' value='Generate db.ini file' />
    </fieldset>
    <div id='buttonbar'>
      <?php print $steps->getPrevNextButtons(); ?>
    </div>
  <?php
  endHTML();
}

function printStepCustomise($values, $steps) {
  startHTML_install($values, $steps);
  reflectUserData($values, false);
  ?>
    <fieldset id='dbini'>
      <legend>Customise Installation</legend>
      <p>You now need to customise your <code>bumblebee.ini</code> file. This file can be found in
      the <code>config/</code> directory of your installation. The most important things for you
      to customise are in the first couple of sections of the file.</p>
      <p>Please refer to the
      <a href="http://bumblebeeman.sourceforge.net/documentation/configure">documentation</a>
      for more information on how to do this.</p>
    </fieldset>
    <div id='buttonbar'>
      <?php print $steps->getPrevNextButtons(); ?>
    </div>
  <?php
  endHTML();
}

function printStepPostInst($values, $steps) {
  startHTML_install($values, $steps);
  reflectUserData($values, false);
  ?>
    <fieldset>
      <legend>Post-installation check</legend>
      <p>The setup script is having a look around your system to see if everything is OK for Bumblebee
      (just as it did before) as well as looking at how your installation went...</p>
      <blockquote>
        <?php print $values['preinst-results']; ?>
      </blockquote>
      <blockquote>
        <?php print $values['postinst-results']; ?>
      </blockquote>
      <p>If you're happy with the results of the post-install check, then you're almost finished!</p>
    </fieldset>
    <div id='buttonbar'>
      <?php print $steps->getPrevReloadNextButtons(NULL, 'Rerun test', NULL); ?>
    </div>
  <?php
  endHTML();
}

function printStepCleanup($values, $steps) {
  startHTML_install($values, $steps);
  reflectUserData($values, false);
  genericCleanupInstructions($values, $steps);
  endHTML();
}

?>
