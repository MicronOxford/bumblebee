<?php
# $Id$
# test the validity of data according to a set of rules

include_once("typeinfo.php");

function ValidTester($validator, $data) {
  $isValid = 1;
  if (isset($validator) && is_callable($validator)) {
    $isValid = $validator($data);
  } 
  echo "[$data, $validator, $isValid]";
  return $isValid;
}

?> 
