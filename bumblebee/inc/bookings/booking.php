<?php
/**
* Object for an individual booking
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

require_once 'inc/bb/configreader.php';

/** date manipulation routines */
require_once 'inc/date.php';
/** parent object */
require_once 'timeslot.php';

/**
* Object for an individual booking
*
* @package    Bumblebee
* @subpackage Bookings
*/
class Booking extends TimeSlot {
  /** @var integer    booking id number  */
  var $id;
  /** @var integer    percentage discount to be applied to the booking  */
  var $discount;
  /** @var string     log message for the instrument log book  */
  var $log;
  /** @var string     log message for the booking calendar  */
  var $comments;
  /** @var integer    project id number  */
  var $project;
  /** @var integer    user id number for user that will use instrument */
  var $userid;
  /** @var string     username of user for user that will use instrument */
  var $username;
  /** @var string     full name of user for user that will use instrument */
  var $name;
  /** @var string     email address for user that will use instrument */
  var $useremail;
  /** @var string     phone number for user that will use instrument */
  var $userphone;
  /** @var integer    user id number for user that booked the instrument */
  var $masquserid;
  /** @var integer    full name of user for user that booked the instrument */
  var $masquser;
  /** @var string     username of user for user that booked the instrument */
  var $masqusername;
  /** @var string     email address for user that booked the instrument */
  var $masqemail;
  /** @var string     instrument name */
  var $instrumentName;
  /** @var string     instrument description */
  var $instrumentDescription;

  /**
  *  Create a booking object
  *
  * @param array  $arr  key => value paids
  */
  function Booking($arr) {
    $this->TimeSlot($arr['bookwhen'], $arr['stoptime'], $arr['duration']);
    $isVacant = false;
    $this->id = $arr['bookid'];
    $this->discount = $arr['discount'];
    $this->log = $arr['log'];
    $this->comments = $arr['comments'];
    $this->project = $arr['project'];
    $this->userid = $arr['userid'];
    $this->username = $arr['username'];
    $this->name = $arr['name'];
    $this->useremail = $arr['email'];
    $this->userphone = $arr['phone'];
    $this->masquserid = $arr['masquserid'];
    $this->masquser = $arr['masquser'];
    $this->masqusername = $arr['masqusername'];
    $this->masqemail = $arr['masqemail'];
    $this->instrumentName = $arr['instrumentname'];
    $this->instrumentDescription = $arr['instrumentdescription'];

    #echo "Booking from ".$this->start->dateTimeString()." to ".$this->stop->dateTimeString()."<br />\n";
    $this->baseclass='booking';
  }

  /**
  * display the booking as a list of settings
  *
  * @param boolean   $displayAdmin   show admin-only information (discount etc)
  * @param boolean   $displayOwner   show owner-only information (project etc)
  * @return string html representation of booking
  */
  function display($displayAdmin, $displayOwner) {
    return $this->displayInTable(2, $displayAdmin, $displayOwner);
  }

  /**
  * display the booking as a list of settings
  *
  * @param boolean   $displayAdmin   show admin-only information (discount etc)
  * @param boolean   $displayOwner   show owner-only information (project etc)
  * @return string html representation of booking
  */
  function displayInTable($cols, $displayAdmin, $displayOwner) {
    $t = '<tr><td>'.T_('Booking ID').'</td><td>'.$this->id.'</td></tr>'."\n"
       . '<tr><td>'.T_('Start').'</td><td>'.$this->start->dateTimeString().'</td></tr>'."\n"
       . '<tr><td>'.T_('Stop').'</td><td>'.$this->stop->dateTimeString().'</td></tr>'."\n"
       . '<tr><td>'.T_('Duration').'</td><td>'.$this->duration->timeString()/*.$bookinglength*/.'</td></tr>'."\n"
       . '<tr><td>'.T_('User').'</td><td><a href="mailto:'.xssqw($this->useremail).'">'.xssqw($this->name).'</a> ('.xssqw($this->username).')</td></tr>'."\n"
       . '<tr><td>'.T_('Comments').'</td><td>'.xssqw($this->comments).'</td></tr>'."\n"
       . '<tr><td>'.T_('Log').'</td><td>'.xssqw($this->log).'</td></tr>'."\n";
    if ($displayAdmin) {
      if ($this->masquser) {
        $t .= '<tr><td>'.T_('Booked by').'</td><td><a href="mailto:'.xssqw($this->masqemail).'">'.xssqw($this->masquser).'</a> ('.xssqw($this->masqusername).')</td></tr>'."\n";
      }
    }
    if ($displayAdmin || $displayOwner) {
      $t .= '<tr><td>'.T_('Project').'</td><td>'.xssqw($this->project).'</td></tr>'."\n";
      if ($this->discount) {
        $t .= '<tr><td>'.T_('Discount').'</td><td>'.xssqw($this->discount).'</td></tr>'."\n";
      }
    }
    return $t;
  }

