<?php
# $Id$
# view a bookings calendar

include_once 'inc/calendar.php';
include_once 'inc/bookingentry.php';
include_once 'inc/bookingentryro.php';
include_once 'inc/dbforms/anchortablelist.php';
include_once 'inc/dbforms/date.php';

class ActionView extends ActionAction {

  function ActionView($auth, $PDATA) {
    parent::ActionAction($auth, $PDATA);
    $this->mungePathData();
  }

  function go() {
    global $BASEURL;
    if (! isset($this->PD['instrid'])
          || $this->PD['instrid'] < 1
          || $this->PD['instrid'] == '') {
      $this->selectInstrument();
    } elseif (isset($this->PD['isodate'])) {
      $this->instrumentDay();
      #FIXME need to make a proper return link
      echo "<br /><br /><a href='$BASEURL/'>Return to instrument list</a>";
    } elseif (isset($this->PD['startticks']) && isset($this->PD['stopticks'])) {
      $this->createBooking();
      #FIXME need to make a proper return link
      echo "<br /><br /><a href='$BASEURL/'>Return to instrument list</a>";
    } elseif (isset($this->PD['bookid']) && isset($this->PD['edit'])) {
      $this->editBooking();
      #FIXME need to make a proper return link
      echo "<br /><br /><a href='$BASEURL/'>Return to instrument list</a>";
    } elseif (isset($this->PD['bookid'])) {
      $this->booking();
      #FIXME need to make a proper return link
      echo "<br /><br /><a href='$BASEURL/'>Return to instrument list</a>";
    } elseif (isset($this->PD['instrid'])) {
      $this->instrumentMonth();
      echo "<br /><br /><a href='$BASEURL/'>Return to instrument list</a>";
    } else {
      # shouldn't get here
      $err = 'I shouldn\'t be able to to get here: action/view.php::go()';
      $this->log($err);
      trigger_error($err, E_USER_WARNING);
    }
  }

  function mungePathData() {
    $this->PD = array();
    foreach ($_POST as $k => $v) {
      $this->PD[$k] = $v;
    }
    if (isset($this->PDATA[1])) {
      $this->PD['instrid'] = $this->PDATA[1];
    }
    for ($i=2; isset($this->PDATA[$i]); $i++) {
      if (preg_match("/o=(-?\d+)/", $this->PDATA[$i], $m)) {
        $this->PD['caloffset'] = $m[1];
      } elseif (preg_match("/\d\d\d\d-\d\d-\d\d/", $this->PDATA[$i], $m)) {
        $this->PD['isodate'] = $this->PDATA[$i];
      } elseif (preg_match("/(\d+)-(\d+)/", $this->PDATA[$i], $m)) {
        $this->PD['startticks'] = $m[1];
        $this->PD['stopticks'] = $m[2];
      } elseif (preg_match("/(\d+)/", $this->PDATA[$i], $m)) {
        $this->PD['bookid'] = $m[1];
      } elseif (preg_match("/edit/", $this->PDATA[$i], $m)) {
        $this->PD['edit'] = 1;
      } else {
        $this->log("I don't know what to do with that data!");
      }
    }
    echoData($this->PD);
  }

  function selectInstrument() {
    global $BASEURL;
    $instrselect = new AnchorTableList("Instrument", "Select which instrument to view");
    if ($this->auth->isSystemAdmin()) {
      $instrselect->connectDB("instruments", 
                            array("id", "name", "longname")
                            );
    } else {                        
      $instrselect->connectDB("instruments", 
                            array("id", "name", "longname"),
                            'userid='.qw($this->auth->getEUID()),
                            'name', 
                            'id', 
                            NULL, 
                            array('permissions'=>'instrid=id'));
    }
    $instrselect->hrefbase = "$BASEURL/view/";
    $instrselect->setFormat("id", "%s", array("name"), " %s", array("longname"));
    echo $instrselect->display();
  }

  function instrumentMonth() {
    global $BASEURL;
    global $CONFIG;
    # FIXME: get this from a configuration table or file?
    #Show a window 6 weeks long starting 2 weeks before the current date
    #Displayed week starts on Monday
    $offset = issetSet($this->PD, 'caloffset');
    #$offset -= 8;
    $now = new SimpleDate(time());
    #$day = date("w Z", $now);
    #echo "o=$offset,d=$day\n";
    $now->dayRound();
    $day = date("w", $now->ticks); #the day of the week, 0=Sun, 6=Sat
//     echo "o=$offset,d=$day\n";
    $start = $now;
    //add one day to the offset so that the weekly display begins on a Monday
    //subtract seven days to start in the previous week
    $start->addDays($offset+1-7-$day);
//     echo $start->datetimestring;
    $stop = $start;
    $stop->addDays(7*6);
//     echo $stop->datetimestring;
    $cal = new Calendar($start, $stop, $this->PD['instrid']);

    $row = quickSQLSelect('instruments', 'id', $this->PD['instrid']);
    $daystart    = new SimpleTime($row['usualopen'],1);
    $daystop     = new SimpleTime($row['usualclose'],1);
    //configure the calendar view granularity (not the same as booking granularity)
    $granularity = $CONFIG['calendar']['granularity'];
    $timelines   = $CONFIG['calendar']['timelines'];
    $cal->setTimeSlotPicture($row['timeslotpicture']);
    #$granularity = 60*60;
//     echo $cal->display();
    $href=$BASEURL.'/view/'.$this->PD['instrid'];
    $cal->href=$href;
    $cal->isAdminView = $this->auth->isSystemAdmin() || $this->auth->isInstrumentAdmin($this->PD['instrid']);
    $cal->setOutputStyles('', $CONFIG['calendar']['todaystyle'], 
                preg_split('/\//',$CONFIG['calendar']['monthstyle']), 'm');
    echo $this->_linksForwardBack($href,"/o=".($offset-28),"","/o=".($offset+28));
    echo $cal->displayMonthAsTable($daystart,$daystop,$granularity,$timelines);
  }

