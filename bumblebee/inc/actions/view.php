<?php
# $Id$
# View the status of an instrument -- present the user with a calendar
# that can be used to make bookings.

  function actionView() {
    showCalendar($_POST['instrument']);
    /*
    if (! isset($_POST['user'])) {
      selectuser('users', 'Create new user', 'Edit/create user');
    } elseif (! isset($_POST['updateuser'])) {
      edituser($_POST['user']);
    } elseif ($_POST['delete'] == 1) {
      deleteuser($_POST['user']);
    } elseif ($_POST['user'] == -1) {
      insertuser();
    } else {
      updateuser($_POST['updateuser']);
    }
    */
  }

  function showCalendar ($instrument) {
    $q = "SELECT id,name,longname,location "
        ."FROM instruments "
        ."WHERE id='$instrument'";
    $sql = mysql_query($q);
    if (! $sql) die (mysql_error());
    $g = mysql_fetch_array($sql);

    #<h1>View Instrument</h1>
    echo "<h2>".$g['name']."</h2>"
        ."<p>Instrument: ".$g['longname']
        .($g['location'] != "" ?  " in ".$g['location'] : "")
        ."</p>";

    $startdate = calcuateStartDate($_POST['caloffset']);
    $stopdate  = calcuateStopDate ($startdate);
    print "<p>Calendar for period $startdate - $stopdate</p>";
    $bookings = selectBookings($instrument, $startdate, $stopdate);
    $offset = $_POST['caloffset'];
    generateCalendar($bookings, $startdate, $stopdate);
    echo "<input type='hidden' name='action' value='view' />";
    echo "<input type='hidden' name='instrument' value='$instrument' />";
  }

  function calcuateStartDate($offset) {
    #Show a window 6 weeks long starting 2 weeks before the current date
    #Displayed week starts on Monday
    $offset -= 8;
    #$offset = (isset($offset) && $offset != "") ? $offset : -8;
    $start = dateAddDays($today, $offset);
    #$start = mktime(0,0,0, date('m'), date('d')+$offset, date('Y'));
    $day = date("w", $start); #the day of the week, 0=Sun, 6=Sat
    $start = dateAddDays($today, $offset+1-$day);
    #$start = mktime(0,0,0, date('m'), date('d')+$offset-$day+1, date('Y'));
    $startstring = strftime("%Y-%m-%d", $start);
    return $startstring;
  }

  function calcuateStopDate($startdate) {
    $stop = dateAddDays($startdate, 6*7-1);
    $stopstring = strftime("%Y-%m-%d", $stop);
    return $stopstring;
  }

  function dateAddDays($startdate, $days) {
    $start = strtotime($startdate);
    $stop  = mktime(0,0,0, date('m',$start), date('d',$start)+$days, date('Y',$start));
    return $stop;
  }

  function selectBookings($instrument, $starttime, $stoptime) {
    $q = "SELECT bookings.id AS bookid,bookwhen,duration,"
        ."DATE_ADD(bookwhen, INTERVAL duration HOUR_SECOND) AS stoptime,"
        ."ishalfday,isfullday,"
        ."users.name AS name "
        ."FROM bookings "
        ."LEFT JOIN users ON bookings.userid=users.id "
        ."WHERE bookings.instrument='$instrument' " 
        ."AND bookwhen BETWEEN '$starttime' AND '$stoptime' "
        ."ORDER BY bookwhen";
    $sql = mysql_query($q);
    if (! $sql) die (mysql_error());
    $bookings = array();
    while ($g = mysql_fetch_array($sql)) {
      #array_push($bookings, $g);
      $date = strftime("%Y-%m-%d", strtotime($g['bookwhen']));
      $bookings[$date][] = $g;
    }
    return $bookings;
  }

  function generateCalendar($bookings, $starttime, $stoptime) {
    $dates = selectDates($starttime, $stoptime);
    #foreach ($dates as $d) {
      #echo strftime("%Y-%m-%d", $d) ."<br />\n";
    #}
    echo "\n<table class='centrein'><tr><td>";
    echo "<button name='caloffset' type='submit' value='".($offset-27)."'>"
        ."&laquo; Earlier"
        ."</button>";
    echo "</td><td style='text-align: right;'>";
    echo "<button name='caloffset' type='submit' value='".($offset+27)."'>"
        ."Later &raquo;"
        ."</button>";
    echo "</td></tr></table>\n";
    echo "<table class='calendar centrein'>";
    echo "<tr>";
    for ($i=0; $i<7; $i++) {
      echo "<th class='caldow'>";
      #echo date('l', $dates[$i]);
      echo date('D', $dates[$i]);
      echo "</th>";
    }
    #echo "</tr>"; #printed by first echo statement later
    foreach ($dates as $d) {
      $isodate = strftime("%Y-%m-%d", $d);
      $day = date("w", $d);
      if ($day == 1) {
        echo "</tr>\n<tr>";
      }
      $month = date("m", $d);
      echo "<td class='calday " . ($month%2 ? "moneven" : "monodd") . "'>";
      echo "<div class='calday'>" . strftime("%e", $d);
      echo "<div class='calmonth "
            .($month == $lastmonth ? "contmonth" : "startmonth") . "'> "
            . strftime("%B", $d) 
            ."</div>";
      echo "</div>";
      $lastmonth=$month;
      #echo strftime("%Y-%m-%d", $d) ."<br />\n";
      if (isset($bookings[$isodate])) {
        echo "<div class='bookings'>\n";
        foreach ($bookings[$isodate] as $g) {
          echo "<div class='booking'>";
          $starttime = strftime("%H:%M", strtotime($g['bookwhen']));
          $stoptime  = strftime("%H:%M", strtotime($g['stoptime']));
          echo "$starttime - $stoptime: " .$g['name']
              ." (" .$g['bookid'] .")";
           echo "</div>\n";
         }
         echo "</div>\n";
      }
      echo "</td>\n";
    }
    echo "</table>";
  }
  
  function selectDates($starttime, $stoptime) {
    $stop = strtotime($stoptime);
    $c = strtotime($starttime);
    $datelist = array();
    while ($c <= $stop) {
      $datelist[] = $c;
      $cy = date("y", $c);
      $cm = date("m", $c);
      $cd = date("d", $c);
      $c = mktime(0,0,0, $cm, $cd+1, $cy);
    }
    return $datelist;
  }

?> 
