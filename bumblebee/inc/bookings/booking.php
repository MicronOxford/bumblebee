<?php
# $Id$
# Booking object

include_once 'inc/dbforms/date.php';
include_once 'timeslot.php';

class Booking extends TimeSlot {
  var $id,
      $ishalfday,
      $isfullday,
      $discount,
      $log,
      $comments,
      $project,
      $username,
      $name,
      $useremail;
  
  function Booking($arr) {
    $this->TimeSlot($arr['bookwhen'], $arr['stoptime'], $arr['duration']);
    $this->id = $arr['bookid'];
    $this->ishalfday = $arr['ishalfday'];
    $this->isfullday = $arr['isfullday'];
    $this->discount = $arr['discount'];
    $this->log = $arr['log'];
    $this->comments = $arr['comments'];
    $this->project = $arr['project'];
    $this->username = $arr['username'];
    $this->name = $arr['name'];
    $this->useremail = $arr['email'];
    echo "Booking from ".$this->start->datetimestring." to ".$this->stop->datetimestring."<br />\n";
    $this->baseclass='booking';
  }

  function display() {
    return $this->displayInTable();
  }

  function displayInTable() {
    return '<tr><td>'.$this->id
            .'</td><td>'.$this->start->datetimestring
            .'</td><td>'.$this->stop->datetimestring
            .'</td><td>'.$this->username
            .'</td></tr>'."\n";
  }

  function displayCellDetails() {
    global $BASEPATH;
    $t = '';
    $t .= "<div style='float:right;'><a href='$this->href/booking/$this->id' title='View or edit booking' class='but'><img src='$BASEPATH/theme/images/editbooking.png' alt='View/edit booking' class='calicon' /></a></div>";
    $t .= '<div class="calbookperson">'
         .'<a href="mailto:'.$this->useremail.'">'
         .$this->name.'</a></div>';
    return $t;
  }

  function generateBookingTitle() {
    return 'Booking from '. $this->start->datetimestring
         .' - '. $this->stop->datetimestring;
  }

} //class Booking
