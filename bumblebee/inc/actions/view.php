<?php
# $Id$
# View the status of an instrument -- present the user with a calendar
# that can be used to make bookings.

  function actionView($auth) {
    $PD = viewMungePath();
    if (! isset($PD['instrument'])
       || $PD['instrument'] < 1 
       || $PD['instrument'] =="" ) {
      viewSelectInstrument($auth, $PD);
    } elseif (isset($PD['bookid'])) {
      showBooking($auth, $PD);
    } elseif (isset($PD['startticks'])) {
      makeBooking($auth, $PD);
    } elseif (isset($PD['isodate'])) {
      showZoom($PD);
    } else {
      showCalendar($PD);
    }
  }

  function viewMungePath() {
    global $PDATA;
    $PD = array();
    foreach ($_POST as $k => $v) {
      $PD[$k] = $v;
    }
    if (isset($PDATA[1])) {
      $PD['instrument'] = $PDATA[1];
    }
    #if (isset($PDATA[2])) {
    for ($i=2; isset($PDATA[$i]); $i++) {
      if (preg_match("/o=(-?\d+)/", $PDATA[$i], $m)) {
        $PD['caloffset'] = $m[1];
      } elseif (preg_match("/\d\d\d\d-\d\d-\d\d/", $PDATA[$i], $m)) {
        $PD['isodate'] = $PDATA[$i];
      } elseif (preg_match("/(\d+)-(\d+)/", $PDATA[$i], $m)) {
        $PD['startticks'] = $m[1];
        $PD['stopticks'] = $m[2];
      } elseif (preg_match("/(\d+)/", $PDATA[$i], $m)) {
        $PD['bookid'] = $m[1];
      } else {
        echo "I don't know what to do with that data!";
      }
    }
    #if (isset($PDATA[1])) $PD['instrument'] = $PDATA[1];
    #echo "<pre>";
    #echo $PD;
    #echo $PD['caloffset'];
    #echo "</pre>";
    return $PD;
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
    ?>
      <h2><?=$g['name']?></h2>
      <p>Instrument: <?=$g['longname']?>
        <?=($g['location'] != "" ?  " in ".$g['location'] : "")?>
      </p>
    <?
  }

  function showCalendar ($PD) {
    $instrument = $PD['instrument'];
    displayInstrument ($instrument);
    $startdate = calcuateStartDate($PD['caloffset']);
    $stopdate  = calcuateStopDate ($startdate);
    ?>
      <p>Calendar for period <?=$startdate?> - <?=$stopdate?></p>
      <input type='hidden' name='action' value='view' />
      <input type='hidden' name='instrument' value='<?=$instrument?>' />
    <?
    $bookings = selectBookings($instrument, $startdate, $stopdate);
    generateCalendar($PD, $bookings, $startdate, $stopdate);
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
    echoSQL($q);
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

  function generateCalendar($PD, $bookings, $starttime, $stoptime) {
    global $BASEURL, $BASEPATH;
    $instrument = $PD['instrument'];
    $href="$BASEURL/view/$instrument";
    $dates = selectDates($starttime, $stoptime);
    $offset = $PD['caloffset'];
    ?>
      <table class='centrein'><tr><td>
      <a class='but' href='<?=$href."/o=".($offset-27)?>'>&laquo; Earlier</a>
      </td><td style='text-align: center;'>
      <a class='but' href='<?=$href?>/o=0'>Today</a>
      </td><td style='text-align: right;'>
      <a class='but' href='<?=$href."/o=".($offset+27)?>'>Later &raquo;</a>
      </td></tr></table>
    <?
    ### Start of the actual calendar
    ?>
      <table class='calendar centrein'>
      <tr><th class='timecol'></th>
    <?
    for ($i=0; $i<7; $i++) {
      ?>
        <th class='caldow'><?=date('D', $dates[$i])?></th>
      <?
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
      echo "<div style='float:right;'><a href='$href/$isodate' class='but' title='Zoom in on date: $isodate'><img src='$BASEPATH/theme/images/zoom.png' alt='Zoom in on $isodate' class='calicon' /></a></div>";
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
        $href="$BASEURL/view/$instrument";
        showBookingsGraphical($href, $t, $height, $entry);
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
  
  function showBookingsGraphical($href, $t, $height, $entry) {
    global $BASEPATH;
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
    $isodate = $t['isodate'];
      echo "<div style='float:right;'><a href='$href/$startticks-$stopticks' class='but' title='Make booking'><img src='$BASEPATH/theme/images/book.png' alt='Make booking' class='calicon' /></a></div>&nbsp;";
    } else {
      $booking = $t['rec']['bookid'];
      echo "<div style='float:right;'><a href='$href/$booking' title='View or edit booking' class='but'><img src='$BASEPATH/theme/images/editbooking.png' alt='View/edit booking' class='calicon' /></a></div>";
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
        $times[] = array('start'=>$timestart, 'stop'=>$timestop, 'rec'=>$g, 'truncdurmin'=>$truncdurmin,'isodate'=>$isodate);
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
        $alltimes[] = array('start'=>$timestart, 'stop'=>$timeptr,'free'=>1,'truncdurmin'=>$truncdurmin,'isodate'=>$isodate);
      }
    }
    return $alltimes;
  }

  function showZoom($PD) {
    global $BASEURL, $BASEPATH;
    $instrument = $PD['instrument'];
    $href="$BASEURL/view/$instrument";

    $instrument = $PD['instrument'];
    displayInstrument ($instrument);
    $startdate = $PD['isodate'];
    $stopdate  = isodate(dateAddDays($startdate, 1));
    $d = strtotime($startdate);
    $bookings = selectBookings($instrument, $startdate, $stopdate);
    $schedstart = mktime(0,0,0, date('m', $d), date('d', $d), date('Y', $d));
    $schedfinish = mktime(0,-1,0, date('m', $d), date('d', $d)+1, date('Y', $d));
    $dateoffset = floor(($d-time())/24/60/60);
    #echo "($schedstart, $schedfinish) ";
    $alltimes = timeSchedule($bookings, $schedstart, $schedfinish);

    $prevday  = isodate(dateAddDays($startdate, -1));
    $nextday  = isodate(dateAddDays($startdate, 1));
    ?>
      <p>Calendar for <?=$startdate?></p>
      <table class='centrein'><tr><td>
      <a class='but' href='<?="$href/$prevday"?>'>&laquo; Previous day</a>
      </td><td style='text-align: center;'>
      <a class='but' href='<?="$href/o=$dateoffset"?>'>Month view</a>
      </td><td style='text-align: right;'>
      <a class='but' href='<?="$href/$nextday"?>'>Next day &raquo;</a>
      </td></tr></table>
    <?
    ### Start of the actual calendar
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
      $href="$BASEURL/view/$instrument";
      showBookingsGraphical($href, $t, $height, $entry);
      #showBookingsListing($t['rec']);
      echo "\n";
    }
    echo "</ul>\n";
    echo "</td></table>";
    
    #echo "<input type='hidden' name='action' value='view' />";
    echo "<input type='hidden' name='instrument' value='$instrument' />";
    
  }

  function showBooking($auth, $PD) {
    #displayPost();
    $instrument = $PD['instrument'];
    displayInstrument ($instrument);
    $bookid = $PD['bookid'];
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
    echoSQL($q);
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
    if ($auth->isadmin || $g['userid']==$auth->uid) {
      editBooking($auth, $g);
    } else {
      displayBooking($auth, $g);
    }
  }

  function makeBooking($auth, $PD) {
    #displayPost();
    $g = array();
    $startticks = $PD['startticks'];
    $stopticks  = $PD['stopticks'];
    $g['ticks']     = $startticks;
    $g['starttime'] = isotime($startticks);
    $g['stoptime']  = isotime($stopticks);
    $g['date']      = isodate($startticks);
    $g['instrument']= $PD['instrument'];
    $g['bookid']    = -1;
    if (isset($MASQUID)) {
      $g['userid']  = $MASQUID;
      $g['bookedby']= $auth->uid;
    } else {
      $g['userid']  = $auth->uid;
    }
    $instrument = $PD['instrument'];
    displayInstrument ($instrument);
    editBooking($auth, $g);
  }

  function displayBooking($g) {
    echo "<input type='hidden' name='caloffset' value='".$PD['caloffset']."' />";
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

  function editBooking($auth, $g) {
    $past = ($g['ticks'] < time());
    $edit = ($auth->isadmin || ! $past) || ($g['bookid'] < 0);
    #echo "<pre>";
    #print_r($g);
    #echo "</pre>";
    if ($past) {
      echo "<p>Log entry for past instrument use:</p>";
    } else {
      echo "<p>Booking for instrument use:</p>";
    }
    /*  <input type='hidden' name='caloffset' value='<?=$PD['caloffset']?>' />*/
    ?>
      <input type='hidden' name='action' value='book' />
      <input type='hidden' name='booking' value='<?=$g['bookid']?>' />
      <input type='hidden' name='instrument' value='<?=$g['instrument']?>' />
      <table>
        <tr><th colspan='2'>Booking details</th></tr>
        <tr><td>Date</td>
          <td><?=$g['date']?>
          <input type='hidden' name='date' value='<?=$g['date']?>' />
        </td></tr>
        <tr><td>Start time</td>
        <td>
        <?= ($edit ? "<input type='text' name='starttime' value='".$g['starttime']."' size='8' maxlength='5'>"
                : $g['starttime']) ?>
        </td></tr>
        <tr><td>Finish time</td>
        <td>
        <?= ($edit ? "<input type='text' name='stoptime' value='".$g['stoptime']."' size='8' maxlength='5'>"
               : $g['stoptime']) ?>
        </td></tr>
    <?
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
      $userid = $auth->uid;
      echo "<input type='hidden' name='userid' value='$userid' />";
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
    
    if ($auth->isadmin) {
      ?>
        <tr><td>Booking IP address</td>
        <td><?=$g['ip']?></td></tr>
        <tr><td>Discount</td>
        <td><input type='text' name='discount' value='<?=$g['discount']?>' /></td></tr>
        <tr><td>Is half-day</td>
        <td><input type='checkbox' name='ishalfday' value='1'
        <?=($g['ishalfday']?"checked='1'":"")?> /></td></tr>
        <tr><td>Is full-day</td>
        <td><input type='checkbox' name='isfullday' value='1'
        <?=($g['isfullday']?"checked='1'":"")?> /></td></tr>
      <?
    }
    ?> 
      <tr>
        <td>
          <input type='submit' name='deletebooking' value='Delete booking'>
        </td>
        <td>
          <input type='submit' name='change' value='Edit/create booking'>
        </td>
      </tr>
    </table>
    <?
  }

  function viewSelectInstrument($auth, $PD) {
    global $BASEURL;
    $URL = "$BASEURL/view";
    $UID = $auth->uid;
    ?>
      <h1>Welcome</h1>
      <table>
      <tr><th>Select instrument to view</th></tr>
      <tr><td>
      <ul class="selectlist">
    <?

    $q = "SELECT instruments.id,instruments.name "
        ."FROM instruments "
        ."LEFT JOIN permissions ON instruments.id=permissions.instrid "
        ."WHERE userid='$UID'";
    $sql = mysql_query($q);
    if (! $sql) die (mysql_error());
    while ($row = mysql_fetch_row($sql)) {
      #echo "<option value='$row[0]'>$row[1]</option>";
      echo "<li><a href='$URL/$row[0]'>$row[1]</a></li>";
    }                                    
    #<select name="instrument">
    #</select>
    ?>
      </ul>
      </td></tr>
      <tr><td>
        <input type="submit" name="submit" value="Select instrument" />
      </td></tr>
      </table>
    <?
  }

?> 
