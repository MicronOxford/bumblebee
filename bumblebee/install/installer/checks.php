<?php
/**
* Pre- and post-install checks of the setup
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Installer
*/

/**
* Check installation to see if required and optional components are installed.
* Also check to see if entered data is valid
*/
function check_preinst($data) {
  $s = array();
  $error = $warn = false;
  ini_set('track_errors', true);
  // check kit: check that a Bumblebee installation can be found
  $REBASE_INSTALL = '..'.DIRECTORY_SEPARATOR;
  set_include_path($REBASE_INSTALL.PATH_SEPARATOR.get_include_path());
  $NON_FATAL_CONFIG = true;
  $php_errormsg = '';
  if (@ include 'inc/config.php') {   // FIXME file moved for v1.2
    $s[] = "GOOD: Found installation of Bumblebee version $BUMBLEBEEVERSION.";
  } else {
    $s[] = "ERROR: I couldn't find any evidence of a Bumblebee installation here. PHP said:<blockquote>\n$php_errormsg</blockquote>";
    $error = true;
  }
  if ($php_errormsg !== '') {
    $s[] = "ERROR: Configuration didn't load properly. "
           ."Bumblebee said:<blockquote>\n$php_errormsg</blockquote>";
    $error = true;
  } else {
    $s[] = "GOOD: Configuration loaded successfully";
  }
  // check kit: check that php-gettext can be found 
  if (! @ include_once 'php-gettext/gettext.inc') {
    $s[] = "WARNING: <a href='https://savannah.nongnu.org/projects/php-gettext/'>php-gettext</a> internationali[sz]ation layer not found. Translations will not be available. "
           ."PHP said:<pre>\n$php_errormsg</pre>";
    $warn = true;
    if (! function_exists('T_')) {
      function T_($s) { return $s; }
    }
  } else {
    $s[] = "GOOD: php-gettext found for generating translated content.";
  }
  // check kit: LDAP and RADIUS modules
  if (! (@ include 'Auth/Auth.php') || ! (@ include_once 'PEAR.php')) {
    $s[] = "WARNING: <a href='http://pear.php.net/'>PEAR::Auth</a> modules not found. LDAP and RADIUS authentication unavailable. "
           ."PHP said:<blockquote>\n$php_errormsg</blockquote>";
    $warn = true;
  } else {
    // check individually for LDAP and RADIUS here? but will that just cause a PHP crash if they are not installed?
    if (! extension_loaded('ldap')) {
      //$b = new Auth("LDAP", array(), '', false); 
      $s[] = "WARNING: PHP's <a href='http://php.net/ldap'>LDAP extension</a> was not found. LDAP authentication unavailable.";
      $warn = true;
    } else {
      $s[] = "GOOD: LDAP extension found for LDAP authentication.";
    }
    if (! PEAR::loadExtension('radius')) {
      //$b = new Auth("RADIUS", array("servers" => array()), "", false);    // hangs if radius module not installed
      $s[] = "WARNING: PHP's <a href='http://pecl.php.net/package/radius'>RADIUS extension</a> was not found. RADIUS authentication unavailable.";
      $warn = true;
    } else {
      $s[] = "GOOD: PECL RADIUS extension found for RADIUS authentication.";
    }
  }
  // check kit: see if FPDF is installed
  if (! (@ include 'fpdf/fpdf.php')) {
    $s[] = "WARNING: Free PDF library <a href='http://www.fpdf.org/'>FPDF</a> not found. Will not be able to generate PDF reports.";
    $warn = true;
  } else {
    $s[] = "GOOD: FPDF library found for generating PDF reports.";
  }

  // check username: make sure admin username meets Bumblebee requirements
  if (! preg_match($CONFIG['auth']['validUserRegexp'], $data['bbAdmin'])) {
    $s[] = "ERROR: The username you have chosen for your Admin user ('".$data['bbAdmin']."') "
          ."will not be able to log into Bumblebee due to restrictions on valid usernames in "
          ."<code>config/bumblebee.ini</code>. Either change the username you have chosen or "
          ."relax the restrictions specficied by <code>[auth].validUserRegexp</code> in that file.";
    $error = true;
  } else {
    $s[] = "GOOD: Admin username is valid.";
  }

  // check password strength of admin password
  list ($strength, $message) = passwordStrength($data['bbAdminPass']);
  if ($strength == 2) {
    $s[] = "ERROR: Admin user's password is poor. $message";
    $error = true;
  } elseif ($strength == 1) {
    $s[] = "WARNING: Admin user's password is poor. $message";
    $warn = true;
  } else {
    $s[] = "GOOD: Admin user's password seems ok. $message";
  }

  // check password strength of database password
  list ($strength, $message) = passwordStrength($data['sqlPass']);
  if ($strength == 2) {
    $s[] = "ERROR: Database user's password is poor. $message";
    $error = true;
  } elseif ($strength == 1) {
    $s[] = "WARNING: Database user's password is poor. $message";
    $warn = true;
  } else {
    $s[] = "GOOD: Database user's password seems ok. $message";
  }

  if ($error) {
    $s[] = "<b>Errors were detected. Please fix them and reload this page to perform these tests again.</b>";
  }
  if ($warn) {
    $s[] = "<b>Warnings were emitted. Please check to see if they are important to your setup and correct them if necessary. Reload this page to perform these tests again.</b>";
  }
  if (! $error && ! $warn) {
    $s[] = "<b>Excellent! Your setup looks fine.</b>";
  }
  return "<h2>Results</h2>"
        ."Checking to see if your kit looks good...<br />\n".parseTests($s);
}

