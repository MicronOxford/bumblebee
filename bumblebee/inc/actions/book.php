<?php
# $Id$
# Book an instrument

  function actionBook() {
    if ($_POST['booking']==-1) {
      createBooking();
    } elseif ($_POST['deletebooking']) {
      deleteBooking();
    } else {
      updateBooking();
    }
  }

  function createBooking() {
    $qi = "INSERT INTO";
    $qf = "";
    bookingSQL($qi, $qf);
  }
    
  function updateBooking() {
    $qi = "UPDATE";
    $qf = " WHERE id='".$_POST['booking']."'";
    bookingSQL($qi, $qf);
  }
    
  function bookingSQL($qi, $qf) {
    #displayPost();
    $startdate = $_POST['date'];
    $bookwhen = $_POST['date'] ." ". $_POST['starttime'];
    $bookstop = $_POST['date'] ." ". $_POST['stoptime'];
    $instrument = $_POST['instrument'];
    $stopdate  = isodate(dateAddDays($startdate, 1));
    $bookings = selectBookings($instrument, $startdate, $stopdate);
    if (!isFree($bookings, $_POST['booking'], $bookwhen, $bookstop)) {
      echo "<h2>Sorry!</h2><p>The instrument is not free for those times. Please try again.</p>";
    } else {
      $durmin   = minutesBetween(strtotime($bookstop),strtotime($bookwhen));
      $duration = sprintf("%02d:%02d", $durmin/60, $durmin%60);
      if (isset($_POST['isfullday']) || isset($_POST['ishalfday'])) {
        $isfullday = $_POST['isfullday'];
        $ishalfday = $_POST['ishalfday'];
      } else {
        $isfullday = ($durmin > 360);
        $ishalfday = ($durmin > 180) && ! $isfullday;
      }
      $q = "$qi bookings "
          ."SET "
          ."bookwhen='$bookwhen', "
          ."duration='$duration', "
          ."ishalfday='$ishalfday', "
          ."isfullday='$isfullday', "
          ."instrument='$instrument', "
          ."bookedby='".$_POST['bookedby']."', "
          ."userid='".$_POST['userid']."', "
          ."projectid='".$_POST['project']."', "
          ."discount='".$_POST['discount']."', "
          ."ip='".$_SERVER['REMOTE_ADDR']."', "
          ."comments='".$_POST['comments']."', "
          ."log='".$_POST['log']."'"
          .$qf;
      echoSQL($q);
      $sql = mysql_query($q);
      if (! $sql) die (mysql_error());
      echo "<h2>Booking made</h2>";
      $_POST['isodate']   = $_POST['date'];
      actionRestart("view");
    }
  }

  function isFree($bookings, $exclude, $teststart, $teststop) {
    $starttime = strtotime($teststart);
    $stoptime  = strtotime($teststop);
    $date = isodate($starttime);
    $b = $bookings[$date];
    #echo "($teststart=$starttime),($teststop=$stoptime)<br />";
    for ($i=0; $i<count($b); $i++) {
      if ($b[$i]['bookid'] != $exclude) {
        $bookstart = strtotime($b[$i]['bookwhen']);
        $bookstop  = strtotime($b[$i]['stoptime']);
        #echo "($bookstart,$bookstop)/($starttime,$stoptime) ";
        if ( (   $bookstart >= $starttime
              && $bookstart <  $stoptime )
          || (   $bookstop  >  $starttime
              && $bookstop  <= $stoptime ) ) {
          #then the booking $b[$i] starts or stops within the test booking
          #echo "failed 1";
          return false;
        }
        if ( (   $starttime >= $bookstart 
              && $starttime <  $bookstop )
          || (   $stoptime  >  $bookstart 
              && $stoptime  <=  $bookstop ) ) {
          #then the test booking starts or stops within $b[$i]
          #echo "failed 2";
          return false;
        }
      }
    }
    return true;
  }

  function deleteBooking() {
    $q = "DELETE FROM bookings WHERE id='".$_POST['booking']."'";
    echoSQL("<div class='sql'>$q</div>");
    $sql = mysql_query($q);
    if (! $sql) die (mysql_error());
    echo "<h2>Booking deleted</h2>";
    $_POST['isodate']   = $_POST['date'];
    actionRestart("view");
  }
?>
