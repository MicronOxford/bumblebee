<?php
# $Id$
# Booking object

include_once 'date.php';

class Booking {
  var $id,
      $start,
      $stop,
      $duration,
      $ishalfday,
      $isfullday,
      $discount,
      $log,
      $comments,
      $project,
      $username,
      $useremail;
  
  function Booking($arr) {
    echo "FIXME: DEAD CODE IS NOT DEAD. LONG LIVE THE CODE!";
    $this->id = $arr['bookid'];
    $this->start = new SimpleDate($arr['bookwhen'],1);
    $this->stop = new SimpleDate($arr['stoptime'],1);
    $this->duration = $arr['duration'];
    $this->ishalfday = $arr['ishalfday'];
    $this->isfullday = $arr['isfullday'];
    $this->discount = $arr['discount'];
    $this->log = $arr['log'];
    $this->comments = $arr['comments'];
    $this->project = $arr['project'];
    $this->username = $arr['username'];
    $this->useremail = $arr['email'];
    echo "Booking from ".$this->start->datetimestring." to ".$this->stop->datetimestring."<br />\n";
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

} //class Booking
