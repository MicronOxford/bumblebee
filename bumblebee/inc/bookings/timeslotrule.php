<?php
# $Id$
# Timeslot validation based on rules passed to us (presumably from an SQL entry)

include_once '../../dbforms/date.php';

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
   *         HH:MM time slots
   *         e.g. 09:00-17:00 specifies a slot starting at 9am and finishing 5pm.
   *              both 00:00 and 24:00 are valid, also 32:00 for 8am the next day (o'night)
   * starttime-stoptime/granularity 
   *         granularity in HH:MM too, or wildcard of * for freely adjustable time
   *         e.g. 09:00-12:00/01:00 specifies 3 slots 9-10, 10-11 and 11-12.
   * 
   * EXAMPLES:
   *
   * [0-6]<00:00-08:00/*,08:00-13:00,13:00-18:00,18:00-24:00/*>
   *    Available all days, free booking until 8am and after 6pm, other slots as defined
   * [0]<>[1-5]<09:00-17:00/01:00>[6]<>
   *    Not available Sunday and Saturday. Only available weekdays 9am till 5pm with 1hr booking
   *    granularity.
   * [0]<>[1-5]<00:00-09:00/*,09:00-17:00/01:00,17:00-24:00/*>[6]<>
   *    Not available Sunday and Saturday. Available weekdays 9am till 5pm with 1hr booking
   *    granularity, before 9am and after 5pm with no granularity
   * [0]<>[1-5]<09:00-13:00/01:00,13:00-17:00/02:00,17:00-33:00/16:00>[6]<>
   *    Not available Sunday and Saturday. Available weekdays 9am till 5pm with 1hr booking
   *    granularity, before 9am and after 5pm with no granularity
   *
  **/
  function _interpret() {
    $this->slots = array();
    for ($dow=0; $dow<=6; $dow++) {
      $this->slots[$dow] = array();
    }
    $this->_findDayLines();
    for ($dow=0; $dow<=6; $dow++) {
      $this->_fillDaySlots($dow);
    }
  } 
  
  function _findDayLines() {  
    $daylines = array();
    preg_match_all('/\[.+?\>/', $this->picture, $daylines);
    foreach ($daylines[0] as $d) {
      $day = array();
      #echo "considering $d\n";
      if (preg_match('/\[(\d)\]/', $d, $day)) {
        #echo "found match: ".$day[1]."\n";
        $this->slots[$day[1]]['picture']=$d;
      }
      if (preg_match('/\[(\d)-(\d)\]/', $d, $day)) {
        #echo "found multimatch: ".$day[1].'.'.$day[2]."\n";
        for ($i=$day[1]; $i<=$day[2]; $i++) {
          $this->slots[$i]['picture']=$d;
        }
      }
    }
  }
  
  function _fillDaySlots($dow) {
    $times = array();
    preg_match('/\<(.+)\>/', $this->slots[$dow]['picture'], $times);
    $i=0;
    foreach(preg_split('/,/', $times[1]) as $slot) {
      #echo "found slot=$slot\n";
      $this->slots[$dow][$i] = array();
      $this->slots[$dow][$i]['picture'] = $slot;
      $tmp = array();
      if (preg_match('/(\d\d:\d\d)-(\d\d:\d\d)\/((\d\d:\d\d|\*))/', $slot, $tmp)) {
        $start = $tmp[1];
        $stop = $tmp[2];
        $this->slots[$dow][$i]['start'] = $start;
        $this->slots[$dow][$i]['stop'] = $stop;
        $tstart = new SimpleTime($start,1);
        $tstop  = new SimpleTime($stop, 1);
        $granularity = $tmp[3];
        #echo "(start,stop,gran) = ($start,$stop,$granularity)\n";
        if ($granularity != '*') {
          #echo "Trying to interpolate timeslots\n";
          $tgran = new SimpleTime($granularity, 1);
          $this->slots[$dow][$i]['granularity'] = $tgran;
          for ($time = $tstart; $time->ticks < $tstop->ticks; $time->addTime($tgran)) {
            #echo $time->timestring.' '. $tstop->timestring.' '.$tgran->timestring."\n";
            $this->slots[$dow][$i][TSSTART] = $time;
            $this->slots[$dow][$i][TSSTOP]  = $time;
            $this->slots[$dow][$i][TSSTOP]->addTime($tgran);
            $this->slots[$dow][$i]['unrestricted'] = 0;
            $i++;
          }
        } else {
          #echo "No need to interpolate\n";
          $this->slots[$dow][$i][TSSTART] = new SimpleTime($tmp[1], 1);
          $this->slots[$dow][$i][TSSTOP] = new SimpleTime($tmp[2], 1);
          $this->slots[$dow][$i]['unrestricted'] = 1;
          $this->slots[$dow][$i]['granularity'] = 0;
          $i++;
        }
      }
    }
    #var_dump($this->slots);
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
    $dow = $date->dow();
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
    $dow = $date->dow();
    //FIXME: stub
  }
  
  
  function dump() {
    //var_dump($this->slots);
    //return;
    $s = '';
    $s .= "Initial Pattern = '". $this->picture ."'\n";
    for ($day=0; $day<=6; $day++) {
      $s .= "Day Pattern[$day] = '". $this->slots[$day]['picture'] ."'\n";
      for ($j=0; $j<count($this->slots[$day]) && isset($this->slots[$day][$j]); $j++) {
        $s .= "\t" . $this->slots[$day][$j][TSSTART]->timestring 
              ." - ". $this->slots[$day][$j][TSSTOP]->timestring ."\n";
      }
    }
    return $s;
  }

  

} //class TimeSlotRule
