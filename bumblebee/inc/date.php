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

} // class SimpleDate

?> 
