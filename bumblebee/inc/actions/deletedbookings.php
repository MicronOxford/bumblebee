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

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

/** list of choices */
require_once 'inc/formslib/anchortablelist.php';
/** date manipulation routines */
require_once 'inc/date.php';
/** DateRange object */
require_once 'inc/bb/daterange.php';
/** parent object */
require_once 'inc/actions/actionaction.php';

/**
* View a list of deleted bookings for an instrument over a given time period
* @package    Bumblebee
* @subpackage Actions
*/
class ActionDeletedBookings extends ActionAction {
    
  /**
  * Initialising the class 
  * 
  * @param  BumblebeeAuth $auth  Authorisation object
  * @param  array $pdata   extra state data from the call path
  * @return void nothing
  */
  function ActionDeletedBookings($auth, $PDATA) {
    parent::ActionAction($auth, $PDATA);
    $this->mungeInputData();
  }

  function go() {
    if (! isset($this->PD['instrid'])
          || $this->PD['instrid'] < 1
          || $this->PD['instrid'] == '') {
      $this->selectInstrument();
      return;
    }
    $daterange = new DateRange('daterange', T_('Select date range'), 
                      T_('Enter the dates over which you want to report deleted bookings'));
    $daterange->update($this->PD);
    $daterange->checkValid();
    if ($daterange->newObject) {
      $daterange->setDefaults(DR_PREVIOUS, DR_MONTH);
      echo $daterange->display($this->PD);
      echo "<br /><br /><a href='".makeURL('deletedbookings')."'>"
                  .T_('Return to instrument list')."</a>";
    } else {
      $this->showDeleted($daterange);
      echo "<br /><br /><a href='".makeURL('deletedbookings', array('instrid'=>$this->PD['instrid']))."'>".T_('Choose different dates')."</a>";
    }
  }

/*  function mungeInputData() {
    $this->PD = array();
    foreach ($_POST as $k => $v) {
      $this->PD[$k] = $v;
    }
    if (isset($this->PDATA[1]) && $this->PDATA[1] !== '') {
      $this->PD['instrid'] = $this->PDATA[1];
    }
    echoData($this->PD, 0);
  }
  */
  /**
  * Select which instrument the listing should be generated for
  */
  function selectInstrument() {
    $instrselect = new AnchorTableList(T_('Instrument'), T_('Select which instrument to view'));
    $instrselect->connectDB('instruments', 
                            array('id', 'name', 'longname')
                            );
    $instrselect->hrefbase = makeURL('deletedbookings', array('instrid'=>'__id__'));;
    $instrselect->setFormat('id', '%s', array('name'), ' %50.50s', array('longname'));
    echo $instrselect->display();
  }

  /**
  * Display the deleted bookings
  */
  function showDeleted($daterange) {
    $start = $daterange->getStart();
    $stop  = $daterange->getStop();
    $stop->addDays(1);
    $instrument = $this->PD['instrid'];
    $bookings = new AnchorTableList(T_('Bookings'), T_('Select deleted bookings'));
    $bookings->setTableHeadings(array(T_('Date'), T_('Duration'), T_('User'), T_('Log Entry')));
    $bookings->numcols = 4;
    $bookings->deleted = true;
    $bookings->connectDB('bookings', 
                            array('bookings.id', 'username', 'bookwhen', 'duration','log'),
                                    'bookwhen >= '.qw($start->dateTimeString())
                              .' AND bookwhen < ' .qw($stop->dateTimeString())
                              .' AND instrument = '.qw($instrument),
                            'bookwhen', 
                            'bookings.id', 
                            NULL, 
                            array('users'=>'userid=users.id'));
    $bookings->hrefbase = makeURL('view', array('bookid'=>'__id__', 'instrid'=>$instrument));
    $bookings->setFormat('id', '%s', array('bookwhen'),
                               '%s', array('duration'),
                               '%s', array('username'),
                               '%s', array('log'));
    echo $bookings->display();
  }

} // class ActionDeletedBookings
?> 
