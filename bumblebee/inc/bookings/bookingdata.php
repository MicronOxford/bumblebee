<?php
# $Id$
# Booking data

include_once 'booking.php';

class BookingData {
  var $start;
  var $stop;
  var $instrument;
  var $id;
  var $bookinglist;
  var $booking;
  var $fatal_sql = 1;
  
  function BookingData($arr) {
    $this->start = issetSet($arr,'start');
    $this->stop  = issetSet($arr,'stop');
    $this->instrument  = issetSet($arr,'instrument');
    $this->id  = issetSet($arr,'id');
    $this->_fill();
  }

  function _fill() {
    global $TABLEPREFIX;
    $q = 'SELECT bookings.id AS bookid,bookwhen,duration,'
        .'DATE_ADD(bookwhen, INTERVAL duration HOUR_SECOND) AS stoptime,'
        /*.'ishalfday,isfullday,'*/
        .'discount,log,comments,projectid,'
        .'userid,'
        .'users.name AS name, '
        .'users.username AS username, '
        .'users.email AS email, '
        .'bookedby AS masquserid, '
        .'masq.name AS masquser, '
        .'masq.username AS masqusername, '
        .'masq.email AS masqemail, '
        .'projects.name AS project '
        .'FROM '.$TABLEPREFIX.'bookings AS bookings '
        .'LEFT JOIN '.$TABLEPREFIX.'users AS users ON '
            .'bookings.userid=users.id '
        .'LEFT JOIN '.$TABLEPREFIX.'users AS masq ON '
            .'bookings.bookedby=masq.id '
        .'LEFT JOIN '.$TABLEPREFIX.'projects AS projects ON '
            .'bookings.projectid=projects.id '
        .'WHERE bookings.deleted<>1 ';
    if ($this->id) {
      $q .= 'AND bookings.id='.qw($this->id);
    } else {
      $q .= 'AND bookings.userid<>0 AND bookings.instrument='.qw($this->instrument).' '
           .'AND bookwhen BETWEEN '.qw($this->start)
                            .' AND '.qw($this->stop).' '
           .'ORDER BY bookwhen';
    }
    
    if ($this->id) {
      $g = db_get_single($q, $this->fatal_sql);
      $this->booking = new Booking($g); 
    } else {
      $this->bookinglist = array();
      $sql = db_get($q, $this->fatal_sql);
      //FIXME: mysql specific function
      while ($g = mysql_fetch_array($sql)) {
        $this->bookinglist[] = new Booking($g); 
      }
    }
  }

  function dataArray() {
    return $this->bookinglist;
  }

  function dataEntry() {
    return $this->booking;
  }

} //class BookingData
