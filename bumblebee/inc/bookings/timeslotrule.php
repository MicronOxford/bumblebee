<?php
# $Id$
# Timeslot validation based on rules passed to us (presumably from an SQL entry)

include_once 'inc/dbforms/date.php';
include_once 'timeslot.php';

//enumeration of date-time operations for sharing around code between find operations
define('TSSTART',  0);
define('TSSTOP',   1);
define('TSWITHIN', 2);
define('TSNEXT',   3);
define('TSSLOT',   4);
// number of extra elements in each slot array ('picture')
define('TSARRAYMIN', 1);

  
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
   *         around the comma-separated slot specifications for that day. All times from 00:00 to 24:00
   *         must be included in the list
   * starttime-stoptime/num_slots 
   *         HH:MM time slots with the length of each slot.
   *         e.g. 09:00-17:00/1 specifies a slot starting at 9am and finishing 5pm.
   *              both 00:00 and 24:00 are valid, also 32:00 for 8am the next day (o'night)
   *         num_slots is integer, or wildcard of * for freely adjustable time
   *         e.g. 09:00-12:00/3 specifies 3 slots 9-10, 10-11 and 11-12.
   * 
   * EXAMPLES:
   *
   * [0-6]<00:00-08:00/*,08:00-13:00/05:00,13:00-18:00/05:00,18:00-24:00/*>
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
    #preDump($this->slots[$dow]['picture']);
    preg_match('/\<(.*)\>/', $this->slots[$dow]['picture'], $times);
    #preDump($times);
    if ($times[0] == '<>') {
      $times[1]='00:00-24:00/0';
    }
    
    $i=0;
    foreach(preg_split('/,/', $times[1]) as $slot) {
      #echo "found slot=$slot\n";
      $this->slots[$dow][$i] = array();
      $this->slots[$dow][$i]['picture'] = $slot;
      $tmp = array();
      if (preg_match('/(\d\d:\d\d)-(\d\d:\d\d)\/((\d+|\*))/', $slot, $tmp)) {
        $start = $tmp[1];
        $stop = $tmp[2];
        $this->slots[$dow][$i]['start'] = $start;
        $this->slots[$dow][$i]['stop'] = $stop;
        $tstart = new SimpleTime($start,1);
        $tstop  = new SimpleTime($stop, 1);
        $numslots = $tmp[3];
        #echo "(start,stop,slots) = ($start,$stop,$numslots)\n";
        if ($numslots != '*' && $numslots != '0') {
          #echo "Trying to interpolate timeslots\n";
          $tgran = new SimpleTime($tstop->subtract($tstart)/$numslots);
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
          $this->slots[$dow][$i]['unrestricted'] = $numslots == '*';
          $this->slots[$dow][$i]['notavailable'] = $numslots == '0';
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
    return $this->_findSlot($date, $type, TSSTART) != -1;
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
    $slot = $this->_findSlot($startdate, TSSTART, TSSLOT);
    return $slot->stop->ticks == $stopdate->ticks;
  }
  
  /**
   * return true if the specified dates & times are valid as above, but only occupy one slot
  **/
  function slotDisabled($startdate) {
    $slot = $this->_findSlot($startdate, TSSTART, TSSLOT);
//     echo "Disabled: $startdate->datetimestring\n";
//     preDump($slot);
    return $slot->isDisabled;
  }
    
  /**
   * return the corresponding stopdate/time to the specified start date.
   * ASSUMES that the specified date is a valid start, else behaviour is undefined.
  **/
  function findStop($date, $datetime=0) {
    return $this->_findSlot($date, TSSTART, TSSTOP, $datetime);
//     return ($slot >= 0) ? $this->slots[$date->dow()][$slot][TSSTOP] : 0;
  }
  
  /**
   * return the corresponding startdate/time to a time that is possibly within a slot
  **/
  function findSlotStart($date, $datetime=0) {
    return $this->_findSlot($date, TSWITHIN, TSSTART, $datetime);
//     echo "given slot=$slot\n";
//     return ($slot >= 0) ? $this->slots[$date->dow()][$slot][TSSTART] : 0;
  }

  /**
   * return the corresponding stopdate/time to a time that is possibly within a slot
  **/
  function findSlotStop($date, $datetime=0) {
    return $this->_findSlot($date, TSWITHIN, TSSTOP, $datetime);
//     return ($slot >= 0) ? $this->slots[$date->dow()][$slot][TSSTOP] : 0;
  }
  
  /**
   * returns the startdate/time that is >= to the date passed.
  **/
  function findNextSlot($date, $datetime=0) {
    return $this->_findSlot($date, TSNEXT, TSSTART, $datetime);
//     return ($slot >= 0) ? $this->slots[$date->dow()][$slot][TSSTOP] : 0;
  }
  
  
  /**
   * return the corresponding slot number of the starting or stopping date-time $date
   * returns -1 if no matching slot found.
   * $match is TSSTART, TSSTOP, TSWITHIN, TSNEXT depending on what matching is queried.
   * $return is TSSTART or TSSTOP depending on the required return value
  **/
  function _findSlot($date, $match, $return, $datetime=0) {
//     preDump(debug_backtrace());
    if ($datetime == 0) {
      $time = $date->timePart();
      $dow = $date->dow();
      $day = $date;
    } else {
      $time = $date;
      $dow = $datetime->dow();
      $day = $datetime;
    } 
    $slot=0;
    $timecmp = $match;
    if ($match == TSWITHIN) $timecmp = TSSTOP;
    if ($match == TSNEXT)   $timecmp = TSSTART;
//     echo "($time->timestring, $dow)";
//     preDump($this->slots[$dow]);
//     echo "Asking for ($dow, $slot, $timecmp, $match)<br />";
    while(
//             print_r("Asking for ($dow, $slot, $match, $return)<br />") && 
            $slot < count($this->slots[$dow])-TSARRAYMIN 
            && $time->ticks >= $this->slots[$dow][$slot][$timecmp]->ticks) {
//       echo $time->ticks .'-'. $this->slots[$dow][$slot][$timecmp]->ticks."\n";
//       echo $slot .'-'.(count($this->slots[$dow])-TSARRAYMIN)."\n";
      $slot++;
    }
    #$slot--;
//     echo "Final ($dow, $slot, $match, $return)<br />";
    if ($match == TSSTART || $match == TSSTOP) {
      $slot--;
      /*preDump($this->slots[$dow]);
      echo "m=$match";
      echo "slot= $slot\n";
      echo "count=". (count($this->slots[$dow])-TSARRAYMIN)."\n";
      echo "ticks=".$time->ticks."\n";
      echo "t2=". $this->slots[$dow][$slot][$timecmp]->ticks;*/
      $finalslot = ($slot < count($this->slots[$dow])-TSARRAYMIN
                    && $time->ticks == $this->slots[$dow][$slot][$timecmp]->ticks) 
                    ? $slot : -1 ;
    } elseif ($match == TSWITHIN) {
//       preDump($this->slots[$dow][$slot]);
/*      echo $time->dump();
      echo $this->slots[$dow][$slot][0]->dump();
      echo $this->slots[$dow][$slot][1]->dump();
      echo $slot < count($this->slots[$dow])-TSARRAYMIN;
      echo $time->ticks >= $this->slots[$dow][$slot][TSSTART]->ticks;
      echo $time->ticks <  $this->slots[$dow][$slot][TSSTOP]->ticks;*/
      
      $finalslot =  ($slot < count($this->slots[$dow])-TSARRAYMIN
                      && $time->ticks >= $this->slots[$dow][$slot][TSSTART]->ticks
                      && $time->ticks <  $this->slots[$dow][$slot][TSSTOP]->ticks) 
                      ? $slot : -1 ;
    } else { //TSNEXT
      //$slot++;
      if ($slot >= count($this->slots[$dow])-TSARRAYMIN) {
//         echo "Looking for next slot in overflow:\n";
        do {
//           echo "($dow, $day->datetimestring,".count($this->slots[$dow]).")\n";
          $dow = ($dow+1) % 7;
          $day->addDays(1);
          $finalslot=0;
        } while (count($this->slots[$dow]) <= TSARRAYMIN);
//         echo "($dow, $day->datetimestring,".TSARRAYMIN.")\n".count($this->slots[$dow]);
      } else {
        $finalslot = $slot;
      }
    }
//     echo "ReallyFinal ($dow, $finalslot, $match, $return)<br />";
    if ($return == TSSLOT) {
      $ts = new TimeSlot($day->setTime($this->slots[$dow][$finalslot][TSSTART]),
                         $day->setTime($this->slots[$dow][$finalslot][TSSTOP])  );
      $ts->isDisabled = issetSet($this->slots[$dow][$finalslot], 'notavailable');
      return $ts;
    } else {
//     echo "making foo";
//     return ($finalslot == -1) ? 0 : $day->setTime($this->slots[$dow][$finalslot][$return]);
      $foo = ($finalslot == -1 ? 0 : $day->setTime($this->slots[$dow][$finalslot][$return]) );
//     preDump($foo);
      return $foo;
    }
  }
  
  function dump($html=1) {
    #preDump($this->slots);
    #return;
    $eol = $html ? "<br />\n" : "\n";
    $s = '';
    $s .= "Initial Pattern = '". $this->picture ."'".$eol;
    for ($day=0; $day<=6; $day++) {
      $s .= "Day Pattern[$day] = '". $this->slots[$day]['picture'] ."'".$eol;
      //for ($j=0; $j<count($this->slots[$day]) && isset($this->slots[$day][$j]); $j++) {
      foreach ($this->slots[$day] as $k => $v) {
        if (is_numeric($k)) {
          $s .= "\t" . $v[TSSTART]->timestring 
                ." - ". $v[TSSTOP]->timestring .$eol;
        }
      }
    }
    return $s;
  }

  

} //class TimeSlotRule
