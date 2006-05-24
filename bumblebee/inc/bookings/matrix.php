<?php
/**
* Booking matrix object for display in a table
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Bookings
*/

/**
* Booking matrix object for display in a table
*
* @package    Bumblebee
* @subpackage Bookings
* @todo       documentation
*/
class BookingMatrix {
  var $dayStart;
  var $dayStop;
  var $day; 
  var $granularity;
  var $bookings;
  var $numRows;
  var $rows;
  var $_lastnum = 0;
  
  function BookingMatrix($dayStart, $dayStop, $granularity, &$bookings) {
    $this->dayStart    = $dayStart;
    $this->dayStop     = $dayStop;
    $this->granularity = $granularity;
    $this->bookings    = $bookings;
  }
  
  function setDate($date) {
    $this->day = $date;
    $this->rows = array();
  }
  
  function getMatrix() {
    return $this->rows;
  }

  function prepareMatrix() {
    $this->numRows = $this->dayStop->subtract($this->dayStart)
                        / $this->granularity;
    $numBookings = count($this->bookings);

    #echo "Preparing matrix with $this->numRows rows for date "
        #.$this->day->dateString()."<br/>\n";

    // keep track of where we got up to in the calculations (reduces O(n2) to O(n))
    // provides 25% performance boost to this function
    $startnum = ($this->_lastnum - 2 > 0) ? $this->_lastnum : 0;
    $foundFlag = false;

    // foreach is much more expensive than for and since this function is called repeatedly,
    // it's best not to do that. (function is 20% faster from this change)
    //foreach ($this->bookings as $booknum => $b) {
    for ($booknum = $startnum; $booknum < $numBookings; $booknum++) {
      $b = $this->bookings[$booknum];
      #echo "Booking $k, ".$b->start->dateTimeString()." - ".$b->stop->dateTimeString()."<br />";
      // the TimeSlot object will cache the dayStart and dayStop calculations.
      // (function is 50% faster with this change)
      $bookDay = clone($this->bookings[$booknum]->dayStart());
      $bookStopDay = clone($this->bookings[$booknum]->dayStop());
      #echo "Checking eligibility for booking: ".$this->day->ticks .'='.$bookDay->ticks.'||'.$bookStopDay->ticks.'<br />';
      if ($bookDay->ticks == $this->day->ticks) {
        $foundFlag = true;
        $bookDayStart = clone($bookDay);
        $mystart = clone( isset($b->displayStart) ? $b->displayStart : $b->start );
        $mystop  = clone( isset($b->displayStop)  ? $b->displayStop  : $b->stop  );
        $bookDayStart->setTime($this->dayStart);
        //$starttime = $b->start->subtract($bookDayStart);
        $starttime = $mystart->subtract($bookDayStart);
        if ($starttime > 0) {
          //then the start of the booking is after the start time of the matrix
          $rowstart = floor($starttime/$this->granularity);
        } else {
          //the booking starts before the matrix; starting row adjusted
          $rowstart = 0;
        }
        $bookDayStop = clone($bookDay);
        $bookDayStop->setTime($this->dayStop);
        //$stoptime = $b->stop->subtract($bookDayStop);
        $stoptime = $mystop->subtract($bookDayStop);
        if ($stoptime < 0) {
          //the stop time is before the stop time of the matrix
          //$stoptimestart = $b->stop->subtract($bookDayStart);
          $stoptimestart = $mystop->subtract($bookDayStart);
          $rowstop = floor($stoptimestart/$this->granularity);
        } else {
          //the stop time is after the stop time of the matrix,
          //adjust the duration
          $rowstop = $this->numRows;
        }
        $rowspan = round($rowstop - $rowstart);

        // Only add the cell to the matrix if the cell doesn't already have an entry in it.
        // Otherwise, the second part of a booking that is split across multiple slots would
        // overwrite the earlier part.
        if (! isset($this->rows[$rowstart]) && $rowspan > 0) {
          $cell = new BookingCell($this->bookings[$booknum],$this->bookings[$booknum]->isStart,$rowspan);
          $this->rows[$rowstart] = $cell;
        }
      } else {
        // since the list of bookings should be in date order, once we get a negative match
        // we can return
        if ($foundFlag) {
          $this->_lastnum = $booknum;
          return;
        }
      }
    }
    $this->_lastnum = $booknum;
  }

} //class BookingMatrix
?>