  /**
  * display the booking as a single cell in a calendar
  *
  * @return string html representation of booking
  */
  function displayInCell(/*$isadmin=0*/) {
    $conf = ConfigReader::getInstance();
    $BasePath = $conf->BasePath;
    $start = isset($this->displayStart) ? $this->displayStart : $this->start;
    $stop  = isset($this->displayStop)  ? $this->displayStop  : $this->stop;
    if ($this->freeBusyOnly) {
      static $freeBusyStr = null;
      static $busyStr = null;
      if ($freeBusyStr === null) {
        $freeBusyStr = T_('Busy from %s to %s');
        $busyStr     = T_('busy');
      }
      $timedescription = sprintf($freeBusyStr, $start->dateTimeString(), $stop->dateTimeString());
      return "<div title='$timedescription'>$busyStr</div>";
    }
    static $viewEditStr = null;
    if ($viewEditStr === null) $viewEditStr = T_('View or edit booking from %s to %s');
    $timedescription = sprintf($viewEditStr, $start->dateTimeString(), $stop->dateTimeString());
    //$timedescription = $this->start->timeString().' - '.$this->stop->timeString();
    $isodate = $start->dateString();
    $t = '';
    $t .= "<div style='float:right;'><a href='$this->href&amp;isodate=$isodate&amp;bookid=$this->id' "
              ."title='$timedescription' class='but'><img src='$BasePath/theme/images/editbooking.png' "
              ."alt='$timedescription' class='calicon' /></a></div>";
    // Finally include details of the booking:
    $t .= '<div class="calbookperson">'
         .'<a href="mailto:'.xssqw($this->useremail).'">'
         .xssqw($this->name).'</a></div>';
    if ($conf->value('calendar', 'showphone', false)) {
      $t .= '<div class="calphone">'
          .xssqw($this->userphone)
          .'</div>';
    }
    if ($this->comments) {
      $t .= '<div class="calcomment">'
          .xssqw($this->comments)
          .'</div>';
    }
    return $t;
  }

  /**
  * work out the title (start and stop times) for the booking for display
  *
  * @return string title
  */
  function generateBookingTitle() {
    static $bookingStr = null;
    if ($bookingStr === null) $bookingStr = T_('Booking from %s - %s');

    $start = isset($this->displayStart) ? $this->displayStart : $this->start;
    $stop  = isset($this->displayStop)  ? $this->displayStop  : $this->stop;
    return sprintf($bookingStr, $start->dateTimeString(), $stop->dateTimeString());
  }

  /**
  * construct a long description of the time slot for pop-ups
  *
  * @return string description
  */
  function generateLongDescription($short=false) {
    $s = '';
    if (! $short && isset($this->children) && is_array($this->children)) {
      $list = array();
      foreach ($this->children as $b) {
        $list[] = $b->generateLongDescription(true);
      }
      #print $s;
      $s = '<table class="popup">'. join($list, "") .'</table>';
    } elseif ($short) {
      $instrument = $this->instrumentName;
      $user = $this->name;
      $start = isset($this->displayStart) ? $this->displayStart : $this->start;
      $stop  = isset($this->displayStop)  ? $this->displayStop  : $this->stop;
      $starttime = $start->timeString();
      $stoptime  = $stop->timeString();
      $s = "<tr><td>$instrument</td><td>$user</td><td>$starttime</td><td>$stoptime</td></tr>";
    } else {
      $s = '<table class="popup">'. $this->displayInTable(2, false, false) .'</table>';
    }

    #print $s;
    return $s;
  }

} //class Booking