/**
* Check installation to see if required and optional components are installed.
* post-inst auto-test of db, environment, auth modules etc.
* post-inst test that .ini files are protected by .htaccess
* check that admin can log in ok using auth.php
*/
function check_postinst($data) {
  $s = array();
  $error = $warn = false;
  ini_set('track_errors', true);
  // check that we can load the config correctly
  $REBASE_INSTALL = '..'.DIRECTORY_SEPARATOR;
  $NON_FATAL_CONFIG = true;
  $php_errormsg = '';
  if ((! @ require 'inc/config.php') || $php_errormsg !== '') {
    $s[] = "ERROR: Configuration didn't load properly. "
           ."Bumblebee said:<blockquote>\n$php_errormsg</blockquote>";
    $error = true;
  } else {
    $s[] = "GOOD: Configuration loaded successfully";
  }
  // check that we can login to the db
  $NON_FATAL_DB = true;
  $DB_CONNECT_DEBUG = true;
  $php_errormsg = '';
  if ((! @ require_once 'inc/db.php') || $php_errormsg !== '') {
    $s[] = "ERROR: Unable to connect to database. "
           ."PHP said:<blockquote>\n$php_errormsg</blockquote>";
    $error = true;
  } else {
    $s[] = "GOOD: Successfully connected to database";
  }
  if (! $error) {
    // check that the admin user can log into the system
    require_once 'inc/bb/auth.php';
    $_POST['username'] = $data['bbAdmin'];
    $_POST['pass']     = $data['bbAdminPass'];
    $auth = @ new BumblebeeAuth(true);
    if (! $auth->isLoggedIn()) {
      $auth->DEBUG=10;
      $s[] = "ERROR: Admin user cannot log in to Bumblebee with username and password supplied. Bumblebee said:"
            . "<blockquote>".$auth->loginError()."</blockquote>";
      $error = true;
    } else {
      $s[] .= "GOOD: Admin can log in to Bumblebee with this username and password.";
      $auth->logout();  // destroy the session cookie so the user has to log in
    }
  }

  // check to see if ini files are accessible to outsiders using HTTP
  $htdbini    = 'http://'.$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$BASEPATH.'/config/db.ini';
  $localdbini = '..'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'db.ini';
  if (! ini_get('allow_url_fopen')) {
    $s[] = "WARNING: The accessibility of your ini files (and passwords!) to outsiders cannot be checked."
          ."You can enable the PHP option <code>allow_url_fopen</code> in the <code>php.ini</code> file and rerun this test,"
          ."or you can see if you are able to download the file yourself through your browser: "
          ."<a href='$htdbini' target='_blank'>config/db.ini</a>.";
    $warn = true;
  } else {
    if ($dbinidata = @ file_get_contents($htdbini)) {
      // then something was downloaded
      if ($dbinidata == file_get_contents($localdbini)) {
        $s[] = "ERROR: it appears that your db.ini file can be downloaded from your webserver, exposing your "
              ."database passwords. Try it for yourself: "
              ."<a href='$htdbini' target='_blank'>config/db.ini</a>. "
              ."You really want to correct that either in your webserver's configuration or in a local .htaccess file.";
        $error = true;
      } else {
          $s[] = "WARNING: it appears something can be downloaded from your webserver from the config/db.ini file, "
                ."however, I was unable to verify what it was. Please try it for yourself: "
                ."<a href='$htdbini' target='_blank'>config/db.ini</a>. "
                ."You really want to make sure that the db.ini (and ldap.ini etc) file cannot be accessed as it "
                ."contains your password information.";
          $warn = true;
      }
    } elseif (preg_match('/\s403 Forbidden/', $php_errormsg)) {
      $s[] = "GOOD: db.ini file is protected against downloading (gives 403 Forbidden).";
    } elseif (preg_match('/\s404 Not Found/', $php_errormsg)) {
      $s[] = "WARNING: db.ini file gave a 404 Not Found error. If you have manually moved the config files "
            ."out of the webserver's file tree then that's fine, but if you haven't done this then your "
            ."setup in <code>bumblebee.ini</code> is specifying an incorrect location for your Bumblebee installation.";
    } else {
        $s[] = "WARNING: db.ini file appears to be protected against downloading, "
              ."but I didn't get a 403 Forbidden error. "
              ."Please try it for yourself: "
              ."<a href='$htdbini' target='_blank'>config/db.ini</a>. "
              ."You really want to make sure that the db.ini (and ldap.ini etc) file cannot be accessed as it "
              ."contains your password information.";
        $warn = true;
    }
  }
  
  // check to see if bumblebee.ini has the right place for the installation
  $htbb[]  = 'http://'.$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$BASEURL;
  $htbb[]  = 'http://'.$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$BASEPATH.'/';
  if (! ini_get('allow_url_fopen')) {
    $s[] = "WARNING: I can't test to see that your bumblebee.ini file points to the right URL.."
          ."You can enable the PHP option <code>allow_url_fopen</code> in the <code>php.ini</code> file and rerun this test,"
          ."or you can see if you are able to download the file yourself through your browser: "
          ."<a href='$htbb[0]' target='_blank'>check 1</a> "
          ."<a href='$htbb[1]' target='_blank'>check 2</a>.";
    $warn = true;
  } else {
    $htbbdata[] = @ file_get_contents($htbb[0]);
    $htbbdata[] = @ file_get_contents($htbb[1]);
    if ($htbbdata[0] || $htbbdata[1]) {
      // then something was downloaded
      if ($htbbdata[0] != $htbbdata[1]) {
        $s[] = "ERROR: I got different results when I tried to go to <a href='$htbb[0]' target='_blank'>check 1</a> "
              ."and <a href='$htbb[1]' target='_blank'>check 2</a>.";
              echo "......".$htbbdata[0]."......".$htbbdata[1]."......";
        $error = true;
      } elseif (! preg_match('/Bumblebee/', $htbbdata[0])) {
        $s[] = "WARNING: I was able to find a webpage at your <a href='$htbb[0]' target='_blank'>configured location</a>, "
              ."but I couldn't find any evidence that it was a Bumblebee installation.";
        $warn = true;
      } else {
        $s[] = "GOOD: I could find your installation using http.";
      }
    } else {
      $s[] = "ERROR: I couldn't find a web page at your  <a href='$htbb[0]' target='_blank'>configured location</a>.";
      $error = true;
    }
  }
  
  if ($error) {
    $s[] = "<b>Errors were detected. Please fix them and reload this page.</b>";
  }
  if ($warn) {
    $s[] = "<b>Warnings were emitted. Please check to see if they are important to your setup and correct them if necessary.</b>";
  }
  if (! $error && ! $warn) {
    $s[] = "<b>Excellent! Your setup looks fine.</b>";
  }
  return "<h2>Post-install check results</h2>"
        ."Checking your setup works now you've installed the db.ini file and created the database...<br />\n"
        .parseTests($s);
}


