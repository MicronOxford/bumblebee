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


  function SimpleDate($time, $type=0) {
    #echo "New SimpleDate: $time, $type<br />";
    if ($type) {
      $this->setStr($time);
    } else {
      $this->setTicks($time);
    }
  }

  function setStr($s) {
    $this->_setTicks($s);
    $this->_setStr();
  }

  function _setStr() {
    #echo "SimpleDate::Str $this->ticks<br />";
    $this->datestring = strftime('%Y-%m-%d', $this->ticks);
    $this->datetimestring = strftime('%Y-%m-%d %H:%M:%S', $this->ticks);
  }

  function setTicks($t) {
    $this->ticks = $t;
    $this->_setStr();
  }

  function _setTicks($s) {
    #echo "SimpleDate::Ticks $this->string<br />";
    $this->ticks = strtotime($s);
  }

  function addDays($d) {
    $this->addTimeParts(0,0,0,$d,0,0);
  }

  function addTime($t) {
    $this->addTimeParts($t->part('s'),$t->part('i'),$t->part('h'),0,0,0);
  }

  function dayRound() {
    $this->setStr($this->datestring);
  }

  /** 
   * returns the number of days between two dates ($this - $d) 
   * note that it will return fractional days across daylight saving boundaries
  **/
  function daysBetween($d) {
    return $this->subtract($d) / (24 * 60 * 60);
  }
  
  /** 
   * returns the number of days (or part thereof) between two dates ($this - $d) 
  **/
  function daysBetween($d) {
    //Calculate the number of days as a fraction.
    $numdays = $this->daysBetween($d);
    
    //we want this to be an integer and since we want "part thereof" we'd normally round up
    //but daylight saving might cause problems.... 
    //The case we don't want to count an extra day just because the day range includes going
    //from summertime to wintertime so the date range includes an extra hour!
    
    
    //(PHP's silly banker's rounding would also make like harder for us here...)
    //Since PHP stupidly uses bankers rounding, we subtract a fuzz factor 
   return $this->subtract($d) / (24 * 60 * 60);
  }
 
  /** 
   * returns the number of seconds between two times
   * NB this takes into account daylight saving changes, so will not always give
   * the 24*60*60 for two datetimes that are 1 day apart...!
  **/
  function subtract($d) {
    #echo "$this->ticks - $d->ticks ";
    return $this->ticks - $d->ticks;
  }

  function timePart() {
    $timestring = strftime('%H:%M:%S', $this->ticks);
    return new SimpleTime($timestring,1);
  }

  function setTime($s) {
    #echo $this->datetimestring.'-'.$this->ticks.'/'.$s.'<br/>';
    $this->dayRound();
    #echo $this->datetimestring.'-'.$this->ticks.'<br/>';
    $time = new SimpleTime($s,1);
    #echo $time->timestring.'-'.$time->ticks.'<br/>';
    $this->addTime($time);
    ##echo $this->datetimestring.'-'.$this->ticks.'<br/>';
  }
    

  function min($t) {
    $this->setTicks(min($t->ticks, $this->ticks));
  }

  function max($t) {
    $this->setTicks(max($t->ticks, $this->ticks));
  }
  
  /**
   * round time down to the nearest $g time-granularity measure
   * FIXME: does this lose the date part of the time??
  **/
  function floorTime($g) {
    $tp = $this->timePart();
    $tp->floorTime($g);
    $this->setTime($tp->timestring);
  }
  
  function addTimeParts($sec, $min, $hour, $day, $month, $year) {
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
} // class SimpleDate


class SimpleTime {
  var $timestring = '',
      $ticks = '';

  function SimpleTime($time, $type=0) {
    #echo "New SimpleTime: $time, $type<br />";
    if ($type) {
      $this->setStr($time);
    } else {
      $this->setTicks($time);
    }
  }

  function setStr($s) {
    $this->_setTicks($s);
    $this->_setStr();
  }

  function _setStr() {
    #echo "SimpleDate::Str $this->ticks<br />";
    #$this->timestring = strftime('%H:%M', $this->ticks);
    //$ticks = $this->seconds();
    $this->timestring = sprintf('%02d:%02d', $this->ticks/3600, $this->ticks%3600);
  }

  function setTicks($t) {
    $this->ticks = $t;
    $this->_setStr();
  }

  function _setTicks($s) {
    #echo "SimpleDate::Ticks $this->string<br />";
    //$this->ticks = strtotime($s);
    #echo "matching $s for time HH:MM or HH:MM:SS\n";
    if (preg_match('/^(\d\d):(\d\d):(\d\d)$/', $s, $t)) {
      #preDump($t);
      $this->ticks = $t[1]*3600+$t[2]*60+$t[3];
    } else {
      preg_match('/^(\d\d):(\d\d)$/', $s, $t);
      #preDump($t);
      $this->ticks = $t[1]*3600+$t[2]*60;
    }
    #echo $this->ticks;
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
    /*
    return date('s',$this->ticks)
         + date('i',$this->ticks)*60
         + date('H',$this->ticks)*60*60;
    */
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
    $gt = $g->seconds();
    $this->setTicks(floor(($this->ticks+1)/$gt)*$gt);
  }
  
  function ceilTime($g) {
    $gt = $g->seconds();
    $this->setTicks(ceil(($this->ticks-1)/$gt)*$gt);
  }
  
  function part($s) {
    return date($s, $this->ticks);
  }
  
} // class SimpleTime

?> 
