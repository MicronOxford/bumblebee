<?php
# $Id$
# Booking object

include_once 'inc/bookings/booking.php';
include_once 'inc/bookings/bookingdata.php';

class BookingEntryRO {
  var $id;
  var $data;
  
  function BookingEntryRO($id) {
    $this->id = $id;
    $this->_fill();
  }

  function _fill() {
    $bookdata = new BookingData(array('id' => $this->id), true);
    $this->data = $bookdata->dataEntry();
  }

  function display($displayAdmin, $displayOwner) {
    return $this->displayAsTable($displayAdmin, $displayOwner);
  }

  function displayAsTable($displayAdmin, $displayOwner) {
    $t = '<table class="tabularobject">';
    $t .= $this->data->displayInTable(2, $displayAdmin, $displayOwner);
    $t .= '</table>';
    return $t;
  }

} //class BookingEntryRO
