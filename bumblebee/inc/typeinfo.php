<?
# $Id$
# functions for handling types, comparisons, conversions etc

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
