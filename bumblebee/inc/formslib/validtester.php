<?php
# $Id$
# test the validity of data according to a set of rules

include_once("typeinfo.php");

function ValidTester($validator, $data) {
  global $VERBOSEDATA;
  $isValid = 1;
  if (isset($validator) && is_callable($validator)) {
    $isValid = $validator($data);
  } 
  if ($VERBOSEDATA) echo "[$data, $validator, $isValid]";
  return $isValid;
}

?> 
