<?php
# $Id$
# Booking object

include_once 'inc/formslib/dbrow.php';
include_once 'inc/formslib/idfield.php';
include_once 'inc/formslib/textfield.php';
include_once 'inc/formslib/datetimefield.php';
include_once 'inc/formslib/timefield.php';
include_once 'inc/formslib/droplist.php';
include_once 'inc/formslib/referencefield.php';
include_once 'inc/formslib/dummyfield.php';

include_once 'inc/bookings/timeslotrule.php';
include_once 'inc/bb/calendar.php';
include_once 'inc/statuscodes.php';

class BookingEntry extends DBRow {
  var $slotrules;
  var $_isadmin = 0;
  var $euid;
  var $uid;
  var $minunbook;
  
  function BookingEntry($id, $auth, $instrumentid, $minunbook='', $ip='', $start='', $duration='', $granlist='') {
    //$this->DEBUG = 10;
    $this->DBRow('bookings', $id);
    $this->_checkAuth($auth, $instrumentid);
    $this->minunbook = $minunbook;
    if ($ip=='' && $start=='' && $duration=='' && $granlist=='') {
      return $this->_bookingEntryShort($id, $instrumentid);
    }
    $this->slotrules = new TimeSlotRule($granlist);
    $this->editable = 1;
    $f = new IdField('id', 'Booking ID');
    $f->editable = 0;
    $f->duplicateName = 'bookid';
    $this->addElement($f);
    $f = new ReferenceField('instrument', 'Instrument');
    $f->extraInfo('instruments', 'id', 'name');
    $f->duplicateName = 'instrid';
    $f->defaultValue = $instrumentid;
    $this->addElement($f);
    $startf = new DateTimeField('bookwhen', 'Start');
//     $this->starttime = &$startf;
    $startf->required = 1;
    $startf->defaultValue = $start;
    $startf->isValidTest = 'is_valid_datetime';
    $attrs = array('size' => '24');
    $startf->setAttr($attrs);
    $startf->setManualRepresentation($this->_isadmin ? TF_FREE : TF_AUTO);
//     echo $f->manualRepresentation .'-'.$f->time->manualRepresentation."\n";
    $startf->setSlots($this->slotrules);
    $startf->setSlotStart($start);
    $startf->setEditableOutput(false, true);
    $this->addElement($startf);
    $durationf = new TimeField('duration', 'Duration');
//     $this->duration = &$durationf;
    $durationf->required = 1;
    $durationf->isValidTest = 'is_valid_nonzero_time';
    $durationf->defaultValue = $duration;
    $durationf->setManualRepresentation($this->_isadmin ? TF_FREE : TF_AUTO);
//     echo $f->manualRepresentation .'-'.$f->time->manualRepresentation."\n";
    $durationf->setSlots($this->slotrules);
    $durationf->setSlotStart($start);
    $this->addElement($durationf);
    $f = new DropList('projectid', 'Project');
    $f->connectDB('projects', 
                  array('id', 'name', 'longname'), 
                  'userid='.qw($this->euid),
                  'name', 
                  'id', 
                  NULL, 
                  array('userprojects'=>'projectid=id'));
    $f->setFormat('id', '%s', array('name'), ' (%35.35s)', array('longname'));
    $this->addElement($f);
    $attrs = array('size' => '48');
    $f = new TextField('comments', 'Comments');
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('log', 'Log');
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new ReferenceField('userid', 'User');
    $f->extraInfo('users', 'id', 'name');
    $f->value = $this->euid;
    $this->addElement($f);
    $f = new ReferenceField('bookedby', 'Recorded by');
    $f->extraInfo('users', 'id', 'name');
    $f->value = $auth->uid;
    $f->editable = $this->_isadmin;
    $f->hidden = !$this->_isadmin;
    $this->addElement($f);
    $f = new TextField('discount', 'Discount (%)');
    $f->isValidTest = 'is_number';
    $f->defaultValue = '0';
    $f->editable = $this->_isadmin;
    $f->hidden = !$this->_isadmin;
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('ip', 'Computer IP');
    $f->value = $ip;
    $f->editable = 0;
    $this->addElement($f);
    $this->fill();
    $this->dumpheader = 'Booking entry object';
    $f = new DummyField('edit');
    $f->value = '1';
    $this->addElement($f);
  }

  /**
   *  secondary constructor that we can use just for deleting
   *
   */
  function _bookingEntryShort($id, $instrumentid) {
    $f = new Field('id');
    $f->value = $id;
    $this->addElement($f);
    $f = new Field('instrument');   //not necessary, but for peace-of-mind.
    $f->value = $instrumentid;
    $this->addElement($f);
    $f = new Field('bookwhen');
    $this->addElement($f);
    $f = new Field('userid', 'User');
    $f->value = $this->euid;
    $this->addElement($f);
    $f = new Field('log', 'Log');
    $this->addElement($f);
    $this->fill();
  }