/**
* Parse the test results and do some pretty printing of them
* @param array $results
* @return string pretty printed results
*/
function parseTests($r) {
  $replace = array(
              '/^GOOD:/'    => '<span class="good">GOOD:</span>',
              '/^WARNING:/' => '<span class="warn">WARNING:</span>',
              '/^ERROR:/'   => '<span class="error">ERROR:</span>'
             );
  $s = preg_replace(array_keys($replace), array_values($replace), $r);
  return join($s, "<br />\n");
}


/**
* Check the strength of the password
* @param string $password
* @return array list(integer (0=ok, 1=warn, 2=err), string description)
*/
function passwordStrength($password) {
  $advice = "Use at least 8 characters and include numbers, upper and lower case letters and some punctuation.";
  if (preg_match("/^(?=.{8,})(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*\\W).*$/", $password)) {
    // password at least 8 chars and contains uppercase, lowercase, digits and punctuation
    return array(0, "password seems strong enough");
  }
  if (preg_match("/^(?=.{7,})(((?=.*[A-Z])(?=.*[a-z]))|((?=.*[A-Z])(?=.*[0-9]))|((?=.*[a-z])(?=.*[0-9]))).*$/", $password)) {
    // password at least 7 chars and contains two out of uppercase, lowercase, digits and punctuation
    return array(1, "This password is relatively weak. $advice");
  }
  if (strlen($password) > 8) {
    // password doesn't have lots of letters and numbers but at least it has 8 characters...
    return array(1, "This password is quite weak. $advice");
  }
  // password is exceedingly poor
  return array(1, "This password is very weak. $advice");
} 

?>
