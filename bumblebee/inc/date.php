<?php
# $Id$
# a simple date class to perform basic date calculations

// WARNING USING TICKS IS DANGEROUS
// the ticks variable here is a little problematic... daylight saving transitions tend
// to make it rather frought.

class SimpleDate {
  var $datestring = '';
  var $ticks  = '';
  var $datetimestring = '';
  var $isValid = 1;

  function SimpleDate($time) {
    ($time == NULL) && $time = 0;
    if (is_numeric($time)) {
      $this->setTicks($time);
    } elseif (is_a($time, 'SimpleDate')) {
      $this->setTicks($time->ticks);
    } else {
      $this->setStr($time);
    } 
  }

  function setStr($s) {
    $this->isValid = 1;
    $this->_setTicks($s);
    $this->_setStr();
  }

  function _setStr() {
    #echo "SimpleDate::Str $this->ticks<br />";
    $this->datestring = strftime('%Y-%m-%d', $this->ticks);
    $this->datetimestring = strftime('%Y-%m-%d %H:%M:%S', $this->ticks);
    $this->isValid = $this->isValid && ($this->datestring != '' && $this->datetimestring != '' 
                   && $this->datestring != -1 && $this->datetimestring != -1);
  }

  function setTicks($t) {
    $this->isValid = 1;
    $this->ticks = $t;
    $this->_setStr();
  }

  function _setTicks($s) {
    #echo "SimpleDate::Ticks $s<br />";
    #preDump(debug_backtrace());
    $this->ticks = strtotime($s);
    $this->isValid = $this->isValid && ($this->ticks != '' && $this->ticks != -1);
  }

  function addDays($d) {
    $this->addTimeParts(0,0,0,$d,0,0);
  }

  function addTime($t) {
    if (is_a($t, 'SimpleTime')) {
      $this->ticks += $t->seconds();
    } else {
      $this->ticks += $t;
    }
    $this->_setStr();
  }
  
  function addSecs($s) {
    $this->ticks+=$s;
    $this->_setStr();
  }

  function dayRound() {
    $this->setStr($this->datestring);
  }

  function weekRound() {
    $this->dayRound();
    $this->addDays(-1 * $this->dow());
  }
  
  function monthRound() {
    $this->dayRound();
    $this->addDays(-1*$this->dom()+1);
  }

  function quarterRound() {
    $month = $this->moy();
    $quarter = floor(($month-1)/3)*3+1;
    $this->setTimeParts(0,0,0,1,$quarter,$this->year());
  }
  
  function yearRound() {
    $this->dayRound();
    $this->addDays(-1*$this->doy());
  }
  
  /** 
   * returns the number of days between two dates ($this - $d) 
   * note that it will return fractional days across daylight saving boundaries
  **/
  function daysBetween($date) {
    return $this->subtract($date) / (24 * 60 * 60);
  }
  
  /** 
   * returns the number of days between two dates ($this - $date) accounting for daylight saving
  **/
  function dsDaysBetween($date) {
    //Calculate the number of days as a fraction, removing fractions due to daylight saving
    $numdays = $this->daysBetween($date);
    
    //We don't want to count an extra day (or part thereof) just because the day range 
    //includes going from summertime to wintertime so the date range includes an extra hour!

    $tz1 = date('Z', $this->ticks);
    $tz2 = date('Z', $date->ticks);
    if ($tz1 == $tz2) {
      // same timezone, so return the computed amount 
      #echo "Using numdays $tz1 $tz2 ";
      return $numdays;
    } else {
      // subtract the difference in the timezones to fix this
      #echo "Using tzinfo: $tz1 $tz2 ";
      return $numdays - ($tz2-$tz1) / (24*60*60);
    }
  }

  /** 
   * returns the number of days (or part thereof) between two dates ($this - $d) 
  **/
  function partDaysBetween($date) {
    //we want this to be an integer and since we want "part thereof" we'd normally round up
    //but daylight saving might cause problems....  We also have to include the part day at 
    //the beginning and the end
    
    $startwhole = $date;
    $startwhole->dayRound();
    $stopwhole = $this;
    $stopwhole->ticks += 24*60*60-1;
    $stopwhole->_setStr();
    $stopwhole->dayRound();
    
    return $stopwhole->dsDaysBetween($startwhole);
  }
   
  /** 
   * returns the number of seconds between two times
   * NB this takes into account daylight saving changes, so will not always give
   * the 24*60*60 for two datetimes that are 1 day apart...!
  **/
  function subtract($date) {
    #echo "$this->ticks - $date->ticks ";
    return $this->ticks - $date->ticks;
  }

  function timePart() {
    $timestring = strftime('%H:%M:%S', $this->ticks);
    return new SimpleTime($timestring,1);
  }

  function setTime($s) {
//     echo $this->dump().$s.'<br/>';
    $this->dayRound();
//     echo $this->dump().'<br/>';
    $time = new SimpleTime($s);
//     echo $time->dump().'<br/>';
    $this->addTimeParts($time->part('s'), $time->part('i'), $time->part('H'), 0,0,0);
//     echo $this->dump().'<br/>';
    return $this;
  }  
    

  function min($t) {
    $this->setTicks(min($t->ticks, $this->ticks));
  }

  function max($t) {
    $this->setTicks(max($t->ticks, $this->ticks));
  }
  
