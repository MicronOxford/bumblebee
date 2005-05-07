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
    $q = 'SELECT '.$TABLEPREFIX.'bookings.id AS bookid,bookwhen,duration,'
        .'DATE_ADD(bookwhen, INTERVAL duration HOUR_SECOND) AS stoptime,'
        /*.'ishalfday,isfullday,'*/
        .'discount,log,comments,projectid,'
        .'userid,'
        .$TABLEPREFIX.'users.name AS name, '
        .$TABLEPREFIX.'users.username AS username, '
        .$TABLEPREFIX.'users.email AS email, '
        .'bookedby AS masquserid, '
        .'masq.name AS masquser, '
        .'masq.username AS masqusername, '
        .'masq.email AS masqemail, '
        .$TABLEPREFIX.'projects.name AS project '
        .'FROM '.$TABLEPREFIX.'bookings '
        .'LEFT JOIN '.$TABLEPREFIX.'users ON '
            .$TABLEPREFIX.'bookings.userid='.$TABLEPREFIX.'users.id '
        .'LEFT JOIN '.$TABLEPREFIX.'users AS masq ON '
            .$TABLEPREFIX.'bookings.bookedby='.$TABLEPREFIX.'masq.id '
        .'LEFT JOIN '.$TABLEPREFIX.'projects ON '
            .$TABLEPREFIX.'bookings.projectid='.$TABLEPREFIX.'projects.id '
        .'WHERE '.$TABLEPREFIX.'bookings.deleted<>1 ';
    if ($this->id) {
      $q .= 'AND '.$TABLEPREFIX.'bookings.id='.qw($this->id);
    } else {
      $q .= 'AND '.$TABLEPREFIX.'bookings.userid<>0 AND '.$TABLEPREFIX.'bookings.instrument='.qw($this->instrument).' '
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