  /**
   *  check our admin status
   */
  function _checkAuth($auth, $instrumentid) {
    $this->_isadmin = $auth->isSystemAdmin() || $auth->isInstrumentAdmin($instrumentid);
    $this->uid = $auth->uid;
    if ($this->id > 0 && $this->_isadmin) {
      $row = quickSQLSelect('bookings', 'id', $this->id);
      $this->euid = $row['userid'];
    } else {
      $this->euid = $auth->getEUID();
    }
  }
    
  /** 
   * override the default update() method with a custom one that allows us to:
   * - munge the start and finish times to fit in with the permitted granularity
   */
  function update($data) {
    parent::update($data);
    $this->fields['bookwhen']->setSlotStart($this->fields['bookwhen']->getValue());
    $this->fields['duration']->setSlotStart($this->fields['bookwhen']->getValue());
    return $this->changed;
  }

  /** 
   * override the default fill() method with a custom one that allows us to...
   * - check permissions on whether we should be allowed to change the dates
   */
  function fill() {
    parent::fill();
    // check whether we are allowed to modify time fields: this picks up existing objects immediately
    $this->_checkMinNotice();
  }

  /** 
   * override the default sync() method with a custom one that allows us to...
   * - check permissions on whether we should be allowed to change the dates
   */
  function sync() {
    return parent::sync();
  }

  function _checkMinNotice() {
  //$this->DEBUG=10;
    // get some cursory checks out of the way to save the expensive checks for later
    if ($this->_isadmin || $this->id == -1) {
      //then we are unrestricted
      $this->log('Booking changes not limited by time restrictions as we are admin or new booking.',9);
      return;
    }
    $booking = new SimpleDate($this->fields['bookwhen']->getValue());
    $timeoffset = $this->minunbook*60*60;
    $booking->addTime(-1*$timeoffset);
    $now = new SimpleDate(time());
    $this->log('Booking times comparison: now='.$now->datetimestring
                  .', minunbook='.$booking->datetimestring);
    if ($booking->ticks < $now->ticks) {
      // then we can't edit the date and time and we shouldn't delete the booking
      $this->log('Within limitation period, preventing time changes and deletion',9);
      $this->deletable = 0;
      $this->fields['bookwhen']->editable = 0;
      $this->fields['duration']->editable = 0;
    } else {
      $this->log('Booking changes not limited by time restrictions.',9);
    }
  }
  
  /** 
   * override the default checkValid() method with a custom one that also checks that the
   * booking is permissible (i.e. the instrument is indeed free)
   *
   * A temp booking is made by _checkIsFree if all tests are OK. This temporary booking
   * secures the slot (no race conditions) and is then updated by the sync() method.
   */
  function checkValid() {
    //$this->DEBUG = 10;
    parent::checkValid();
    $this->log('Individual fields are '.($this->isValid ? 'VALID' : 'INVALID'));
    $this->isValid = $this->_isadmin || ($this->isValid && $this->_legalSlot());
    $this->log('After checking for legality of timeslot: '.($this->isValid ? 'VALID' : 'INVALID'));
    $this->isValid = $this->isValid && $this->_checkIsFree();
    $this->log('After checking for double bookings: '.($this->isValid ? 'VALID' : 'INVALID'));
    return $this->isValid;
  }

  function display() {
    // check again whether we are allowed to modify time objects -- after sync() we might not
    // be allowed to any more.
    $this->_checkMinNotice();
    return $this->displayAsTable();
  }
  
