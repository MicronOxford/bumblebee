<?php
# $Id$
# a simple date class to perform basic date calculations

class SimpleDate {
  var $datestring = '',
      $ticks  = '',
      $datetimestring = '';


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
    $this->ticks += $d*24*60*60;
    $this->_setStr();
  }

  function addTime($t) {
    /*$this->ticks = mktime(
                            date('H',$t->ticks) + date('H',$this->ticks),
                            date('i',$t->ticks) + date('i',$this->ticks),
                            date('s',$t->ticks) + date('s',$this->ticks),
                            date('m',$this->ticks),
                            date('d',$this->ticks),
                            date('y',$this->ticks)
                        );
    */
    $this->ticks += $t->seconds();
    $this->_setStr();
  }

  function dayRound() {
    $this->setStr($this->datestring);
  }

  function daysBetween($d) {
    return $this->subtract($d) / (24 * 60 * 60);
  }

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
  **/
  function floorTime($g) {
    $tp = $this->timePart();
    $tp->floorTime($g);
    $this->setTime($tp->timestring);
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
  
} // class SimpleTime

?> 
