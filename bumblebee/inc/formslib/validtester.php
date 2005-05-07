<?php
# $Id$
# test the validity of data according to a set of rules

include_once 'inc/typeinfo.php';

function ValidTester($validator, $data, $DEBUG=0) {
  //global $VERBOSEDATA;
  $isValid = 1;
  if (isset($validator) && is_callable($validator)) {
    $isValid = $validator($data);
  } 
  if ($DEBUG > 9) echo "[$data, $validator, $isValid]";
  return $isValid;
}

?> 
