<?php
# $Id$
# view a bookings calendar

include_once 'inc/calendar.php';
include_once 'inc/bookingentry.php';
include_once 'inc/bookingentryro.php';
include_once 'inc/dbforms/anchortablelist.php';
include_once 'inc/dbforms/date.php';

  function actionView($auth) {
    global $BASEURL;
    $PD = viewMungePathData();
    if (! isset($PD['instrid'])
          || $PD['instrid'] < 1
          || $PD['instrid'] == '') {
      viewSelectInstrument($PD, $auth);
    } elseif (isset($PD['isodate'])) {
      viewInstrumentDay($PD);
      #FIXME need to make a proper return link
      echo "<br /><br /><a href='$BASEURL/'>Return to instrument list</a>";
    } elseif (isset($PD['startticks']) && isset($PD['stopticks'])) {
      viewCreateBooking($PD, $auth);
      #FIXME need to make a proper return link
      echo "<br /><br /><a href='$BASEURL/'>Return to instrument list</a>";
    } elseif (isset($PD['bookid']) && isset($PD['edit'])) {
      viewEditBooking($PD, $auth);
      #FIXME need to make a proper return link
      echo "<br /><br /><a href='$BASEURL/'>Return to instrument list</a>";
    } elseif (isset($PD['bookid'])) {
      viewBooking($PD, $auth);
      #FIXME need to make a proper return link
      echo "<br /><br /><a href='$BASEURL/'>Return to instrument list</a>";
    } elseif (isset($PD['instrid'])) {
      viewInstrumentMonth($PD);
      echo "<br /><br /><a href='$BASEURL/'>Return to instrument list</a>";
    } else {
      # shouldn't get here
    }
  }

  function viewMungePathData() {
    global $PDATA;
    $PD = array();
    foreach ($_POST as $k => $v) {
      $PD[$k] = $v;
    }
    if (isset($PDATA[1])) {
      $PD['instrid'] = $PDATA[1];
    }
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
      } elseif (preg_match("/edit/", $PDATA[$i], $m)) {
        $PD['edit'] = 1;
      } else {
        echo "I don't know what to do with that data!";
      }
    }
    preDump($PD);
    return $PD;
  }

  function viewSelectInstrument($PD, $auth) {
    global $BASEURL;
    $instrselect = new AnchorTableList("Instrument", "Select which instrument to view");
    $instrselect->connectDB("instruments", array("id", "name", "longname"));
    $instrselect->hrefbase = "$BASEURL/view/";
    $instrselect->setFormat("id", "%s", array("name"), " %s", array("longname"));
    echo $instrselect->display();
  }

  function viewInstrumentMonth($PD) {
    global $BASEURL;
    global $CONFIG;
    # FIXME: get this from a configuration table or file?
    #Show a window 6 weeks long starting 2 weeks before the current date
    #Displayed week starts on Monday
    $offset = issetSet($PD, 'caloffset');
    #$offset -= 8;
    $now = new SimpleDate(time());
    #$day = date("w Z", $now);
    #echo "o=$offset,d=$day\n";
    $now->dayRound();
    $day = date("w", $now->ticks); #the day of the week, 0=Sun, 6=Sat
    #echo "o=$offset,d=$day\n";
    $start = $now;
    //add one day to the offset so that the weekly display begins on a Monday
    //subtract seven days to start in the previous week
    $start->addDays($offset+1-7-$day);
    $stop = $start;
    $stop->addDays(7*6);
    $cal = new Calendar($start, $stop, $PD['instrid']);

    $row = quickSQLSelect('instruments', 'id', $PD['instrid']);
    $daystart    = new SimpleTime($row['usualopen'],1);
    $daystop     = new SimpleTime($row['usualclose'],1);
    $granularity = $CONFIG['calendar']['granularity'];
    $timelines   = $CONFIG['calendar']['timelines'];
    #$granularity = 60*60;
    #echo $cal->display();
    $href=$BASEURL.'/view/'.$PD['instrid'];
    $cal->href=$href;
    $cal->setOutputStyles('', $CONFIG['calendar']['todaystyle'], 
                preg_split('/\//',$CONFIG['calendar']['monthstyle']), 'm');
    echo viewLinksForwardBack($href,"/o=".$offset-28,"","/o=".$offset+28);
    echo $cal->displayMonthAsTable($daystart,$daystop,$granularity,$timelines);
  }

  function viewLinksForwardBack($href, $back, $today, $forward) {
    return '<div style="text-align:center">'
        .'<a href="'.$href.$back.'">&laquo; earlier</a> | '
        .'<a href="'.$href.$today.'">today</a> | '
        .'<a href="'.$href.$forward.'">later &raquo;</a>'
        .'</div>';
  }

  function viewInstrumentDay($PD) {
    global $BASEURL;
    $start = new SimpleDate($PD['isodate'],1);
    $start->dayRound();
    $offset = issetSet($PD, 'caloffset');
    $start->addDays($offset);
    $stop = $start;
    $stop->addDays(1);
    $today = new SimpleDate(time());
    $cal = new Calendar($start, $stop, $PD['instrid']);

    # FIXME: get this from the instrument table?
    $daystart    = new SimpleTime('00:00:00',1);
    $daystop     = new SimpleTime('23:59:59',1);
    $granularity = 15*60;
    #echo $cal->display();
    $href=$BASEURL.'/view/'.$PD['instrid'];
    $cal->href=$href;
    $cal->setOutputStyles('', 'caltoday', array('monodd', 'moneven'), 'm');
    echo viewLinksForwardBack($href.'/', $start->datestring.'/o=-1', $today->datestring, $start->datestring.'/o=1');
    echo $cal->displayDayAsTable($daystart,$daystop,$granularity,4);
  }

  function viewCreateBooking($PD, $auth) {
    $start = new SimpleDate($PD['startticks']);
    $stop  = new SimpleDate($PD['stopticks']);
    $row = quickSQLSelect('instruments', 'id', $PD['instrid']);
    $day = $start;
    $daystart = $day;
    $daystop = $day;
    $daystart->setTime($row['usualopen']);
    $daystop->setTime($row['usualclose']);
    $start->max($daystart);
    $stop->min($daystop);
    $duration = new SimpleTime($stop->subtract($start));
    viewEditCreateBooking($PD, -1, $auth, $start->datetimestring, $duration->timestring);
  }

  function viewEditBooking($PD, $auth) {
    viewEditCreateBooking($PD, $PD['bookid'], $auth, -1, -1);
  }

  function viewEditCreateBooking($PD, $bookid, $auth, $start, $duration) {
    global $BASEURL;
    $ip = getRemoteIP();
    echo $ip;
    $booking = new BookingEntry($bookid,$auth,$PD['instrid'],$ip, $start, $duration);
    $booking->update($PD);
    $booking->checkValid();
    $booking->sync();
    #echo $group->text_dump();
    echo $booking->display();
    if ($booking->id < 0) {
      $submit = "Make booking";
      $delete = "0";
    } else {
      $submit = "Update booking";
      $delete = "Delete booking";
    }
    #$submit = ($PD['id'] < 0 ? "Create new" : "Update entry");
    echo "<input type='submit' name='submit' value='$submit' />";
    if ($delete) echo "<input type='submit' name='delete' value='$delete' />";
  }

  function viewBooking($PD, $auth) {
    global $BASEURL;
    $booking = new BookingEntryRO($PD['bookid']);
    $isOwnBooking = $auth->isMe($booking->data->userid);
    $isAdminView = $auth->isSystemAdmin() || $auth->isInstrumentAdmin($PD['instrid']);
    echo $booking->display($isAdminView, $isOwnBooking);
    if ($isOwnBooking || $isAdminView) {
      echo "<p><a href='$BASEURL/view/".$PD['instrid'].'/'.$PD['bookid']."/edit'>Edit booking</a></p>\n";
    }
  }

?> 
