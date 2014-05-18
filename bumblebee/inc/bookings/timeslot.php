<?php
/**
* Booking/Vacancy base object -- designed to be inherited by Vacancy and Booking
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Bookings
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

/** date manipulation routines */
require_once 'inc/date.php';

/**
* Booking/Vacancy base object -- designed to be inherited by Vacancy and Booking
*
* @package    Bumblebee
* @subpackage Bookings
*/
class TimeSlot {
  /** @var SimpleDate   start of the slot as used for calculating graphical representation   */
  var $start;
  /** @var SimpleDate   end of the slot as used for calculating graphical representation  */
  var $stop;
  /** @var SimpleDate   date the start is on (i.e. $start with time of 00:00:00)   */
  var $_dayStart = NULL;
  /** @var SimpleDate   date the end is on (i.e. $stop with time of 00:00:00)  */
  var $_dayStop = NULL;
  /** @var SimpleTime   unused?  */
  var $duration;
  /** @var string       href to current page */
  var $href = '';
  /** @var string       html/css class to use for display */
  var $baseclass;
  /** @var boolean      time slot is disabled (instrument is unavailable)  */
  var $isDisabled=0;
  /** @var boolean      instrument is vacant  */
  var $isVacant = 0;
  /** @var boolean      timeslot is the start of a booking (bookings can go fro mone day to another)  */
  var $isStart = 0;
  /** @var SimpleDate   start of the slot for when time should be displayed   */
  var $displayStart;
  /** @var SimpleDate   end of the slot for when time should be displayed  */
  var $displayStop;
  /** @var boolean      start time is arbitrary from truncation due to db lookup */
  var $arb_start = false;
  /** @var boolean      stop time is arbitrary from truncation due to db lookup */
  var $arb_stop  = false;
  /** @var TimeSlotRule timeslot definitions */
  var $slotRule;
  /** @var boolean      only show free/busy information for the slot */
  var $freeBusyOnly = false;

  /**
  *  Create a new timeslot to be superclassed by Booking or Vacancy object
  *
  * @param mixed  $start    start time and date (SimpleDate or string or ticks)
  * @param mixed  $stop     stop time and date (SimpleDate or string or ticks)
  * @param mixed  $duration duration of the slot (SimpleTime or string or ticks, 0 to autocalc)
  */
  function TimeSlot($start, $stop, $duration=0) {
    $this->start = new SimpleDate($start);
    $this->stop = new SimpleDate($stop);
    if ($duration==0) {
      $this->duration = new SimpleTime($this->stop->ticks - $this->start->ticks);
    } else {
      $this->duration = new SimpleTime($duration);
    }
  }

  /**
  *  Set the start/stop times of the slot
  *
  * @param SimpleDate  $start    start time and date
  * @param SimpleDate  $stop     stop time and date
  * @param SimpleTime  $duration duration of the slot
  */
  function _TimeSlot_SimpleDate($start, $stop, $duration) {
    $this->start = $start;
    $this->stop = $stop;
    $this->duration = $duration;
  }

  /**
  * display the timeslot as a short table row
  */
  function displayShort() {
    return '<tr><td>'.get_class($this)
            .'</td><td>'.$this->start->dateTimeString()
            .'</td><td>'.$this->stop->dateTimeString()
            .'</td><td>'.(is_object($this->displayStart) ? $this->displayStart->dateTimeString() : 'NULL')
            .'</td><td>'.(is_object($this->displayStop)  ? $this->displayStop->dateTimeString()  : 'NULL')
            .'</td><td>'.$this->isStart
            .'</td></tr>'."\n";
  }

  function dayStart() {
    if ($this->_dayStart !== NULL) {
      #echo "start hit ";
      return $this->_dayStart;
    }
      #echo "start miss ";
    $this->_dayStart = clone($this->start);
    $this->_dayStart->dayRound();
    return $this->_dayStart;
  }

  function dayStop() {
    if ($this->_dayStop !== NULL) {
      #echo "stop hit ";
      return $this->_dayStop;
    }
      #echo "stop miss ";
    $this->_dayStop = clone($this->stop);
    $this->_dayStop->dayRound();
    return $this->_dayStop;
  }

  /**
  * construct a long description of the time slot for pop-ups
  *
  * @return string description
  */
  function generateLongDescription() {
    return "";
  }

  /**
  * work out the title (start and stop times) for the vacancy for display
  *
  * @return string title
  */
  function generateBookingTitle() {
    return "";
  }

} //class TimeSlot
