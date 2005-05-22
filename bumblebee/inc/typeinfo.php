<?php
# $Id$
# functions for handling types, comparisons, conversions etc

function issetSet(&$a, $k) {
  return (isset($a[$k]) ? $a[$k] : NULL);
}

function preDump($v) {
  #echo "<pre>".print_r($v,1)."</pre>\n";
  echo '<pre>';
  #var_dump($v);
  print_r($v);
  echo '</pre>'."\n";
}

function echoData($v, $DEBUG=0) {
  global $VERBOSEDATA;
  if ($VERBOSEDATA || $DEBUG) {
    preDump($v);
  }
}


function is_alphabetic($var) {
  return preg_match('/^\w+$/', $var);
}

function qw($v) {
  // magic-quotes-gpc is a pain in the backside: I would rather I was just given
  // the data the user entered.
  // We can't just return the data if magic_quotes_gpc is turned on because 
  // that would be wrong if there was programatically set data in there.
  if (get_magic_quotes_gpc()) { 
    // first remove any (partial or full) escaping then add it in properly
    $v = addslashes(stripslashes($v));
  } else {
    // just add in the slashes
    $v = addslashes($v);
  }
  return "'".$v."'";
}

/**
 * xssqw -- quote words against XSS attacks
 * replace some bad HTML characters with entities to protext against 
 * cross-site scripting attacks. the generated code should be clean of 
 * nasty HTML
**/
function xssqw($v) {
  // once again magic_quotes_gpc gets in the way
  if (get_magic_quotes_gpc()) { 
    // first remove any (partial or full) escaping then we'll do it properly below
    $v = stripslashes($v);
  }
  return htmlentities($v, ENT_QUOTES);
}

function array_xssqw($a) {
  return array_map('xssqw', $a);
}

function is_nonempty_string($v) {
  #echo "'val=$v' ";
  return !(strlen($v) == 0);
}

function choice_set($v) {
  #echo "'val=$v' ";
  return !($v == NULL || $v == '');
}

function is_valid_radiochoice($v) {
  #echo "'val=$v' ";
  return (choice_set($v) && is_numeric($v) && $v >= -1);
}

function is_email_format($v) {
  #echo "'val=$v' ";
  $pattern = '/^\w.+\@[A-Z_\-]+\.[A-Z_\-]/i';
  return (preg_match($pattern, $v));
}

function is_number($v) {
  #echo "'val=$v' ";
  #echo "i=".is_int($v);
  #echo "f=".is_float($v);
  #return (is_int($v) || is_float($v));
  return is_numeric($v);
}

function is_cost_amount($v) {
   return is_numeric($v);
}

function is_valid_datetime($v) {
  return (preg_match('/^\d\d\d\d-\d\d-\d\d \d\d:\d\d/',$v));
}

function is_valid_time($v) {
  return (preg_match('/^\d\d:\d\d/',$v) || preg_match('/^\d\d:\d\d:\d\d/',$v));
}

function is_valid_nonzero_time($v) {
  return (preg_match('/^\d\d:\d\d/',$v) || preg_match('/^\d\d:\d\d:\d\d/',$v)) 
            && ! preg_match('/^00:00/',$v) && ! preg_match('/^00:00:00/',$v);
}

function sum_is_100($vs) {
  #echo "<br/>Checking sum<br/>";
  $sum=0;
  foreach ($vs as $v) {
    #echo "'$v', ";
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
