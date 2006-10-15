<?php
/**
* Make, edit and delete bookings
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

/** calendar object */
require_once 'inc/bb/calendar.php';
/** generic booking entry object */
require_once 'inc/bb/bookingentry.php';
/** read-only booking entry object */
require_once 'inc/bb/bookingentryro.php';
/** list of choices object */
require_once 'inc/formslib/anchortablelist.php';
/** date maniuplation objects */
require_once 'inc/date.php';
/** parent object */
require_once 'inc/actions/viewbase.php';

/**
* Make edit or delete a booking
* @package    Bumblebee
* @subpackage Actions
*/
class ActionBook extends ActionViewBase {
  /**
  * booking is for the logged in user
  * @var boolean
  */
  var $_isOwnBooking    = false;
  /**
  * logged in user can modify booking
  * @var boolean
  */
  var $_haveWriteAccess = false;


  /**
  * Initialising the class
  *
  * @param  BumblebeeAuth $auth  Authorisation object
  * @param  array $pdata   extra state data from the call path
  * @return void nothing
  */
  function ActionBook($auth, $PDATA) {
    parent::ActionViewBase($auth, $PDATA);
    $this->mungeInputData();
  }

  function go() {
    if (! isset($this->PD['instrid'])
          || $this->PD['instrid'] < 1
          || $this->PD['instrid'] == '') {
      $err = 'Invalid action specification in actions/book.php::go(): no instrument specified';
      $this->log($err);
      trigger_error($err, E_USER_WARNING);
      return;
    }

    $this->row = quickSQLSelect('instruments', 'id', $this->instrument);

    if (isset($this->PD['delete']) && isset($this->PD['bookid'])) {
      $this->deleteBooking();
      echo $this->_calendarViewLink($this->instrument);
    } elseif (isset($this->PD['bookid']) && isset($this->PD['edit'])) {
      $this->editBooking();
      echo $this->_calendarViewLink($this->instrument);
    } elseif (isset($this->PD['bookid'])) {
      $this->viewBooking();
      echo $this->_calendarViewLink($this->instrument);
    } elseif ( (isset($this->PD['startticks']) && isset($this->PD['stopticks']))
               || (isset($this->PD['bookwhen-time']) && isset($this->PD['bookwhen-date']) && isset($this->PD['duration']) ) ) {
      $this->createBooking();
      echo $this->_calendarViewLink($this->instrument);
    } else {
      # shouldn't get here
      $err = 'Invalid action specification in action/book.php::go()';
      $this->log($err);
      trigger_error($err, E_USER_WARNING);
      return;
    }
  }

  /**
  * Make a new booking
  */
  function createBooking() {
    $start = new SimpleDate(issetSet($this->PD, 'startticks'));
    $stop  = new SimpleDate(issetSet($this->PD, 'stopticks'));
    $duration = new SimpleTime($stop->subtract($start));
    $this->log($start->dateTimeString().', '.$duration->timeString().', '.$start->dow());

    if ($this->MakeBookingPermitted($start)) {
      $this->_editCreateBooking(-1, $start->dateTimeString(), $duration->timeString());
    } else {
      $this->_createBookingForbidden();
    }
  }

  /**
  * Editing an existing booking
  */
  function editBooking() {
    $start = new SimpleDate(issetSet($this->PD, 'startticks'));
    $this->_editCreateBooking($this->PD['bookid'], $start->dateTimeString(), -1);
  }

  /**
  * Do the hard work to edit or create the booking
  */
  function _editCreateBooking($bookid, $start, $duration) {
    $ip = $this->auth->getRemoteIP();
    //echo $ip;
    $booking = new BookingEntry($bookid, $this->auth, $this->instrument, $this->row['mindatechange'],$ip,
                                $start, $duration, $this->row['timeslotpicture']);
    $this->_checkBookingAuth($booking->fields['userid']->getValue());
    if (! $this->_haveWriteAccess) {
      return $this->_forbiddenError(T_('Edit booking'));
    }
    $booking->update($this->PD);
    $booking->checkValid();
    echo $this->displayInstrumentHeader();
    echo $this->reportAction($booking->sync(),
              array(
                  STATUS_OK =>   ($bookid < 0 ? T_('Booking made') : T_('Booking updated')),
                  STATUS_ERR =>  T_('Booking could not be made:').'<br/><br/>'.$booking->errorMessage
              )
            );
    echo $booking->display();
    $submit = ($booking->id < 0) ? T_('Make booking') : T_('Update booking');
    $delete = ($booking->id >= 0 && $booking->deletable) ? T_('Delete booking') : '';
    echo "<input type='submit' name='submit' value='$submit' />";
    if ($delete) echo "<input type='submit' name='delete' value='$delete' />";
    echo $this->displayInstrumentFooter();
  }

  /**
  * Display a booking in read-only format (i.e. not in a form to allow it to be edited)
  */
  function viewBooking() {
    $booking = new BookingEntryRO($this->PD['bookid']);
    $this->_checkBookingAuth($booking->data->userid);
    echo $this->displayInstrumentHeader();
    $adminView = $this->auth->permitted(BBROLE_VIEW_BOOKINGS_DETAILS, $this->instrument);
    echo $booking->display($adminView, $this->_isOwnBooking);
    $adminEdit = $this->auth->permitted(BBROLE_EDIT_ALL, $this->instrument);
    if ($this->_isOwnBooking || $adminEdit) {
      echo "<p><a href='"
            .makeURL('book',
                array('instrid' => $this->instrument,
                      'bookid'  => $this->PD['bookid'],
                      'edit'    => 1,
                      'isodate' => $this->PD['isodate']))
            ."'>". T_('Edit booking') ."</a></p>\n";
    }
  }

  /**
  * Delete a booking
  */
  function deleteBooking() {
    $booking = new BookingEntry($this->PD['bookid'], $this->auth, $this->instrument, $this->row['mindatechange']);
    $this->_checkBookingAuth($booking->fields['userid']->getValue());
    if (! $this->_haveWriteAccess) {
      return $this->_forbiddenError(T_('Delete booking'));
    }
    echo $this->displayInstrumentHeader();
    echo $this->reportAction($booking->delete(),
              array(
                  STATUS_OK =>   T_('Booking deleted'),
                  STATUS_ERR =>  T_('Booking could not be deleted:').'<br/><br/>'.$booking->errorMessage
              )
            );
  }

  function _createBookingForbidden() {
    $this->_Forbidden(T_('Sorry, making bookings at that time is not permitted.'));
  }

} // class ActionBook
?>