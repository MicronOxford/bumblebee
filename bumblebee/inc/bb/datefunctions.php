<?php
# $Id$
# Miscellaneous date functions

  function isodate($d) {
    echo "FIXME THE CODE IS NOT DEAD.";
    return strftime("%Y-%m-%d", $d);
  }

  function isotime($d) {
    return strftime("%H:%M", $d);
  }

  function minutesBetween($timestop, $timestart) {
    return ($timestop - $timestart)/60;
  }

  function truncatedDuration($timestart, $timestop, $starttime, $finishtime) {
    $truncstop = min($timestop,$finishtime);
    $truncstart = max($timestart,$starttime);
    #echo "($timestart,$starttime,$truncstart)-($timestop,$finishtime,$truncstop)";
    $durmin = minutesBetween($truncstop, $truncstart);
    #echo "=$durmin.";
    return $durmin;
  }

  function dateAddDays($startdate, $days) {
    $start = strtotime($startdate);
    $stop  = mktime(0,0,0, date('m',$start), date('d',$start)+$days, date('Y',$start));
    return $stop;
  }

  function selectDates($starttime, $stoptime) {
    $stop = strtotime($stoptime);
    $c = strtotime($starttime);
    $datelist = array();
    while ($c <= $stop) {
      $datelist[] = $c;
      $cy = date("y", $c);
      $cm = date("m", $c);
      $cd = date("d", $c);
      $c = mktime(0,0,0, $cm, $cd+1, $cy);
    }
    return $datelist;
  }

?> 
