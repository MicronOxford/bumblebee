<?php
# $Id$
# Booking object

include_once 'inc/date.php';
include_once 'timeslot.php';

class Booking extends TimeSlot {
  var $id;
  var $discount;
  var $log;
  var $comments;
  var $project;
  var $userid;
  var $username;
  var $name;
  var $useremail;
  var $masquserid;
  var $masquser;
  var $masqusername;
  var $masqemail;
  
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
    $this->masquserid = $arr['masquserid'];
    $this->masquser = $arr['masquser'];
    $this->masqusername = $arr['masqusername'];
    $this->masqemail = $arr['masqemail'];

    #echo "Booking from ".$this->start->datetimestring." to ".$this->stop->datetimestring."<br />\n";
    $this->baseclass='booking';
  }

  function display($displayAdmin, $displayOwner) {
    return $this->displayInTable(2, $displayAdmin, $displayOwner);
  }
  
  function displayInTable($cols, $displayAdmin, $displayOwner) {
    $t = '<tr><td>Booking ID</td><td>'.$this->id.'</td></tr>'."\n"
       . '<tr><td>Start</td><td>'.$this->start->datetimestring.'</td></tr>'."\n"
       . '<tr><td>Stop</td><td>'.$this->stop->datetimestring.'</td></tr>'."\n"
       . '<tr><td>Duration</td><td>'.$this->duration->timestring/*.$bookinglength*/.'</td></tr>'."\n"
       . '<tr><td>User</td><td><a href="mailto:'.$this->useremail.'">'.$this->name.'</a> ('.$this->username.')</td></tr>'."\n"
       . '<tr><td>Comments</td><td>'.$this->comments.'</td></tr>'."\n"
       . '<tr><td>Log</td><td>'.$this->log.'</td></tr>'."\n";
    if ($displayAdmin) {
      if ($this->masquser) {
        $t .= '<tr><td>Booked by</td><td><a href="mailto:'.$this->masqemail.'">'.$this->masquser.'</a> ('.$this->masqusername.')</td></tr>'."\n";
      }
    }
    if ($displayAdmin || $displayOwner) {
      $t .= '<tr><td>Project</td><td>'.$this->project.'</td></tr>'."\n";
      if ($this->discount) {
        $t .= '<tr><td>Discount</td><td>'.$this->discount.'</td></tr>'."\n";
      }
    }
    return $t;
  }

  //function displayCellDetails() {
  function displayInCell($isadmin=0) {
//     preDump($this);
    global $BASEPATH;
    $start = isset($this->displayStart) ? $this->displayStart : $this->start;
    $stop  = isset($this->displayStop)  ? $this->displayStop  : $this->stop;
    $timedescription = $start->datetimestring.' - '.$stop->datetimestring;
    //$timedescription = $this->start->timestring.' - '.$this->stop->timestring;
    $isodate = $start->datestring;
    $t = '';
    $t .= "<div style='float:right;'><a href='$this->href/$isodate/$this->id' title='View or edit booking $timedescription' class='but'><img src='$BASEPATH/theme/images/editbooking.png' alt='View/edit booking $timedescription' class='calicon' /></a></div>";
    // Finally include details of the booking:
    $t .= '<div class="calbookperson">'
         .'<a href="mailto:'.$this->useremail.'">'
         .$this->name.'</a></div>';
    if ($this->comments) {
      $t .= '<div class="calcomment">'
          .xssqw($this->comments)
          .'</div>';
    }
    return $t;
  }

  function generateBookingTitle() {
    $start = isset($this->displayStart) ? $this->displayStart : $this->start;
    $stop  = isset($this->displayStop)  ? $this->displayStop  : $this->stop;
    return 'Booking from '. $start->datetimestring
         .' - '. $stop->datetimestring;
  }

} //class Booking
