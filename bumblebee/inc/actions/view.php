<?php
# $Id$
# View the status of an instrument -- present the user with a calendar
# that can be used to make bookings.

  function actionView() {
    if (isset($_POST['zoom'])) {
      showZoom();
    } elseif (isset($_POST['booking'])) {
      showBooking();
    } elseif (isset($_POST['makebooking'])) {
      makeBooking();
    } else {
      showCalendar($_POST['instrument']);
    }
  }

  function keyExist ($keypattern) {
    foreach ($_POST as $key => $value) {
      if (preg_match($keypattern, $key)) {
        return $key;
      }
    }
  }

  function isodate($d) {
    return strftime("%Y-%m-%d", $d);
  }

  function isotime($d) {
    return strftime("%H:%M", $d);
  }

  function minutesBetween($timestop, $timestart) {
    return ($timestop - $timestart)/60;
  }

  function truncatedDuration($timestart, $timestop, $starttime, $finishtime) {
    $truncstop = min($timestop,$finishtime);
    $truncstart = max($timestart,$starttime);
    #echo "($timestart,$starttime,$truncstart)-($timestop,$finishtime,$truncstop)";
    $durmin = minutesBetween($truncstop, $truncstart);
    #echo "=$durmin.";
    return $durmin;
  }

  function displayInstrument ($instrument) {
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
  }

  function showCalendar ($instrument) {
    displayInstrument ($instrument);
    $startdate = calcuateStartDate($_POST['caloffset']);
    $stopdate  = calcuateStopDate ($startdate);
    print "<p>Calendar for period $startdate - $stopdate</p>";
    $bookings = selectBookings($instrument, $startdate, $stopdate);
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
    $startstring = isodate($start);
    return $startstring;
  }

  function calcuateStopDate($startdate) {
    $stop = dateAddDays($startdate, 6*7-1);
    $stopstring = isodate($stop);
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
        ."discount,log,comments,projectid,"
        ."users.name AS name, "
        ."users.username AS username, "
        ."users.email AS email, "
        ."masq.name AS masquser, "
        ."masq.username AS masqusername, "
        ."projects.name AS project "
        ."FROM bookings "
        ."LEFT JOIN users ON bookings.userid=users.id "
        ."LEFT JOIN users AS masq ON bookings.bookedby=masq.id "
        ."LEFT JOIN projects ON bookings.projectid=projects.id "
        ."WHERE bookings.instrument='$instrument' " 
        ."AND bookwhen BETWEEN '$starttime' AND '$stoptime' "
        ."ORDER BY bookwhen";
    echo "<div class='sql'>$q</div>";
    $sql = mysql_query($q);
    if (! $sql) die (mysql_error());
    $bookings = array();
    while ($g = mysql_fetch_array($sql)) {
      $date = isodate(strtotime($g['bookwhen']));
      $bookings[$date][] = $g;
      #echo $g['bookwhen'];
    }
    return $bookings;
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

  function generateCalendar($bookings, $starttime, $stoptime) {
    $dates = selectDates($starttime, $stoptime);
    $offset = $_POST['caloffset'];
    #foreach ($dates as $d) {
      #echo isodate($d) ."<br />\n";
    #}
    echo "<input type='hidden' name='caloffset' value='".$_POST['caloffset']."' />";
    echo "\n<table class='centrein'><tr><td>";
    echo "<button name='caloffset' type='submit' value='".($offset-27)."'>"
        ."&laquo; Earlier"
        ."</button>";
    echo "</td><td style='text-align: center;'>";
    echo "<button name='caloffset' type='submit' value='0'>"
        ."Today"
        ."</button>";
    echo "</td><td style='text-align: right;'>";
    echo "<button name='caloffset' type='submit' value='".($offset+27)."'>"
        ."Later &raquo;"
        ."</button>";
    echo "</td></tr></table>\n";
    ### Start of the actual calendar
    echo "<table class='calendar centrein'>";
    echo "<tr><th class='timecol'></th>";
    for ($i=0; $i<7; $i++) {
      echo "<th class='caldow'>";
      #echo date('l', $dates[$i]);
      echo date('D', $dates[$i]);
      echo "</th>";
    }
    #echo "</tr>"; #printed by first echo statement later
    $today = isodate(time());
    foreach ($dates as $d) {
      $isodate = isodate($d);
      $day = date("w", $d);
      if ($day == 1) {
        echo "</tr>\n<tr>";
        echo "<td class='timecol'><ul class='booktime'><li>&nbsp;</li>";
        $STARTTIME=8;
        $FINISHTIME=18;
        for ($i=$STARTTIME; $i<=$FINISHTIME-1; $i++) {
          echo sprintf("<li>%02d:00</li>", $i);
        }
        echo "</ul></td>";
      }
      $month = date("m", $d);
      echo "<td class='calday " . ($month%2 ? "moneven" : "monodd") 
                  . ($today==$isodate ? " caltoday" : "") . "'>";
      echo "<ul class='booktime'><li>";
      #echo "<div style='float:right;'><button name='zoom' type='submit' value='$isodate'>zoom</button></div>";
      echo "<div style='float:right;'><button name='zoom' type='submit' value='$isodate' title='Zoom in on date: $isodate'><img src='images/zoom.png' alt='Zoom in on $isodate' class='calicon' /></button></div>";
      echo "<div class='caldate'>" . strftime("%e", $d);
      echo "<span class='calmonth "
            .($month == $lastmonth ? "contmonth" : "startmonth") . "'> "
            . strftime("%B", $d) 
            ."</span>";
      echo "</div>";
      echo "</li>";
      $lastmonth=$month;
      #echo isodate($d) ."<br />\n";
      #echo "$STARTTIME,0,0,". date('m', $d). date('d', $d). date('Y', $d);
      $schedstart = mktime($STARTTIME,0,0, date('m', $d), date('d', $d), date('Y', $d));
      $schedfinish = mktime($FINISHTIME,0,0, date('m', $d), date('d', $d), date('Y', $d));
      $alltimes = timeSchedule($bookings, $schedstart, $schedfinish);

      foreach ($alltimes as $t) {
        # height of the cell is the duration in hours * 1.2em,
        #  less 0.2em for the borders top and bottom.
        $height = $t['truncdurmin']/60*1.2 - 0.2;
        $entry = "<div class='calbookperson'>"
                ."<a href='mailto:".$t['rec']['email']."'>"
                .$t['rec']['name']."</a></div>";
        showBookingsGraphical($t, $height, $entry);
        #showBookingsListing($t['rec']);
      }
      echo "</ul></td>\n";
    }
    echo "</table>";
  }

  function showBookingsListing($g) {
    echo "<li>";
    echo "<span class='booking'>";
    $timestart = strftime("%H:%M", strtotime($g['bookwhen']));
    $timestop  = strftime("%H:%M", strtotime($g['stoptime']));
    #echo "$starttime - $stoptime: " .$g['name']
    echo "$timestart - $timestop: ";
    echo $g['name']
        ." (" .$g['bookid'] .")";
    echo "</span>\n";
    echo "</li>\n";
  }
  
  function showBookingsGraphical($t, $height, $entry) {
    $free = isset($t['free']);
    $startticks = $t['start'];
    $stopticks = $t['stop'];
    $start = isotime($startticks);
    $stop  = isotime($stopticks);
    #echo "($start, $stop)";
    $type = $free ? "freetime" : "bookedtime";
    #echo $t['hourstop']." ".$t['hourstart'];
    echo "<li class='$type' style='height:".$height."em' title="
         ."'".($free ? "Free time " : "Booking ") . "$start - $stop'>";
    if ($free) {
      #echo "<div style='float:right;'><button name='booking' type='submit' value='$start'>book</button></div>";
      echo "<div style='float:right;'><button name='makebooking' type='submit' value='$startticks-$stopticks' title='Make booking'><img src='images/book.png' alt='Make booking' class='calicon' /></button></div>&nbsp;";
    } else {
      $booking = $t['rec']['bookid'];
      echo "<div style='float:right;'><button name='booking' type='submit' value='$booking' title='View or edit booking'><img src='images/editbooking.png' alt='View/edit booking' class='calicon' /></button></div>";
      echo $entry;
    }
    echo "</li>";
  }
  
  function timeSchedule($bookings, $starttime, $finishtime) {
    $isodate = isodate($starttime);
    #echo "$starttime=$isodate, $finishtime";
    $times = array();
    if (isset($bookings[$isodate])) {
      foreach ($bookings[$isodate] as $g) {
        $timestart = strtotime($g['bookwhen']);
        $timestop  = strtotime($g['stoptime']);
        #echo "f=($timestart,$timestop)";
        $truncdurmin = truncatedDuration($timestart, $timestop, $starttime, $finishtime);
        $times[] = array('start'=>$timestart, 'stop'=>$timestop, 'rec'=>$g, 'truncdurmin'=>$truncdurmin);
      }
    }
    /*
    for ($i = 0; $i<count($times); $i++) {
      print $i ." ". $times[$i]['start']."\n";
    }
    */
    $i = 0;
    $timeptr = $starttime;
    $alltimes = array();
    while ($timeptr < $finishtime) {
      #echo "($starttime, $timeptr, $finishtime)<br />";
      if (isset($times[$i]['start']) && 
                $times[$i]['start'] <= $timeptr) {
        $alltimes[] = $times[$i];
        $timeptr = $times[$i]['stop'];
        $i++;
      } else {
        $timestart = $timeptr;
        $timeptr = isset($times[$i]['start']) ? $times[$i]['start']
                                             : $finishtime ;
        $truncdurmin = truncatedDuration($timestart, $timeptr, $starttime, $finishtime);
        #$durmin = minutesBetween($timeptr, $timestart);
        $alltimes[] = array('start'=>$timestart, 'stop'=>$timeptr,'free'=>1,'truncdurmin'=>$truncdurmin);
      }
    }
    return $alltimes;
  }

  function showZoom() {
    global $ISADMIN;

    $instrument = $_POST['instrument'];
    displayInstrument ($instrument);
    $startdate = $_POST['zoom'];
    $stopdate  = isodate(dateAddDays($startdate, 1));
    $d = strtotime($startdate);
    $bookings = selectBookings($instrument, $startdate, $stopdate);
    $schedstart = mktime(0,0,0, date('m', $d), date('d', $d), date('Y', $d));
    $schedfinish = mktime(0,-1,0, date('m', $d), date('d', $d)+1, date('Y', $d));
    #echo "($schedstart, $schedfinish) ";
    $alltimes = timeSchedule($bookings, $schedstart, $schedfinish);

    print "<p>Calendar for $startdate</p>";
    echo "<div class='centrein'><button name='zoomout' value='1'>Back to month view</button></div>";
    echo "<input type='hidden' name='caloffset' value='".$_POST['caloffset']."' />";
    echo "<table class='calendarzoom centrein'>";
    echo "<tr><td class='timecolzoom'><ul class='booktimezoom'>";
    for ($hour=0; $hour<24; $hour++) {
      echo sprintf("<li>%02d:00</li>", $hour);
      echo sprintf("<li>%02d:30</li>", $hour);
    }
    echo "</ul></td>\n";
    echo "<td class='caldayzoom'><ul class='booktimezoom'>";

    foreach ($alltimes as $t) {
      # height of the cell is the duration in hours * 4.8em,
      #  less 0.2em for the borders top and bottom.
      $height = $t['truncdurmin']/60*4.8 - 0.2;
      $entry = "<div class='calbookperson''>"
              ."<a href='mailto:".$t['rec']['email']."'>"
              .$t['rec']['name']
              ."</a>"
              ." (username: ". $t['rec']['username'] .") "
              ."from ". isotime($t['start']) .' until '. isotime($t['stop'])
              .".</div>";
      if (isset($t['rec']['masquser'])) {
        $entry .= "<div class='masquerade'>"
                 ."Booked made by: " . $t['rec']['masquser'] 
                 ." (username: ". $t['rec']['masqusername'] .") "
                 ."</div>";
      }
      $entry .= "<div class='project'>"
               ."Project: " . $t['rec']['project'] 
               ."</div>";
      if ($ISADMIN && isset($t['rec']['discount']) && $t['rec']['discount']) {
        $entry .= "<div class='discount'>"
                 ."Discount: " . $t['rec']['discount'] . "%"
                 ."</div>";
      }
      if (isset($t['rec']['comments']) && $t['rec']['comments'] != '') {
        $entry .= "<div class='comments'>"
                 ."Comments: " . $t['rec']['comments']
                 ."</div>";
      }
      if (isset($t['rec']['log']) && $t['rec']['log'] != '') {
        $entry .= "<div class='log'>"
                 ."Log entry: " . $t['rec']['log']
                 ."</div>";
      }
      showBookingsGraphical($t, $height, $entry);
      #showBookingsListing($t['rec']);
      echo "\n";
    }
    echo "</ul>\n";
    echo "</td></table>";
    
    echo "<input type='hidden' name='action' value='view' />";
    echo "<input type='hidden' name='instrument' value='$instrument' />";
    
  }

  function showBooking() {
    global $UID, $MASQUID, $MASQUSER, $ISADMIN;
    #displayPost();
    $bookid = $_POST['booking'];
    $q = "SELECT bookings.id AS bookid,bookwhen,duration,"
        ."DATE_ADD(bookwhen, INTERVAL duration HOUR_SECOND) AS stoptime,"
        ."ishalfday,isfullday,"
        ."discount,log,comments,ip,instrument,projectid,"
        ."users.id AS userid, "
        ."users.name AS name, "
        ."users.username AS username, "
        ."users.email, "
        ."masq.name AS masquser, "
        ."masq.username AS masqusername, "
        ."projects.name AS project "
        ."FROM bookings "
        ."LEFT JOIN users ON bookings.userid=users.id "
        ."LEFT JOIN users AS masq ON bookings.bookedby=masq.id "
        ."LEFT JOIN projects ON bookings.projectid=projects.id "
        ."WHERE bookings.id='$bookid'";
    echo "<div class='sql'>$q</div>";
    $sql = mysql_query($q);
    if (! $sql) die (mysql_error());
    $g = mysql_fetch_array($sql);
    $ticks          = strtotime($g['bookwhen']);
    $g['ticks']     = $ticks;
    $g['starttime'] = isotime($ticks);
    $g['stopticks'] = $g['stoptime'];
    $g['stoptime']  = isotime(strtotime($g['stoptime']));
    $g['date']      = isodate($ticks);
    $g['bookid']    = $bookid;
    #echo "<pre>";
    #print_r($g);
    #echo "</pre>";
    if ($ISADMIN || $g['userid']==$UID) {
      editBooking($g);
    } else {
      displayBooking($g);
    }
  }

  function makeBooking() {
    global $UID, $MASQUID, $MASQUSER;
    #displayPost();
    $g = array();
    preg_match("/(.+)-(.+)/", $_POST['makebooking'], $times);
    #$ticks = $_POST['makebooking'];
    $g['ticks']     = $times[1];
    $g['starttime'] = isotime($times[1]);
    $g['stoptime']  = isotime($times[2]);
    $g['date']      = isodate($times[1]);
    $g['instrument']= $_POST['instrument'];
    $g['bookid']    = -1;
    if (isset($MASQUID)) {
      $g['userid']  = $MASQUID;
      $g['bookedby']= $UID;
    } else {
      $g['userid']  = $UID;
    }
    editBooking($g);
  }

  function displayBooking($g) {
    global $UID, $MASQUID, $MASQUSER, $ISADMIN;
    echo "<input type='hidden' name='caloffset' value='".$_POST['caloffset']."' />";
    echo "<h2>Booking details</h2>";
    displayInstrument($g['instrument']);
    echo "<table>";
    echo "<tr><td>Date</td>"
        ."<td>".$g['date']."</td></tr>";
    echo "<tr><td>Start time</td>"
        ."<td>".$g['starttime']."</td></tr>";
    echo "<tr><td>Finish time</td>"
        ."<td>".$g['stoptime']."</td></tr>";
    echo "<tr><td>Duration</td>"
        ."<td>".$g['duration']."</td></tr>";
    echo "<tr><td>Booking for </td>"
        ."<td>".$g['name']." (".$g['username'].")</td></tr>";
    if (isset($g['bookedby'])) {
      echo "<tr><td>Booking made by </td>"
        ."<td>".$g['masqname']." (".$g['masqusername'].")</td></tr>";
    }
    echo "<tr><td>Project</td>"
        ."<td>".$g['project']."</td></tr>";
    echo "<tr><td>Comments</td>"
        ."<td>".$g['comments']."</td></tr>";
    echo "<tr><td>Log entry</td>"
        ."<td>".$g['log']."</td></tr>";
    echo "</table>";
  }

  function editBooking($g) {
    global $UID, $MASQUID, $MASQUSER, $ISADMIN;
    $past = ($g['ticks'] < time());
    $edit = ($ISADMIN || ! $past) || ($g['bookid'] < 0);
    #echo "<pre>";
    #print_r($g);
    #echo "</pre>";
    if ($past) {
      echo "<p>Log entry for past instrument use:</p>";
    } else {
      echo "<p>Booking for instrument use:</p>";
    }
    echo "<input type='hidden' name='caloffset' value='".$_POST['caloffset']."' />";
    echo "<input type='hidden' name='action' value='book' />";
    echo "<input type='hidden' name='booking' value='".$g['bookid']."' />";
    echo "<input type='hidden' name='instrument' value='".$g['instrument']."' />";
    echo "<table>";
    echo "<tr><th colspan='2'>Booking details</th></tr>";
    echo "<tr><td>Date</td>"
        ."<td>".$g['date']
        ."<input type='hidden' name='date' value='".$g['date']."' />"
        ."</td></tr>";
    echo "<tr><td>Start time</td>"
        ."<td>"
        .($edit ? "<input type='text' name='starttime' value='".$g['starttime']."' size='8' maxlength='5'>"
                : $g['starttime'])
        ."</td></tr>";
    echo "<tr><td>Finish time</td>"
        ."<td>"
        .($edit ? "<input type='text' name='stoptime' value='".$g['stoptime']."' size='8' maxlength='5'>"
               : $g['stoptime'])
        ."</td></tr>";
    if (isset($MASQUID) && $MASQUID) {
      echo "<tr><td>Booking for </td>"
          #."<td>".$MASQUSER[1]." (".$MASQUSER[0].")"
          #."<input type='hidden' name='userid' value='$MASQUID' />"
          ."<td>";
      userselectbox("userid","",$g['userid']);
      echo "<input type='hidden' name='bookedby' value='$UID' />"
          ."</td></tr>";
      $userid = $MASQUID;
    } elseif (isset($g['userid'])) {
      $userid = $g['userid'];
      echo "<input type='hidden' name='userid' value='$userid' />";
    } else {
      $userid = $UID;
      echo "<input type='hidden' name='userid' value='$UID' />";
    }

    $q = "SELECT projectid,isdefault,name,longname "
        ."FROM userprojects "
        ."LEFT JOIN projects ON projectid=id "
        ."WHERE userid='$userid'";
    $sql = mysql_query($q);
    if (! $sql) die (mysql_error());
    echo "<tr><td>Project</td><td><select name='project'>";
    while ($p = mysql_fetch_array($sql)) {
      if ((! isset($g['projectid'])) && $p['isdefault']) {
        $g['projectid'] = $p['projectid'];
      }
      #echo "(".$p['projectid'] .",". $g['projectid'].")";
      $s = ($p['projectid']==$g['projectid']) ? " selected='1'" : "";
      echo "<option value='".$p['projectid']."'$s>".$p['name']."</option>";
    }
    echo "</select></td></tr>";
    echo "<tr><td>Comments</td>"
        ."<td>"
        .($edit ? "<input type='text' name='comments' value='".$g['comments']."' size='24' maxlength='31'>"
                : $g['comments'])
        ."</td></tr>";
    echo "<tr><td>Log entry</td>"
        ."<td><textarea name='log' rows='5' cols='40'>".$g['log']."</textarea></td></tr>";
    
    if ($ISADMIN) {
      echo "<tr><td>Booking IP address</td>"
          ."<td>".$g['ip']."</td></tr>";
      echo "<tr><td>Discount</td>"
          ."<td><input type='text' name='discount' value='".$g['discount']."' /></td></tr>";
      echo "<tr><td>Is half-day</td>"
          ."<td><input type='checkbox' name='ishalfday' value='1'"
          .($g['ishalfday']?"checked='1'":"")."' /></td></tr>";
      echo "<tr><td>Is full-day</td>"
          ."<td><input type='checkbox' name='isfullday' value='1'"
          .($g['isfullday']?"checked='1'":"")."' /></td></tr>";
    }
    echo "<tr>"
        ."<td><input type='submit' name='deletebooking' value='Delete booking'></td>"
        ."<td><input type='submit' name='change' value='Edit/create booking'></td></tr>";
    echo "</table>";
  }

?> 
