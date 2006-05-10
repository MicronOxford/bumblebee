<?php
/**
* Vacancy object
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Bookings
*/

/** date manipulation routines */
require_once 'inc/date.php';
/** parent object */
require_once 'timeslot.php';

/**
* Vacancy object
*
* @package    Bumblebee
* @subpackage Bookings
*/
class Vacancy extends TimeSlot {
  
  /**
  *  Create a new vacancy slot
  *
  * @param array  $arr   field => value  
  */
  function Vacancy($arr='') {
    if (is_array($arr)) {
      $this->TimeSlot($arr['bookwhen'], $arr['stoptime'], $arr['duration']);
      #echo "Vacancy from ".$this->start->dateTimeString()." to ".$this->stop->dateTimeString()."<br />\n";
    }
    $this->isVacant = true;
    $this->baseclass='vacancy';
  }

  /**
  * Set the times for the slot
  *
  * @param SimpleDate  $start  start time/date
  * @param SimpleDate  $stop   stop time/date
  */
  function setTimes($start, $stop) {
    $duration = new SimpleTime($stop->subtract($start));
    $this->_TimeSlot_SimpleDate($start, $stop, $duration);
  }

  /**
  * display the vacancy as a list of settings
  *
  * @return string html representation of the slot
  */
  function display() {
    return $this->displayInTable();
  }
  
  /**
  * display the vacancy as a list of settings
  *
  * @return string html representation of the slot
  */
  function displayInTable() {
    return '<tr><td>Vacant'
            .'</td><td>'.$this->start->dateTimeString()
            .'</td><td>'.$this->stop->dateTimeString()
            .'</td><td>'
            .'</td></tr>'."\n";
  }

  /**
  * display the vacancy in a table calendar cell
  *
  * @global string base path to the installation
  * @return string html representation of the slot
  */
  function displayInCell($isadmin=0) {
    global $BASEPATH;
    $t = '';
    #echo $this->isDisabled ? 'disabled ' : 'active ';
    if ($isadmin || ! $this->isDisabled) {
      $start = isset($this->displayStart) ? $this->displayStart : $this->start;
      $stop  = isset($this->displayStop)  ? $this->displayStop  : $this->stop;
      $startticks = $start->ticks;
      $stopticks = $stop->ticks;
      $timedescription = sprintf(T_('Make booking from %s to %s'), $start->dateTimeString(), $stop->dateTimeString());
      //$timedescription = $this->start->timeString().' - '.$this->stop->timeString();
      $isodate = $start->dateString();
      $t .= '<div style="float:right;">'
              .'<a href="'
                  .$this->href.'&amp;isodate='.$isodate.'&amp;startticks='.$startticks.'&amp;stopticks='.$stopticks.'" '
                  .'class="but" title="'.$timedescription.'">'
                      .'<img src="'.$BASEPATH.'/theme/images/book.png" '
                          .'alt="'.$timedescription.'" '
                          .'class="calicon" />'
              .'</a>'
            .'</div>';
    }
    //echo 'Comment: '.$this->slotRule->comment.'<br/>';
    if ($this->slotRule->comment) {
      $t .= '<div class="calcomment" title="'.xssqw($this->slotRule->comment).'">'
                //.xssqw(sprintf('%20.20s',$this->slotRule->comment))
                .xssqw($this->slotRule->comment)
          .'</div>';
    } else {
      $t .= '&nbsp;';   // make sure there is some non-floating content in the table.
    }
    return $t;
  }

  /**
  * work out the title (start and stop times) for the vacancy for display
  *
  * @return string title
  */
  function generateBookingTitle() {
    $start = isset($this->displayStart) ? $this->displayStart : $this->original->start;
    $stop  = isset($this->displayStop)  ? $this->displayStop  : $this->original->stop;
    if ($this->isDisabled) {
      $t = sprintf(T_('Unavailable from %s to %s'), $start->dateTimeString(), $stop->dateTimeString());
    } else {
      $t = sprintf(T_('Vacant from %s to %s'), $start->dateTimeString(), $stop->dateTimeString());
    }
    return $t;
  }

} //class Vacancy
