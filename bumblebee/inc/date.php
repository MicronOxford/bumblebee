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
    $this->ticks = mktime(
                            date('H',$t->ticks) + date('H',$this->ticks),
                            date('i',$t->ticks) + date('i',$this->ticks),
                            date('s',$t->ticks) + date('s',$this->ticks),
                            date('m',$this->ticks),
                            date('d',$this->ticks),
                            date('y',$this->ticks)
                        );
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
    $this->timestring = strftime('%H:%M', $this->ticks);
  }

  function setTicks($t) {
    $this->ticks = $t;
    $this->_setStr();
  }

  function _setTicks($s) {
    #echo "SimpleDate::Ticks $this->string<br />";
    $this->ticks = strtotime($s);
  }

  function subtract($d) {
    return $this->ticks - $d->ticks;
  }

  function addSecs($s) {
    $this->ticks += $s;
    $this->_setStr();
  }

  function seconds() {
    return date('s',$this->ticks)
         + date('i',$this->ticks)*60
         + date('H',$this->ticks)*60*60;
  }

} // class SimpleTime

?> 
