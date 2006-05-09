<?php
/**
* Test of date objects
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Tests
*/

/** include the classes we are supposed to be testing */
require_once '../../date.php';
ini_set("error_reporting",E_ALL);

/*
$days = 30;

$start = new SimpleDate(time() - $days * 24 * 60 * 60);
$start->dayRound();

for ($d = 0; $d < 84; $d++) {
  $date = $start;
  $date->addDays($d);
  echo $start->datetimestring. " (" .$start->ticks.") \n";
  echo "\t + $d days = ";
  echo $date->datetimestring. " (" .$date->ticks.") \n";;
  echo "\t (daysBetween=".$date->daysBetween($start).")";
  echo ", (dsDaysBetween=".$date->dsDaysBetween($start).")";
  echo ", (partDaysBetween=".$date->partDaysBetween($start).")";
  echo "\n";
}

$offset = new SimpleTime("01:00:00",1);
echo "OFFSET=".$offset->ticks.", ".$offset->timestring."\n\n";
$start->addTime($offset);
for ($d = 0; $d < 84; $d++) {
  $date = $start;
  $date->addDays($d);
  echo $start->datetimestring. " (" .$start->ticks.") \n";
  echo "\t + $d days = ";
  echo $date->datetimestring. " (" .$date->ticks.") \n";;
  echo "\t (daysBetween=".$date->daysBetween($start).")";
  echo ", (dsDaysBetween=".$date->dsDaysBetween($start).")";
  echo ", (partDaysBetween=".$date->partDaysBetween($start).")";
  echo "\n";
}
*/


// benchmarks:

$reps = 10000;
$benchmark_start_time = getmicrotime();

$days = 30;
$start = new SimpleDate(time() - $days * 24 * 60 * 60);
$newtime = new SimpleTime('01:30:00');
for ($i=0; $i<$reps; $i++) {
  //$start->dayRound();
  $time = $start;
  $time->setTime($newtime);
  #echo $time->datetimestring;
}


$elapsed_time = getmicrotime() - $benchmark_start_time;
echo "<p>Time elapsed: $elapsed_time seconds";


function getmicrotime() {
  list($usec, $sec) = explode(" ",microtime());
   return ((float)$usec + (float)$sec);
}




?> 
