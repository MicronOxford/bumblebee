<?php
# $Id$
# test harness for Date classes

include_once '../date.php';

$days = 30;

$start = time() - $days * 24 * 60 * 60;

for ($d = 0; $d < 84; $d++) {
  $date = new SimpleDate($start);
  $date->dayRound();
  echo $date->datetimestring;
  $date->addDays($d);
  echo " + $d days = ";
  echo $date->datetimestring;
  echo "\n";
}

?> 
