<?php
/**
* Simple Bumblebee upgrader -- performs everything necessary/possible to upgrade a user to newer version
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Installer
*/


define('BUMBLEBEE', true);

error_reporting(E_ALL);

$sqlSetupFilename = 'bumbelebeeupgrade.sql';

ob_start();
require_once 'installer/upgradedatabase.php';
require_once 'installer/forms.php';
require_once 'installer/sqlload.php';
require_once 'installer/fileoutput.php';
require_once 'installer/loadconfig.php';

loadInstalledConfig();
require_once 'inc/db.php';
require_once 'inc/formslib/sql.php';

require_once 'inc/bb/configreader.php';
$conf = ConfigReader::getInstance();

require_once 'installer/installstep.php';

$steps = new InstallStepCollection();
$steps->addStep(new InstallStep('Release notes',      'do_releasenotes'));
$steps->addStep(new InstallStep('Check database',     'do_checkdb'));
$steps->addStep(new InstallStep('Upgrade database',   'do_dbupgrade'));
$steps->addStep(new InstallStep('Clean-up',           'do_cleanup'));

$data = $_POST;

$steps->setCurrent(1);
if (! isset($_POST['havedata']) || isset($_POST['do_releasenotes'])) {
  printStepReleaseNotes($data, $steps);
  exit;
}

$steps->increment();
if (empty($_POST['old_db_version']) || isset($_POST['do_checkdb'])) {
  $data['old_db_version'] = getCurrentDBVersion();
  $data['old_version']    = $data['old_db_version'];
  $data['new_version']    = $BUMBLEBEEVERSION;
  $data['new_db_version'] = $data['new_version']; #substr($data['new_version'], 0, strrpos($data['new_version'], '.'));
  if (version_compare($data['old_db_version'], $data['new_db_version']) == -1) {
    // then the db needs upgrading
    $data['db_upgrade'] = true;
  } else {
    $data["db_upgrade"] = false;
  }
  printStepUpgradeCheck($data, $steps);
  exit;
}

$old_db_version = $data['old_db_version'];

$steps->increment();
if (isset($_POST['do_dbupgrade'])) {
  list($sql, $notes) = makeUpgradeSQL($old_db_version);
  $data['db-notes'] = $notes;
  printStepDBUpgrade($data, $steps);
  exit;
}
if (isset($_POST['submitsql'])) {
  list($sql, $notes) = makeUpgradeSQL($old_db_version);
  ob_end_clean();
  outputTextFile($sqlSetupFilename, $sql);
  exit;
}
if (isset($_POST['submitsqlload'])) {
  list($sql, $notes) = makeUpgradeSQL($old_db_version);
  $results = loadSQL($sql, $conf->value('database', 'host'), $_POST['sqlAdminUsername'], $_POST['sqlAdminPassword']);
  $data['db-notes']   = $notes;
  $data['db-results'] = $results;
  printStepDBUpgrade($data, $steps);
  exit;
}

$steps->increment();
if (isset($_POST['do_cleanup'])) {
  printStepCleanup($data, $steps);
  exit;
}



printErrorMessage("I can't work out what you want me to do");
exit;




/**
* Tell the user to look at the release notes
*/
function printStepReleaseNotes($data, $steps) {
  startHTML_upgrade($data, $steps);
  ?>
  <fieldset>
    <legend>Upgrade information</legend>
    <p>Before proceeding you should have:</p>
    <ol>
    <li>Read the
      <a href='http://bumblebeeman.sourceforge.net/documentation/upgrade'>upgrade instructions</a>
      before proceeding. No really, I mean it.</li>
    <li>Backed up your data including the database and the Bumblebee installation
      (or at least the <code>theme</code> and <code>config</code> directories)</li>
    <li>Unpacked the archive you downloaded on your webserver; you can copy your old
      <code>theme</code> and <code>config</code> directories back into the new installation
      (instead of using the new ones).
      You might find that many config options have been added to the <code>bumblebee.ini</code>
      file that you won't even know about unless you at least look at the new file;
      all new options have sensible defaults.</li>
    </ol>
      <p>After you have done these things, I'll guide you through the upgrade process.
      It shouldn't be too hard, but we'll see how we go.</p>

  </fieldset>
    <div id='buttonbar'>
      <?php print $steps->getPrevNextButtons(); ?>
    </div>
  <?php
  endHTML();
}
/**
* Find out from the user what username and passwords to use for connecting to the database etc
*/
function printStepUpgradeCheck($data, $steps) {
  startHTML_upgrade($data, $steps);
  ?>
    <fieldset>
      <legend>Upgrade information</legend>
      <p>Looking at your installation (you should have installed the new version of Bumblebee at this stage).</p>
      <p>It appears you are trying to upgrade to Bumblebee version <?php echo $data['new_version']; ?>.</p>
      <p>Your old version of Bumblebee appears to be using the database format used with version
      <?php echo $data['old_db_version']; ?>.</p>
      <?php
        if ($data['db_upgrade']) {
          echo "<p>Your database needs upgrading.</p>";
        } else {
          echo "<p>Your database doesn't need upgrading. Great!!</p>";
        }
      ?>
    </fieldset>
    <div id='buttonbar'>
      <?php print $data['db_upgrade'] ? $steps->getPrevNextButtons() : $steps->getPrevSkipToButtons(1); ?>
    </div>
  <?php
  endHTML();
}

/**
* Show the user what data they have given and give options for what to do next
*/
function printStepDBUpgrade($data, $steps) {
  startHTML_upgrade($data, $steps);
  if (isset($data['db-notes']) && $data['db-notes'] && ! isset($data['db-results'])) {
    ?>
      <fieldset>
        <legend>Database Upgrade Notes</legend>
        <p>Please make sure you understand the following upgrade notes regarding your database before proceeding.</p>
        <blockquote>
          <?php print $data['db-notes']; ?>
        </blockquote>
      </fieldset>
    <?php
  }
  printDatabaseSetupForm($data);
  if (isset($data['db-results'])) {
    ?>
      <fieldset>
        <legend>Database Upgrade Results</legend>
        <p>The setup script tried running the database upgrade and this is what MySQL said:</p>
        <blockquote>
          <?php print $data['db-results']; ?>
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

function printStepCleanup($values, $steps) {
  $conf = ConfigReader::getInstance();
  $values['BASEURL'] = $conf->BaseURL;
  startHTML_upgrade($values, $steps);
  genericCleanupInstructions($values, $steps);
  endHTML();
}

function T_($m) {
  return $m;
}

?>
