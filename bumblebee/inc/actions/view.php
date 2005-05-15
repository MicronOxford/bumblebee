<?php
# $Id$
# view a bookings calendar

include_once 'inc/bb/calendar.php';
include_once 'inc/bb/bookingentry.php';
include_once 'inc/bb/bookingentryro.php';
include_once 'inc/formslib/anchortablelist.php';
include_once 'inc/date.php';
include_once 'inc/actions/actionaction.php';

class ActionView extends ActionAction {
    
  var $_isOwnBooking    = false;
  var $_isAdminView     = false;
  var $_haveWriteAccess = false;


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
      return;
    }
    $instrument = $this->PD['instrid'];
    if (isset($this->PD['delete']) && isset($this->PD['bookid'])) {
      $this->deleteBooking();
      echo $this->_calendarViewLink($instrument);
    } elseif (isset($this->PD['isodate']) && 
                    ! isset($this->PD['bookid']) && ! isset($this->PD['startticks']) ) {
      $this->instrumentDay();
      echo $this->_calendarViewLink($instrument);
    } elseif (isset($this->PD['bookid']) && isset($this->PD['edit'])) {
      $this->editBooking();
      echo $this->_calendarViewLink($instrument);
    } elseif (isset($this->PD['bookid'])) {
      $this->booking();
      echo $this->_calendarViewLink($instrument);
    } elseif ( (isset($this->PD['startticks']) && isset($this->PD['stopticks']))
               || (isset($this->PD['bookwhen-time']) && isset($this->PD['bookwhen-date']) && isset($this->PD['duration']) ) ) {
      $this->createBooking();
      echo $this->_calendarViewLink($instrument);
    } elseif (isset($this->PD['instrid'])) {
      $this->instrumentMonth();
      echo "<br /><br /><a href='$BASEURL/view/'>Return to instrument list</a>";
    } else {
      # shouldn't get here
      $err = 'Invalid action specification in action/view.php::go()';
      $this->log($err);
      trigger_error($err, E_USER_WARNING);
      $this->selectInstrument();
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
        if (! isset($this->PD['bookid'])) {
          $this->PD['bookid'] = $m[1];
        }
      } elseif (preg_match("/edit/", $this->PDATA[$i], $m)) {
        $this->PD['edit'] = 1;
      } else {
        $this->log("I don't know what to do with that data!");
      }
    }
    echoData($this->PD, 0);
  }
  
  /**
   * calculates the number of days between the current date and the last date that was selected
   * by the user (in making a booking etc)
   */
  function _offset() {
    $now = new SimpleDate(time());
    if (isset($this->PD['isodate'])) {
      $then = new SimpleDate($this->PD['isodate']);
      return 'o='.floor($then->dsDaysBetween($now));
    } elseif (isset($this->PD['startticks'])) {
      $then = new SimpleDate($this->PD['startticks']);
      return $then->datestring;
    } elseif (isset($this->PD['bookwhen-date'])) {
      $then = new SimpleDate($this->PD['bookwhen-date']);
      return 'o='.floor($then->dsDaysBetween($now));
    }
  }
    
  function _calendarViewLink($instrument) {
    global $BASEURL;
    return '<br /><br /><a href="'.$BASEURL.'/view/'.$instrument.'/'
                      .$this->_offset().'">Return to calendar view</a>';
  }
                      
  function selectInstrument() {
    global $BASEURL;
    $instrselect = new AnchorTableList('Instrument', 'Select which instrument to view');
    if ($this->auth->isSystemAdmin()) {
      $instrselect->connectDB('instruments', 
                            array('id', 'name', 'longname')
                            );
    } else {                        
      $instrselect->connectDB('instruments', 
                            array('id', 'name', 'longname'),
                            'userid='.qw($this->auth->getEUID()),
                            'name', 
                            'id', 
                            NULL, 
                            array('permissions'=>'instrid=id'));
    }
    $instrselect->hrefbase = $BASEURL.'/view/';
    $instrselect->setFormat('id', '%s', array('name'), ' %50.50s', array('longname'));
    echo $instrselect->display();
  }

  function instrumentMonth() {
    global $BASEURL;
    global $CONFIG;
    $row = quickSQLSelect('instruments', 'id', $this->PD['instrid']);
    // Show a window $row['calendarlength'] weeks long starting $row['calendarhistory'] weeks 
    // before the current date. Displayed week always starts on Monday
    $offset = issetSet($this->PD, 'caloffset');
    $now = new SimpleDate(time());
    $now->dayRound();
    $start = $now;
    $start->addDays($offset);
    $day = date('w', $start->ticks); // the day of the week, 0=Sun, 6=Sat
    $start->addDays(1-7*$row['calhistory']-$day);
    $stop = $start;
    $stop->addDays(7*$row['callength']);
    
    $cal = new Calendar($start, $stop, $this->PD['instrid']);

    $daystart    = new SimpleTime($row['usualopen']);
    $daystop     = new SimpleTime($row['usualclose']);
    //configure the calendar view granularity (not the same as booking granularity)
    $granularity = $row['calprecision'];
    $timelines   = $row['caltimemarks'];
    $cal->setTimeSlotPicture($row['timeslotpicture']);
    #$granularity = 60*60;
//     echo $cal->display();
    $href=$BASEURL.'/view/'.$this->PD['instrid'];
    $cal->href=$href;
    $cal->isAdminView = $this->auth->isSystemAdmin() || $this->auth->isInstrumentAdmin($this->PD['instrid']);
    $cal->setOutputStyles('', $CONFIG['calendar']['todaystyle'], 
                preg_split('{/}',$CONFIG['calendar']['monthstyle']), 'm');
    echo $this->displayInstrumentHeader($row);
    echo $this->_linksForwardBack($href,'/o='.($offset-28),'','/o='.($offset+28));
    echo $cal->displayMonthAsTable($daystart,$daystop,$granularity,$timelines);
    echo $this->displayInstrumentFooter($row);
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
    $granularity = $row['calprecision'];
    $timelines   = $row['caltimemarks'];
    #echo $cal->display();
    $href=$BASEURL.'/view/'.$this->PD['instrid'];
    $cal->href=$href;
    $cal->isAdminView = $this->auth->isSystemAdmin() || $this->auth->isInstrumentAdmin($this->PD['instrid']);
    $cal->setOutputStyles('', 'caltoday', array('monodd', 'moneven'), 'm');
    echo $this->displayInstrumentHeader($row);
    echo $this->_linksForwardBack($href.'/', $start->datestring.'/o=-1', $today->datestring, $start->datestring.'/o=1');
    echo $cal->displayDayAsTable($daystart,$daystop,$granularity,$timelines);
    echo $this->displayInstrumentFooter($row);
  }

  function createBooking() {
    $start = new SimpleDate(issetSet($this->PD, 'startticks'));
    $stop  = new SimpleDate(issetSet($this->PD, 'stopticks'));
    $duration = new SimpleTime($stop->subtract($start));
    $this->log($start->datetimestring.', '.$duration->timestring.', '.$start->dow());
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
    $booking = new BookingEntry($bookid,$this->auth,$this->PD['instrid'], $row['mindatechange'],$ip, 
                                $start, $duration, $row['timeslotpicture']);
    $this->_checkBookingAuth($booking->fields['userid']->getValue());
    if (! $this->_haveWriteAccess) {
      return $this->_forbiddenError('Edit booking');
    }
    $booking->update($this->PD);
    $booking->checkValid();
    echo $this->displayInstrumentHeader($row);
    echo $this->reportAction($booking->sync(), 
              array(
                  STATUS_OK =>   ($bookid < 0 ? 'Booking made' : 'Booking updated'),
                  STATUS_ERR =>  'Booking could not be made:<br/><br/>'.$booking->errorMessage
              )
            );
    echo $booking->display();
    $submit = ($booking->id < 0) ? 'Make booking' : 'Update booking';
    $delete = ($booking->id >= 0 && $booking->deletable) ? 'Delete booking' : '';
    echo "<input type='submit' name='submit' value='$submit' />";
    if ($delete) echo "<input type='submit' name='delete' value='$delete' />";
    echo $this->displayInstrumentFooter($row);
  }

  function booking() {
    global $BASEURL;
    $booking = new BookingEntryRO($this->PD['bookid']);
    $this->_checkBookingAuth($booking->data->userid);
    $row = quickSQLSelect('instruments', 'id', $this->PD['instrid']);
    echo $this->displayInstrumentHeader($row);
    echo $booking->display($this->_isAdminView, $this->_isOwnBooking);
    if ($this->_isOwnBooking || $this->_isAdminView) {
      echo "<p><a href='$BASEURL/view/".$this->PD['instrid']
          //  .'/'.$this->PD['isodate']
            .'/'.$this->PD['bookid']."/edit'>Edit booking</a></p>\n";
    }
  }
  
  function deleteBooking() {
    global $BASEURL;
    $row = quickSQLSelect('instruments', 'id', $this->PD['instrid']);
    $booking = new BookingEntry($this->PD['bookid'], $this->auth, $this->PD['instrid'], $row['mindatechange']);
    $this->_checkBookingAuth($booking->fields['userid']->getValue());
    if (! $this->_haveWriteAccess) {
      return $this->_forbiddenError('Delete booking');
    }
    echo $this->displayInstrumentHeader($row);
    echo $this->reportAction($booking->delete(), 
              array(
                  STATUS_OK =>   'Booking deleted',
                  STATUS_ERR =>  'Booking could not be deleted:<br/><br/>'.$booking->errorMessage
              )
            );  
  }

  function _checkBookingAuth($userid) {
    $this->_isOwnBooking = $this->auth->isMe($userid);
    $this->_isAdminView = $this->auth->isSystemAdmin() 
                  || $this->auth->isInstrumentAdmin($this->PD['instrid']);
    $this->_haveWriteAccess = $this->_isOwnBooking || $this->_isAdminView;
  }

  function _forbiddenError($msg) {
    $this->log('Action forbidden: '.$msg);
    echo $this->reportAction(STATUS_FORBIDDEN, 
              array(
                  STATUS_FORBIDDEN => $msg.': <br/>Sorry, you do not have permission to do this.',
              )
            );
    return STATUS_FORBIDDEN;
  }
    
  function displayInstrumentHeader($row) {
    $t = '<h2 class="instrumentname">'
        //.$row['name']
        .$row['longname']
        .'</h2>'
       .'<p class="instrumentlocation">'
       //. $row['longname'] .'; '
       .$row['location'].'</p>'."\n";
    return $t;
  }
  
  function displayInstrumentFooter($row) {
    $t = '';
    if ($row['calendarcomment']) {
      $t = '<h3 style="padding-top: 2em;">Notes</h3>'
         .'<p>'.preg_replace("/\n+/", '</p><p>', $row['calendarcomment']).'</p>';
    }
    return $t;
  }


} // class ActionView
?> 
