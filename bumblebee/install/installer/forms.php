<?php
/**
* Simple Forms library for Bumblebee installer
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Installer
*/


function startHTML($title, $head='') {
  ?>
  <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
  <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
      <head>
        <title><?php echo $title; ?></title>
        <style type="text/css">
              .good  { color: green;  font-weight: bolder; }
              .warn  { color: orange; font-weight: bolder; }
              .error { color: red;    font-weight: bolder; }
              blockquote {border: 1px solid #333399; margin: 1em; padding: 1em;}
              fieldset { background-color: #f9f9ff;}
              fieldset fieldset {background-color: #f9ffff;}
              h2 { padding-top: 0; margin-top: 0;}
        </style>
        <script type='text/javascript'>
        //<![CDATA[
          function enableButton(id) {
            var but = document.getElementById(id);
            but.disabled = false;
            return true;
          }
        //]]>
        </script>
        <?php echo $head; ?>
      </head>
      <body>
  <?php
}

function startHTML_install($data, $steps) {
  $title = 'Bumblebee setup';
  startHTML($title);
  ?>
      <h1>Bumblebee Setup Script</h1>
      <form action='install.php' method='post'>
      <input type='hidden' name='havedata' value='1' />
      <div id="stepnum">Step <?php print $steps->getIndex(); ?> of <?php print $steps->numSteps();?></div>
      <div id="stepbar">
      <?php
        print $steps->getStepButtons();
      ?>
      </div>
      <p>Please use this script in conjunction with the
      <a href='http://bumblebeeman.sourceforge.net/documentation/install'>installation instructions</a>.
      </p>
  <?php
}

function startHTML_upgrade($data, $steps) {
  $title = 'Bumblebee upgrade';
  startHTML($title);
  ?>
      <h1>Bumblebee Upgrade Script</h1>
      <form action='upgrade.php' method='post'>
      <input type='hidden' name='havedata' value='1' />
      <div id="stepnum">Step <?php print $steps->getIndex(); ?> of <?php print $steps->numSteps();?></div>
      <div id="stepbar">
      <?php
        print $steps->getStepButtons();
      ?>
      </div>
      <p>Please use this script in conjunction with the
      <a href='http://bumblebeeman.sourceforge.net/documentation/upgrade'>upgrade instructions</a>.</p>
  <?php
  if (isset($data['old_db_version']))
      echo "<input type='hidden' name='old_db_version' value='{$data['old_db_version']}' />";
}

function endHTML() {
  ?>
      </form>
      </body>
    </html>
  <?php
}



/**
* Find out from the user what username and passwords to use for connecting to the database etc
*/
function printInstallFormFields($values, $hidden, $reallyhidden=false) {
  if ($reallyhidden) {
    printField('sqlHost', $values['sqlHost'], $hidden, $reallyhidden);
    printField('sqlDB', $values['sqlDB'], $hidden, $reallyhidden);
    printField('sqlTablePrefix', $values['sqlTablePrefix'], $hidden, $reallyhidden);
    printField('sqlUser', $values['sqlUser'], $hidden, $reallyhidden);
    printField('sqlPass', $values['sqlPass'], $hidden, $reallyhidden);
    printField('bbAdmin', $values['bbAdmin'], $hidden, $reallyhidden);
    printField('bbAdminPass', $values['bbAdminPass'], $hidden, $reallyhidden);
    printField('bbAdminName', $values['bbAdminName'], $hidden, $reallyhidden);
    return;
  }
  ?>
  <tr>
    <td>MySQL host</td>
    <td><?php printField('sqlHost', $values['sqlHost'], $hidden);?></td>
  </tr>
  <tr>
    <td>MySQL database</td>
    <td><?php printField('sqlDB', $values['sqlDB'], $hidden);?></td>
  </tr>
  <tr>
    <td>MySQL table prefix</td>
    <td><?php printField('sqlTablePrefix', $values['sqlTablePrefix'], $hidden);?></td>
  </tr>
  <tr>
    <td>MySQL username</td>
    <td><?php printField('sqlUser', $values['sqlUser'], $hidden);?></td>
  </tr>
  <tr>
    <td>MySQL user password</td>
    <td><?php printField('sqlPass', $values['sqlPass'], $hidden);?></td>
  </tr>
  <tr>
    <td>Bumblebee admin username</td>
    <td><?php printField('bbAdmin', $values['bbAdmin'], $hidden);?></td>
  </tr>
  <tr>
    <td>Bumblebee admin password</td>
    <td><?php printField('bbAdminPass', $values['bbAdminPass'], $hidden);?></td>
  </tr>
  <tr>
    <td>Bumblebee admin user's real name</td>
    <td><?php printField('bbAdminName', $values['bbAdminName'], $hidden);?></td>
  </tr>
  <input type='hidden' name='havedata' value='1' />

  <?php
}

/**
* Display an individual field
*/
function printField($name, $value, $hidden, $type='text') {
  if ($hidden) {
    print "<input type='hidden' name='$name' value='$value' />".($type===true ? '' : $value);
  } else {
    print "<input type='$type' name='$name' value='$value' />";
  }
}

function printDatabaseSetupForm($values, $next) {
  ?>
  <fieldset id='dbsetup'>
    <legend>Database setup</legend>
    <label>
      <input type='checkbox' name='includeAdmin' value='1' checked='checked' />
      include commands to create the database and MySQL user ("CREATE" and "GRANT" commands)
    </label><br /><br />
    <label>
      <input type='checkbox' name='sqlUseDropTable' value='1' checked='checked' />
      include commands to remove any existing databases and tables ("DROP" commands)
    </label><br /><br />
    You can either download the database setup script and load it manually into the database
    or you can enter the username and password of a database user who is permitted
    to add database users, grant them permissions and create databases (<i>e.g.</i> root)
    and I'll try to setup database for you.
    <table>
    <tr><td width='50%' valign='top'>
    <fieldset>
      <legend>Manual setup</legend>
      <input type='submit' name='submitsql' value='Download database script'
            <?php echo jsEnableButtonClick($next); ?>
      />
            <br/>
            Save the SQL file and then load it into the database using either phpMyAdmin or
            the mysql command line tools, <i>e.g.</i>:
            <code style="white-space: nowrap;">mysql -p --user root &lt; bumbelebee.sql</code>
    </fieldset>
    </td><td width='50%' valign='top'>
    <fieldset>
      <legend>Automated setup</legend>
      <input type='submit' name='submitsqlload' value='Automated setup' />
            (needs username and password)
      <table>
        <tr>
          <td>MySQL admin username</td>
          <td><?php printField('sqlAdminUsername', 'root', false);?></td>
        </tr>
        <tr>
          <td>MySQL admin password</td>
          <td><?php printField('sqlAdminPassword', '', false, 'password');?></td>
        </tr>
      </table>
    </fieldset>
    </td></tr>
    </table>
  </fieldset>
  <?php
}

function printErrorMessage($message) {
  startHTML('Error');
  ?>
  <h1>Error</h1>
  <p>I'm sorry, an error has occurred within the script. </p>
  <blockquote>
    <?php echo $message; ?>
  </blockquote>
  <p><a href="<?php echo $_SERVER['PHP_SELF']; ?>">Back to the script</a></p>
  <?php
  endHTML();
}

function genericCleanupInstructions($values, $steps) {
  ?>
    <fieldset>
      <legend>Post-installation clean-up</legend>
      <p>Now that you've installed Bumblebee and all seems to be working well,
      you should either delete the <code>install</code> directory from your server
      or limit access to it so that only you can get to it.</p>
      <p>The installer can reveal information about your setup to others, so you
      probably don't want to leave it the way it is!</p>
      <p>Once you've done that, you can go to your
      <a href='<?php echo $values['BASEURL']; ?>'>Bumblebee installation</a></p>
    </fieldset>
    <div id='buttonbar'>
      <?php print $steps->getPrevNextButtons(); ?>
    </div>
  <?php
}

function jsEnableButtonClick($name) {
  return "onClick='return enableButton(\"$name\");'";
}

?>