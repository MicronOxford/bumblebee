<?php
# $Id$
# view a bookings calendar

include_once 'inc/formslib/anchortablelist.php';
include_once 'inc/formslib/datefield.php';
include_once 'inc/date.php';
include_once 'inc/actions/actionaction.php';

class ActionDeletedBookings extends ActionAction {
    
  function ActionDeletedBookings($auth, $PDATA) {
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
    if (isset($this->PD['startdate']) && isset($this->PD['stopdate'])) {
      $start = new SimpleDate($this->PD['startdate']);
      $stop  = new SimpleDate($this->PD['stopdate']);
      if ($start->isValid && $stop->isValid) {
        $this->showDeleted($start, $stop);
        echo "<br /><br /><a href='$BASEURL/deletedbookings/$instrument'>Choose different dates</a>";
        return;
      }
    }
    $this->getStartStopDates();
    echo "<br /><br /><a href='$BASEURL/deletedbookings/'>Return to instrument list</a>";
  }

  function mungePathData() {
    $this->PD = array();
    foreach ($_POST as $k => $v) {
      $this->PD[$k] = $v;
    }
    if (isset($this->PDATA[1])) {
      $this->PD['instrid'] = $this->PDATA[1];
    }
    echoData($this->PD, 1);
  }
  
  function selectInstrument() {
    global $BASEURL;
    $instrselect = new AnchorTableList('Instrument', 'Select which instrument to view');
    $instrselect->connectDB('instruments', 
                            array('id', 'name', 'longname')
                            );
    $instrselect->hrefbase = $BASEURL.'/deletedbookings/';
    $instrselect->setFormat('id', '%s', array('name'), ' %50.50s', array('longname'));
    echo $instrselect->display();
  }

  function showDeleted($start, $stop) {
    global $BASEURL;
    $instrument = $this->PD['instrid'];
    $bookings = new AnchorTableList('Bookings', 'Select deleted bookings');
    $bookings->setTableHeadings(array('Date', 'Duration', 'User', 'Log Entry'));
    $bookings->numcols = 4;
    $bookings->connectDB('bookings', 
                            array('bookings.id', 'username', 'bookwhen', 'duration','log'),
                            'deleted = 1'
                              .' AND bookwhen >= '.qw($start->datetimestring)
                              .' AND bookwhen <= '.qw($stop->datetimestring)
                              .' AND instrument = '.qw($instrument),
                            'bookwhen', 
                            'bookings.id', 
                            NULL, 
                            array('users'=>'userid=users.id'));
    $bookings->hrefbase = $BASEURL.'/view/'.$instrument.'/';
    $bookings->setFormat('id', '%s', array('bookwhen'),
                               '%s', array('duration'),
                               '%s', array('username'),
                               '%s', array('log'));
    echo $bookings->display();
  }

  function getStartStopDates() {
    global $BASEURL;
    $now = new SimpleDate(time());
    $then = $now;
    $then->addDays(-28);
    $startdate = new DateField('startdate','Start date');
    $startdate->setDate($then->datestring);
    $stopdate  = new DateField('stopdate','Stop date');
    $stopdate->setDate($now->datestring);
    echo '<table>';
    echo '<input type="hidden" name="instrid" value="'.xssqw($this->PD['instrid']).'" />';
    echo $startdate->displayInTable(2);
    echo $stopdate->displayInTable(2);
    echo '</table>';
    echo '<input type="submit" name="submit" value="Go" />';
  }


} // class ActionDeletedBookings
?> 
