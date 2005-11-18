<?php
/**
* View a list of deleted bookings for an instrument over a given time period
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Actions
*/

include_once 'inc/formslib/anchortablelist.php';
include_once 'inc/formslib/datefield.php';
include_once 'inc/date.php';
include_once 'inc/bb/daterange.php';
include_once 'inc/actions/actionaction.php';

/**
* View a list of deleted bookings for an instrument over a given time period
*/
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
    $daterange = new DateRange('daterange', 'Select date range', 
                      'Enter the dates over which you want to report deleted bookings');
    $daterange->update($this->PD);
    $daterange->checkValid();
    if ($daterange->newObject) {
      $daterange->setDefaults(DR_PREVIOUS, DR_MONTH);
      echo $daterange->display($this->PD);
      echo "<br /><br /><a href='$BASEURL/deletedbookings/'>Return to instrument list</a>";
    } else {
      $instrument = $this->PD['instrid'];
      $this->showDeleted($daterange);
      echo "<br /><br /><a href='$BASEURL/deletedbookings/$instrument'>Choose different dates</a>";
    }
  }

  function mungePathData() {
    $this->PD = array();
    foreach ($_POST as $k => $v) {
      $this->PD[$k] = $v;
    }
    if (isset($this->PDATA[1]) && $this->PDATA[1] !== '') {
      $this->PD['instrid'] = $this->PDATA[1];
    }
    echoData($this->PD, 0);
  }
  
  /**
  * Select which instrument the listing should be generated for
  */
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

  /**
  * Display the deleted bookings
  */
  function showDeleted($daterange) {
    global $BASEURL;
    $start = $daterange->getStart();
    $stop  = $daterange->getStop();
    $stop->addDays(1);
    $instrument = $this->PD['instrid'];
    $bookings = new AnchorTableList('Bookings', 'Select deleted bookings');
    $bookings->setTableHeadings(array('Date', 'Duration', 'User', 'Log Entry'));
    $bookings->numcols = 4;
    $bookings->deleted = true;
    $bookings->connectDB('bookings', 
                            array('bookings.id', 'username', 'bookwhen', 'duration','log'),
                                    'bookwhen >= '.qw($start->datetimestring)
                              .' AND bookwhen < ' .qw($stop->datetimestring)
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

} // class ActionDeletedBookings
?> 
