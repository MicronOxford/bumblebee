<?php
/**
* Test of authentication object logic - crypt'ed passwords
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Tests
*/

ini_set('error_reporting', E_ALL);

set_include_path('../../../');
require 'inc/bb/basicauth.php';


$pass = 'foobar';

$methods = array('md5', 'md5_compat', 'md5_emulated', 'des', 'sha1');

foreach ($methods as $m) {
  
  print "Using method $m\n";
  print $enc = makePasswordHash($pass, NULL, $m);
  print "\n";

  print makePasswordHash($pass, $enc);
  print "\n";
  
  print "strlen=".strlen($enc)."\n";
}


// test that the md5 and md5_emulated methods do the same thing
print "\nTest custom md5 methods\n";
$salt = makeHashSalt('md5');
$enc1 = makePasswordHash($pass, $salt);
#print $salt."\n";
$enc1 = substr($enc1, strrpos($enc1, '$')+1);

$salt = '$md5$'.substr($salt,3);
$enc2 = makePasswordHash($pass, $salt);
#print $salt."\n";
$enc2 = substr($enc2, strrpos($enc2, '$')+1);

print $enc1 . "\n" . $enc2 . "\n" . ($enc1==$enc2 ? "PASS: passwords match\n" : "FAIL: no match\n");

// test that the sha1 and sha1_binary methods do the same thing
print "\nTest custom sha1 methods\n";
$salt = makeHashSalt('sha1');
$enc1 = makePasswordHash($pass, $salt);
#print $salt."\n";
$enc1 = substr($enc1, strrpos($enc1, '$')+1);

$salt = '$sha1_binary$'.substr($salt,6);
$enc2 = makePasswordHash($pass, $salt);
#print $salt."\n";
$enc2 = substr($enc2, strrpos($enc2, '$')+1);

print $enc1 . "\n" . $enc2 . "\n" . ($enc1==$enc2 ? "PASS: passwords match\n" : "FAIL: no match\n");

?> 
