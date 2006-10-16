<?php
/**
* View a list of instruments that are available
*
* @author     Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Actions
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

/** list of choices object */
require_once 'inc/formslib/anchortablelist.php';
/** parent object */
require_once 'inc/actions/viewbase.php';
/** list of bookings */
require_once 'inc/actions/bookinglist.php';

/**
* View a list of instruments so the user can view and make bookings
* @package    Bumblebee
* @subpackage Actions
*/
class ActionView extends ActionViewBase {

  var $list;
  var $defaultListingLength = 14;

  /**
  * Initialising the class
  *
  * @param  BumblebeeAuth $auth  Authorisation object
  * @param  array $pdata   extra state data from the call path
  * @return void nothing
  */
  function ActionView($auth, $PDATA) {
    parent::ActionViewBase($auth, $PDATA);
    $this->mungeInputData();

    $this->list = new ActionBookingList($auth, $PDATA);
  }

  function go() {
    $this->selectInstrument();
    $this->showMyBookings();
  }

  /**
  * Select which instrument for which the calendar should be displayed
  */
  function selectInstrument() {
    $instrselect = new AnchorTableList('Instrument', T_('Select which instrument to view'), 3);
    if ($this->auth->permitted(BBROLE_VIEW_LIST)) {
      $instrselect->connectDB('instruments',
                            array('id', 'name', 'longname', 'location')
                            );
    } else {
      $instrselect->connectDB('instruments',
                            array('id', 'name', 'longname', 'location'),
                            'userid='.qw($this->auth->getEUID()),
                            'name',
                            'id',
                            NULL,
                            array('permissions'=>'instrid=id'));
    }
    $instrselect->hrefbase = makeURL('calendar', array('instrid'=>'__id__'));
    $instrselect->setFormat('id', '%s', array('name'), ' %50.50s', array('longname'), ' %20.20s', array('location'));
    echo '<h2>' . T_('Please select an instrument to view') . '</h2>';
    echo $instrselect->display();
  }

  function showMyBookings() {
    $this->list->tableCaption = '<h2>' . T_('My bookings') . '</h2>';
    $this->list->noneFoundNotice = T_('You have no bookings between %s and %s.');
    $this->list->user = $this->auth->getEUID();
    $this->list->setDefaultRestrictions($this->defaultListingLength);
    $this->list->showBookings();
  }

} // class ActionView

?>
