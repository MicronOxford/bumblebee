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
  # remove \ from strings
  $v = preg_replace('/\\\\/', '', $v);
  # replace ' with \' in strings, but not \' with \\', as that would be bad
  $v = preg_replace("/'/", "\\'", $v);
  # return the string in single quotes
  return "'$v'";
}

function is_empty_string($v) {
  #echo "'val=$v' ";
  return (strlen($v) == 0);
}

function is_no_choice_set($v) {
  #echo "'val=$v' ";
  return ($v == NULL || $v == "");
}

function is_invalid_radiochoice($v) {
  #echo "'val=$v' ";
  return (is_no_choice_set($v) || $v <= 0);
}

function is_not_email_format($v) {
  #echo "'val=$v' ";
  $pattern = "/^\w.+\@[A-Z_\-]+\.[A-Z_\-]/i";
  return (! preg_match($pattern, $v));
}

function is_not_number($v) {
  #echo "'val=$v' ";
  return (! (is_int($v) || is_float($v)));
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