  /**
   * round time down to the nearest $g time-granularity measure
  **/
  function floorTime($g) {
    $tp = $this->timePart();
    $tp->floorTime($g);
    $this->setTime($tp->timestring);
  }
  
  function addTimeParts($sec=0, $min=0, $hour=0, $day=0, $month=0, $year=0) {
    $this->ticks = mktime(
                            date('H',$this->ticks) + $hour,
                            date('i',$this->ticks) + $min,
                            date('s',$this->ticks) + $sec,
                            date('m',$this->ticks) + $month,
                            date('d',$this->ticks) + $day,
                            date('y',$this->ticks) + $year
                        );
    $this->_setStr();
  }
  
  function setTimeParts($sec=0, $min=0, $hour=0, $day=0, $month=0, $year=0) {
    $this->ticks = mktime(
                            $hour,
                            $min,
                            $sec,
                            $month,
                            $day,
                            $year
                        );
    $this->_setStr();
  }
  
  /**
   * return the day of week of the current date. 
   * 0 == Sunday, 6 == Saturday
  **/
  function dow() {
    return date('w', $this->ticks);
  }
  
  function dowStr() {
    return date('l', $this->ticks);
  }
  
  /**
   * day of month   (1..31)
   */
  function dom() {
    return date('d', $this->ticks);
  }
  
  /**
   * month of year (1..12)
   */
  function moy() {
    return date('m', $this->ticks);
  }
  
  /**
   * day of year (0..365)
   */
  function doy() {
    return date('z', $this->ticks);
  }

  /**
   * year (YYYY)
   */
  function year() {
    return date('Y', $this->ticks);
  }
  
  /**
   * dump the datetimestring and ticks in a readable format
  **/
  function dump($html=1) {
    $s = 'ticks = ' . $this->ticks . ', ' . $this->datetimestring;
    $s .= ($html ? '<br />' : '') . "\n";
    return $s;
  }
  
} // class SimpleDate


class SimpleTime {
  var $timestring = '';
  var $ticks = '';
  var $isValid = 1;

  function SimpleTime($time) {
    #echo "New SimpleTime: $time, $type<br />";
    if (is_numeric($time)) {
      $this->setTicks($time);
    } elseif (is_a($time, 'SimpleTime')) {
      $this->setTicks($time->ticks);
    } else {
      $this->setStr($time);
    }
  }

  function setStr($s) {
    $this->_setTicks($s);
    $this->_setStr();
  }

  function _setStr() {
    $this->timestring = sprintf('%02d:%02d', $this->ticks/3600, $this->ticks%3600);
  }

  function setTicks($t) {
    $this->ticks = $t;
    $this->_setStr();
  }

  function _setTicks($s) {
    if (preg_match('/^(\d\d):(\d\d):(\d\d)$/', $s, $t)) {
      #preDump($t);
      $this->ticks = $t[1]*3600+$t[2]*60+$t[3];
    } elseif (preg_match('/^(\d\d):(\d\d)$/', $s, $t) || preg_match('/^(\d):(\d\d)$/', $s, $t)) {
      #preDump($t);
      $this->ticks = $t[1]*3600+$t[2]*60;
    } else {
      $this->ticks = 0;
      $this->inValid = 0;
    }
  }

  function subtract($d) {
    return $this->ticks - $d->ticks;
  }

  function addSecs($s) {
    $this->ticks += $s;
    $this->_setStr();
  }

  function seconds() {
    return $this->ticks;
  }
  
  function min($t) {
    $this->setTicks(min($t->ticks, $this->ticks));
  }

  function max($t) {
    $this->setTicks(max($t->ticks, $this->ticks));
  }

  function getHMSstring() {
    return sprintf('%02d:%02d:%02d', $this->ticks/3600, $this->ticks%3600, $this->ticks%60);
  }


  /**
   * round time down to the nearest $g time-granularity measure
  **/
  function floorTime($g) {
    $gt = $g->seconds();
    $this->setTicks(floor(($this->ticks+1)/$gt)*$gt);
  }
  
  function ceilTime($g) {
    $gt = $g->seconds();
    $this->setTicks(ceil(($this->ticks-1)/$gt)*$gt);
  }
  
  // return hour, minute or seconds parts of the time, emulating the date('H', $ticks) etc
  // functions, but not using them as they get too badly confused with timezones to be useful
  // in many situations
  function part($s) {
    switch ($s) {
      //we don't actually care about zero padding in this case.
      case 'H':
      case 'h':
        return floor($this->ticks/(60*60));
      //let's just allow 'm' to give minutes as well, as it's easier
      case 'i':
      case 'm':
        return floor(($this->ticks/60) % 60);
      case 's':
        return floor($this->ticks % (60*60));
    }
    //we can't use this as we're not actually using the underlying date-time types here.
    //return date($s, $this->ticks);
  }
  
  function addTime($t) {
    $this->ticks += $t->ticks;
    $this->_setStr();
  }
  
  /**
   * dump the timestring and ticks in a readable format
  **/
  function dump($html=1) {
    $s = 'ticks = ' . $this->ticks . ', ' . $this->timestring;
    $s .= ($html ? '<br />' : '') . "\n";
    return $s;
  }
} // class SimpleTime

?> 