  /**
   * check that the booking slot is indeed free before booking it
   *
   * Here, we make a temporary booking and make sure that it is unique for that timeslot 
   * This is to prevent a race condition for checking and then making the new booking.
  **/
  function _checkIsFree() {
    global $TABLEPREFIX;
    if (! $this->changed) return 1;
    #preDump($this);
    $doubleBook = 0;
    $instrument = $this->fields['instrument']->getValue();
    $startdate = new SimpleDate($this->fields['bookwhen']->getValue());
    $start = $startdate->datetimestring;
    $d = new SimpleDate($start);
    $duration = new SimpleTime($this->fields['duration']->getValue());
    $d->addTime($duration);
    $stop = $d->datetimestring;
    
    $tmpid = $this->_makeTempBooking($instrument, $start, $duration->getHMSstring());
    $this->log('Created temp row for locking, id='.$tmpid.'(origid='.$this->id.')');
    
    $q = 'SELECT bookings.id AS bookid, bookwhen, duration, '
        .'DATE_ADD( bookwhen, INTERVAL duration HOUR_SECOND ) AS stoptime, '
        .'name AS username '
        .'FROM '.$TABLEPREFIX.'bookings AS bookings '
        .'LEFT JOIN '.$TABLEPREFIX.'users AS users ON '
        .'bookings.userid = users.id '
        .'WHERE instrument='.qw($instrument).' '
        .'AND bookings.id<>'.qw($this->id).' '
        .'AND bookings.id<>'.qw($tmpid).' '
        .'AND userid<>0 '
        .'AND deleted<>1 '        // old version of MySQL cannot handle true, use 1 instead
        .'HAVING (bookwhen <= '.qw($start).' AND stoptime > '.qw($start).') '
        .'OR (bookwhen < '.qw($stop).' AND stoptime >= '.qw($stop).') '
        .'OR (bookwhen >= '.qw($start).' AND stoptime <= '.qw($stop).')';
    $row = db_get_single($q, $this->fatal_sql);
    if (is_array($row)) {
      // then the booking actually overlaps another!
      $this->log('Overlapping bookings, error');
      $this->_removeTempBooking($tmpid);
      $doubleBook = 1;
      $this->errorMessage .= 'Sorry, the instrument is not free at this time.<br /><br />'
                          .'Instrument booked by ' .$row['username']
                          .' (<a href="'.$row['bookid'].'">booking #'.$row['bookid'].'</a>)<br />'
                          .'from '.$row['bookwhen'].' until ' .$row['stoptime'];
      // The error should be displayed by the driver class, not us. We *never* echo.
      //echo $this->errorMessage;
      #preDump($row);
    } else {
      // then the new booking should take over this one, and we delete the old one.
      $this->log('Booking slot OK, taking over tmp slot');
      $oldid = $this->id;
      $this->id = $tmpid;
      $this->fields[$this->idfield]->set($this->id);
      $this->insertRow = 0;
      $this->includeAllFields = 1;
      $this->_removeTempBooking($oldid);
    }
    return ! $doubleBook;
  }

  /** 
   * Ensure that the entered data fits the granularity criteria specified for this instrument
   */
  function _legalSlot() {
    //$this->DEBUG=10;
    $starttime = new SimpleDate($this->fields['bookwhen']->getValue());
    $stoptime = $starttime;
    $stoptime->addTime(new SimpleTime($this->fields['duration']->getValue()));
    $this->log('BookingEntry::_legalSlot '.$starttime->datetimestring
                  .' '.$stoptime->datetimestring);
    $validslot = $this->slotrules->isValidSlot($starttime, $stoptime);
    if (! $validslot) {
      $this->log('This slot isn\'t legal so far... perhaps it is FreeForm?');
      $startslot = $this->slotrules->findSlotFromWithin($starttime);
      //echo "now stop";
      $stopslot  = $this->slotrules->findSlotFromWithin($stoptime);
      $validslot = $startslot->isFreeForm && $stopslot->isFreeForm;
      $this->log('It '.($validslot ? 'is' : 'is not').'!');
    }
    if (! $validslot) {
      $this->errorMessage .= 'Sorry, the timeslot you have selected is not valid, '
                            .'due to restrictions imposed by the instrument administrator.';
    }
    return $validslot;
  }

  /** 
   * make a temporary booking for this slot to eliminate race conditions for this booking
   */
  function _makeTempBooking($instrument, $start, $duration) {
    $row = new DBRow('bookings', -1, 'id');
    $f = new Field('id');
    $f->value = -1;
    $row->addElement($f);
    $f = new Field('instrument');
    $f->value = $instrument;
    $row->addElement($f);
    $f = new Field('bookwhen');
    $f->value = $start;
    $row->addElement($f);
    $f = new Field('duration');
    $f->value = $duration;
    $row->addElement($f);
    $row->isValid = 1;
    $row->changed = 1;
    $row->insertRow = 1;
    $row->sync();
    return $row->id;
  }

  /** 
   * make a temporary booking for this slot to eliminate race conditions for this booking
   */
  function _removeTempBooking($tmpid) {
    $this->log('Removing row, id='. $tmpid);
    $row = new DBRow('bookings', $tmpid, 'id');
    $row->delete();
  }
  
  /**
   *  delete the entry by marking it as deleted, don't actually delete the 
   *
   *  Note, this function returns false on success
   */
  function delete() {
    global $TABLEPREFIX;
    $this->_checkMinNotice();
    if (! $this->deletable && ! $this->_isadmin) {
      // we're not allowed to do so 
      $this->errorMessage = 'Sorry, this booking cannot be deleted due to booking policy.';
      return STATUS_FORBIDDEN;
    }
    $sql_result = -1;
    $today = new SimpleDate(time());
    $newlog = $this->fields['log']->value
                  .'Booking deleted by user #'.$this->uid.' on '.$today->datetimestring.'.';
    $q = 'UPDATE '.$TABLEPREFIX.$this->table
        .' SET deleted=1,'       // old MySQL cannot handle true, use 1 instead
        .' log='.qw($newlog)
        .' WHERE '.$this->idfield.'='.qw($this->id)
        .' LIMIT 1';
    $sql_result = db_quiet($q, $this->fatal_sql);
    return $sql_result;
  }
  
  
     
} //class BookingEntry
