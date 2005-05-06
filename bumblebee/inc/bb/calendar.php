<?php
# $Id$
# Calendar object -- holds a collection of booking objects

include_once 'dbforms/date.php';
include_once 'booking.php';
include_once 'bookingcell.php';
include_once 'bookingmatrix.php';

class Calendar {
  var $start,
      $stop,
      $instrument;
  var $fatal_sql = 1;
  var $bookinglist;
  
  function Calendar($start, $stop, $instrument) {
    $this->start = $start;
    $this->stop  = $stop;
    $this->instrument = $instrument;
    echo "Creating calendar from $start->datestring to $stop->datestring<br />\n";
    $this->fill();
  }

  function fill() {
    $q = 'SELECT bookings.id AS bookid,bookwhen,duration,'
        .'DATE_ADD(bookwhen, INTERVAL duration HOUR_SECOND) AS stoptime,'
        .'ishalfday,isfullday,'
        .'discount,log,comments,projectid,'
        .'users.name AS name, '
        .'users.username AS username, '
        .'users.email AS email, '
        .'masq.name AS masquser, '
        .'masq.username AS masqusername, '
        .'projects.name AS project '
        .'FROM bookings '
        .'LEFT JOIN users ON bookings.userid=users.id '
        .'LEFT JOIN users AS masq ON bookings.bookedby=masq.id '
        .'LEFT JOIN projects ON bookings.projectid=projects.id '
        .'WHERE bookings.instrument='.qw($this->instrument).' '
        .'AND bookwhen BETWEEN '.qw($this->start->datestring)
                        .' AND '.qw($this->stop->datestring).' '
        .'ORDER BY bookwhen';
    $this->bookinglist = array();
    $sql = db_get($q, $this->fatal_sql);
    //FIXME: mysql specific function
    while ($g = mysql_fetch_array($sql)) {
      $this->bookinglist[] = new Booking($g); 
    }
  }


  /**
   * Display the booking details in a table with rowspan based on
   * the 
  **/
  function displayMonthAsTable($daystart, $daystop, $granularity) {
    $matrix = new bookingMatrix($daystart, $daystop, $granularity, $this->bookinglist);
    $t = "<table class='tabularobject'>";
    foreach ($matrix->rows as $row) {
      foreach ($row as $cell) {
        $cell->display();
      }
    }
    $t .= "</table>";
    return $t;
  }

  function display() {
    return $this->displayAsTable();
  }

  function displayAsTable() {
    $t = "<table class='tabularobject'>";
    foreach ($this->bookinglist as $k => $v) {
      #$t .= '<tr><td>'.$v[0].'</td><td>'.$v[1].'</td></tr>'."\n";
      $t .= $v->display();
    }
    $t .= "</table>";
    return $t;
  }

} //class Calendar