  function _linksForwardBack($href, $back, $today, $forward) {
    return '<div style="text-align:center">'
        .'<a href="'.$href.$back.'">&laquo; earlier</a> | '
        .'<a href="'.$href.$today.'">today</a> | '
        .'<a href="'.$href.$forward.'">later &raquo;</a>'
        .'</div>';
  }

  function instrumentDay() {
    global $BASEURL;
    $start = new SimpleDate($this->PD['isodate'],1);
    $start->dayRound();
    $offset = issetSet($this->PD, 'caloffset');
    $start->addDays($offset);
    $stop = $start;
    $stop->addDays(1);
    $today = new SimpleDate(time());
    $cal = new Calendar($start, $stop, $this->PD['instrid']);

    # FIXME: get this from the instrument table?
    $daystart    = new SimpleTime('00:00:00',1);
    $daystop     = new SimpleTime('23:59:59',1);
    $row = quickSQLSelect('instruments', 'id', $this->PD['instrid']);
    $cal->setTimeSlotPicture($row['timeslotpicture']);
    $granularity = 15*60;
    #echo $cal->display();
    $href=$BASEURL.'/view/'.$this->PD['instrid'];
    $cal->href=$href;
    $cal->isAdminView = $this->auth->isSystemAdmin() || $this->auth->isInstrumentAdmin($this->PD['instrid']);
    $cal->setOutputStyles('', 'caltoday', array('monodd', 'moneven'), 'm');
    echo $this->_linksForwardBack($href.'/', $start->datestring.'/o=-1', $today->datestring, $start->datestring.'/o=1');
    echo $cal->displayDayAsTable($daystart,$daystop,$granularity,4);
  }

  function createBooking() {
    $start = new SimpleDate($this->PD['startticks']);
    $stop  = new SimpleDate($this->PD['stopticks']);
    $row = quickSQLSelect('instruments', 'id', $this->PD['instrid']);
    $day = $start;
    $daystart = $day;
    $daystop = $day;
    $daystart->setTime($row['usualopen']);
    $daystop->setTime($row['usualclose']);
    $start->max($daystart);
    $stop->min($daystop);
    $duration = new SimpleTime($stop->subtract($start));
    $this->editCreateBooking(-1, $start->datetimestring, $duration->timestring);
  }

  function editBooking() {
    $this->editCreateBooking($this->PD['bookid'], -1, -1);
  }

  function editCreateBooking($bookid, $start, $duration) {
    global $BASEURL;
    $ip = $this->auth->getRemoteIP();
    //echo $ip;
    $row = quickSQLSelect('instruments', 'id', $this->PD['instrid']);
    $booking = new BookingEntry($bookid,$this->auth,$this->PD['instrid'],$ip, $start, $duration, $row['timeslotpicture']);
    $booking->update($this->PD);
    #echo "CHECKVALID\n";
    $booking->checkValid();
    #echo "VALID=$booking->isValid\n";
    $booking->sync();
    #echo "FINISH SYNC\n";
    #preDump($booking);
    #echo $group->text_dump();
    echo $booking->display();
    if ($booking->id < 0) {
      $submit = "Make booking";
      $delete = "0";
    } else {
      $submit = "Update booking";
      $delete = "Delete booking";
    }
    #$submit = ($this->PD['id'] < 0 ? "Create new" : "Update entry");
    echo "<input type='submit' name='submit' value='$submit' />";
    if ($delete) echo "<input type='submit' name='delete' value='$delete' />";
  }

  function booking() {
    global $BASEURL;
    $booking = new BookingEntryRO($this->PD['bookid']);
    $isOwnBooking = $this->auth->isMe($booking->data->userid);
    $isAdminView = $this->auth->isSystemAdmin() 
                  || $this->auth->isInstrumentAdmin($this->PD['instrid']);
    echo $booking->display($isAdminView, $isOwnBooking);
    if ($isOwnBooking || $isAdminView) {
      echo "<p><a href='$BASEURL/view/".$this->PD['instrid']
            .'/'.$this->PD['bookid']."/edit'>Edit booking</a></p>\n";
    }
  }

} // class ActionView
?> 
