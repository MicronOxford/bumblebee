<?
# $Id$
# functions for handling types, comparisons, conversions etc

function issetSet(&$a, $k) {
  return (isset($a[$k]) ? $a[$k] : NULL);
}

function preDump($v) {
  echo "<pre>".print_r($v,1)."</pre>\n";
}


function is_alphabetic($var) {
  return preg_match("/^\w+$/", $var);
}

function qw($v) {
  ///FIXME check magic_quotes-Gpc in here somewhere?
  // http://www.sitepoint.com/article/php-anthology-3-php-mysql/7
  # remove \ from strings
  $v = preg_replace('/\\\\/', '', $v);
  # replace ' with \' in strings, but not \' with \\', as that would be bad
  $v = preg_replace("/'/", "\\'", $v);
  # return the string in single quotes
  return "'$v'";
}

/**
 * xssqw -- quote words against XSS attacks
 * replace some bad HTML characters with entities to protext against 
 * cross-site scripting attacks. the generated code should be clean of 
 * nasty HTML
**/
function xssqw($v) {
  $v = preg_replace('/\'/', '\&#39;', $v);
  $v = preg_replace('/\"/', '\&#34;', $v);
  $v = preg_replace('/\</', '\&lt;', $v);
  $v = preg_replace('/\>/', '\&gt;', $v);
  return $v;
}

function is_nonempty_string($v) {
  #echo "'val=$v' ";
  return !(strlen($v) == 0);
}

function choice_set($v) {
  #echo "'val=$v' ";
  return !($v == NULL || $v == "");
}

function is_valid_radiochoice($v) {
  #echo "'val=$v' ";
  return (choice_set($v) && $v > 0);
}

function is_email_format($v) {
  #echo "'val=$v' ";
  $pattern = "/^\w.+\@[A-Z_\-]+\.[A-Z_\-]/i";
  return (preg_match($pattern, $v));
}

function is_number($v) {
  #echo "'val=$v' ";
  return (is_int($v) || is_float($v));
}

function sum_is_100($vs) {
  $sum=0;
  foreach ($vs as $k => $v) {
    $sum += $v;
  }
  return ($sum == 100);
}


/*
echo "<pre>qw test\n";
$test = array();
$test[] = "test";
$test[] = "test data";
$test[] = "stuart's data";
$test[] = "magic quoted stuart\\'s data";
$test[] = "test";

foreach ($test as $t) {
  echo "$t => ". qw($t) ."\n";
}
*/

?> 
