<?php
/**
* functions for handling authentication
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Misc
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

/**
* tests if string is a valid username as per the config setting for usernames
*
* @param string $v string to test if it is a valid username
* @global system config array
* @return boolean username is valid
*/
function is_valid_username($v) {
  global $CONFIG;
  $validUserRegexp = issetSet($CONFIG['auth'], 'validUserRegexp');
  return (empty($validUserRegexp) || preg_match($validUserRegexp, $v));
}

/**
* check that the supplied password matches the encoded password in the database
*
* @param string $test    unencoded string supplied by the user as a password attempt
* @param string $real    encoded string from the database
* @returns boolean       password matches
*/
function check_password($test, $real) {
  //return (makePasswordHash($test, $real) == $real);
  $hash = makePasswordHash($test, $real);
  #print $hash;
  return $hash == $real;
}

/**
* encode a password according to the specified hash method (or using the requested
*
* @param string $passwd     unencoded password string
* @param string $salt       salt used to hash the password
* @returns string           hashed password
*
* @global config array
*/
function makePasswordHash($passwd, $salt=NULL, $method=NULL) {
  global $CONFIG;
  if ($salt === NULL) $salt = makeHashSalt(
            issetSet($CONFIG['auth'], 'LocalPassToken', ($method === NULL) ? 'md5_compat' : $method));

  $tmp = explode('$', $salt);
  if (count($tmp) > 3) $saltpart = '$'.$tmp[1].'$'.$tmp[2].'$';

  # echo "salt=$salt";
  switch(passwordHashType($salt)) {
    case 'des':
    case 'md5':
      # echo "using crypt()";
      return crypt($passwd, $salt);
    case 'md5_emulated':
      # echo "emulating salted md5";
      return $saltpart.md5($tmp[2].$passwd);  // custom bogo md5
    case 'md5_compat':
      # echo "using bogo MD5";
      return md5($passwd);
    case 'sha1':
      # echo "using simple sha1";
      return $saltpart.base64_encode(pack('H*',sha1($tmp[2].$passwd)));  // custom bogo sha1
    case 'sha1_binary':
      # echo "using binary sha1";
      return $saltpart.base64_encode(sha1($tmp[2].$passwd, true));  // custom bogo sha1
  }
}

function makeHashSalt($type='md5') {
  switch($type) {
    case 'md5':
      $len=9;
      $prefix='$1$';
      $suffix='$';
      break;
    case 'md5_emulated':
      $len=9;
      $prefix='$md5$';
      $suffix='$';
      break;
    case 'md5_compat':
      return '';
    case 'des':
      $len=2;
      $prefix='';
      $suffix='';
      break;
    case 'sha1':
      $len=10;
      $prefix='$sha1$';
      $suffix='$';
      break;
    case 'sha1_binary':
      $len=10;
      $prefix='$sha1$';
      $suffix='$';
      break;
  }
  $salt='';
  while (strlen($salt) < $len) $salt .= chr(rand(64,126));
  return $prefix.$salt.$suffix;
}

function passwordHashType($pass) {
  $tmp = explode('$', $pass);
  if (count($tmp) < 3) {
    if (strlen($pass) == 32 || strlen($pass) == 0) return 'md5_compat';
    return 'des';
  }

  if ($tmp[1] == 1)             return 'md5';
  if ($tmp[1] == 'md5')         return 'md5_emulated';
  if ($tmp[1] == 'sha1')        return 'sha1';
  if ($tmp[1] == 'sha1_binary') return 'sha1_binary';
}

?>
