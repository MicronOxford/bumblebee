<?php
# $Id$
# Timeslot validation based on rules passed to us (presumably from an SQL entry)

include_once 'inc/dbforms/date.php';
  define('TSSTART', 0);
  define('TSSTOP',  1);

  
class TimeSlotRule {
  var $picture = '';
  var $slots;
  
  function TimeSlotRule($pic) {
    $this->picture = $pic;
    $this->_interpret();
  }
  
  /** 
   * timeslot picture syntax (concatinate onto one line, no spaces)
   * [x] or [x-y] 
   *         specify the day of week (or range of days). 0=Sunday, 6=Saturday
   * <slot,slot,slot> 
   *         around the comma-separated slot specifications for that day
   * starttime-stoptime 
   *         HH:MM time slots with optional wildcard of *
   *         e.g. 09:00-17:00 specifies a slot starting at 9am and finishing 5pm.
   *              *-09:00 means all times up until 9am.
   * starttime-stoptime/granularity 
   *         granularity in HH:MM too
   *         e.g. 09:00-12:00/01:00 specifies 3 slots 9-10, 10-11 and 11-12.
   * 
   * EXAMPLES:
   *
   * [0-6]<*-08:00,08:00-13:00,13:00-18:00,18:00-*>
   *    Available all days, free booking until 8am and after 6pm, other slots as defined
   * [0]<>[1-5]<09:00-17:00/01:00>[6]<>
   *    Not available Sunday and Saturday. Only available weekdays 9am till 5pm with 1hr booking
   *    granularity.
   *
  **/
  function _interpret() {
    $this->slots = array();
    for ($dow=0; $dow<=6; $dow++) {
      $this->slots[$dow] = array();
    }
    $daylines = array();
    preg_match_all('/\[.+?\>/', $this->picture, $daylines);
    foreach ($daylines[0] as $d) {
      $day = array();
      if (preg_match('/\[(\d)\]/', $d[1], $day)) {
        $this->slots[$day[1]]['picture']=$d;
      }
    }
  }
  
  /**
   * return true if the specified date & time correspond to a valid starting time according
   * to this object's slot rules.
  **/
  function isValidStart($date) {
    return $this->_isValidStartStop($date, TSSTART);
  }

  /**
   * return true if the specified date & time correspond to a valid stopping time according
   * to this object's slot rules.
  **/
  function isValidStop($date) {
    return $this->_isValidStartStop($date, TSSTOP);
  }
  
  /**
   * perform the above operations with no code duplication
  **/
  function _isValidStartStop($date, $type) {
    $time = $date->timePart();
    $dow = date('w', $date->ticks);
    $slot=0;
    while($slot < count($this->slots[$dow][$slot]) 
            && $time->ticks <= $this->slots[$dow][$slot][$type]->ticks) {
      $slot++;
    }
    return $time->ticks == $this->slots[$dow][$slot][$type]->ticks;
  }
  
  /**
   * return true if the specified dates & times are valid start/stop times
   * to this object's slot rules.
  **/
  function isValidSlot($startdate, $stopdate) {
    return $this->isValidStart($startdate) && $this->isValidStop($stopdate);
  }
  
  /**
   * return true if the specified dates & times are valid as above, but only occupy one slot
  **/
  function isValidSingleSlot($startdate, $stopdate) {
    if (! $this->isValidStot($startdate, $stopdate)) {
      return false;
    }
    $slotstop = $this->findStop($startdate);
    return $slotstop->ticks == $stopdate->ticks;
  }
  
  
  /**
   * return the corresponding stopdate/time to the specified start date.
  **/
  function findStop($date) {
    
  }
  
  
  function display($displayAdmin, $displayOwner) {
    return $this->displayInTable(2, $displayAdmin, $displayOwner);
  }

  

} //class TimeSlotRule
