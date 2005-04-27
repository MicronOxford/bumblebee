<?php
# $Id$
# Booking object

include_once 'dbforms/dbrow.php';
include_once 'dbforms/idfield.php';
include_once 'dbforms/textfield.php';
include_once 'dbforms/datetimefield.php';
include_once 'dbforms/timefield.php';
include_once 'dbforms/droplist.php';
include_once 'dbforms/referencefield.php';
include_once 'dbforms/dummyfield.php';

include_once 'bookings/timeslotrule.php';
include_once 'calendar.php';

class BookingEntry extends DBRow {
  var $slotrules;
  var $starttime;
  var $duration;
  var $_isadmin = 0;
  
  function BookingEntry($id, $auth, $instrumentid, $ip, $start, $duration, $granlist) {
    $this->slotrules = new TimeSlotRule($granlist);
    $isadmin = $auth->isSystemAdmin() || $auth->isInstrumentAdmin($instrumentid);
    $this->_isadmin = $isadmin;
    if ($id > 0 && $isadmin) {
      $row = quickSQLSelect('bookings', 'id', $id);
      $euid = $row['userid'];
    } else {
      $euid = $auth->getEUID();
    }
    $this->DBRow('bookings', $id);
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
    $this->starttime = &$f;
    $f = new DateTimeField('bookwhen', 'Start');
    $f->required = 1;
    $f->defaultValue = $start;
    $f->isInvalidTest = 'is_valid_datetime';
    $attrs = array('size' => '24');
    $f->setAttr($attrs);
    $f->setManualRepresentation($isadmin ? TF_FREE : TF_AUTO);
//     echo $f->manualRepresentation .'-'.$f->time->manualRepresentation."\n";
    $f->setSlots($this->slotrules);
    $this->addElement($f);
    $this->duration = &$f;
    $f = new TimeField('duration', 'Duration');
    $f->required = 1;
    $f->isInvalidTest = 'is_valid_time';
    $f->defaultValue = $duration;
    $f->setManualRepresentation($isadmin ? TF_FREE : TF_AUTO);
//     echo $f->manualRepresentation .'-'.$f->time->manualRepresentation."\n";
    $f->setSlots($this->slotrules);
    $f->setSlotStart($start);
    $this->addElement($f);
    $f = new DropList('projectid', 'Project');
    $f->connectDB('projects', 
                  array('id', 'name', 'longname'), 
                  'userid='.qw($euid),
                  'name', 
                  'id', 
                  NULL, 
                  array('userprojects'=>'projectid=id'));
    $f->setFormat('id', '%s', array('name'), ' (%15.15s)', array('longname'));
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
    $f->value = $euid;
    $this->addElement($f);
    $f = new ReferenceField('bookedby', 'Recorded by');
    $f->extraInfo('users', 'id', 'name');
    $f->value = $auth->uid;
    $f->editable = $isadmin;
    $f->hidden = !$isadmin;
    $this->addElement($f);
    $f = new TextField('discount', 'Discount (%)');
    $f->isInvalidTest = 'is_number';
    $f->defaultValue = '0';
    $f->editable = $isadmin;
    $f->hidden = !$isadmin;
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
   * override the default update() method with a custom one that allows us to
   * munge the start and finish times to fit in with the permitted granularity
   * 
   * * actually... do we really want to do that? let's just pass this through
   * * for the time being.
  **/
  function update($data) {
    return parent::update($data);
  }

  /** 
   * override the default checkValid() method with a custom one that also checks that the
   * booking is permissible (i.e. the instrument is indeed free)
  **/
  function checkValid() {
    parent::checkValid();
    $this->isValid = $this->isValid && $this->_checkIsFree();
    $this->isValid = $this->_isadmin || ($this->isValid && $this->_legalSlot());
    return $this->isValid;
  }

  function display() {
    return $this->displayAsTable();
  }

  function displayAsTable() {
    $t = "<table class='tabularobject'>";
    foreach ($this->fields as $k => $v) {
      $t .= $v->displayInTable(2);
    }
    $t .= "</table>";
    return $t;
  }
  
  /**
   * check that the booking slot is indeed free before booking it
   *
   * Here, we make a temporary booking and make sure that it is unique for that timeslot 
   * This is to prevent a race condition for checking and then making the new booking.
  **/
  function _checkIsFree() {
    if (! $this->changed) return 1;
    #preDump($this);
    $doubleBook = 0;
    $instrument = $this->fields['instrument']->getValue();
    $start = $this->fields['bookwhen']->getValue();
    $d = new SimpleDate($start,1);
    $duration = $this->fields['duration']->getValue();
    $d->addTime(new SimpleTime($duration));
    $stop = $d->datetimestring;
    
    $tmpid = $this->_makeTempBooking($instrument, $start, $duration);
        
    $q = 'SELECT bookings.id AS bookid, bookwhen, duration, '
        .'DATE_ADD( bookwhen, INTERVAL duration HOUR_SECOND ) AS stoptime, '
        .'name AS username '
        .'FROM bookings '
        .'LEFT JOIN users on bookings.userid = users.id '
        .'WHERE instrument='.qw($instrument).' '
        .'AND bookings.id<>'.qw($this->id).' '
        .'AND bookings.id<>'.qw($tmpid).' '
        .'AND userid<>0 '
        .'AND deleted<>TRUE '
        .'HAVING (bookwhen <= '.qw($start).' AND stoptime > '.qw($start).') '
        .'OR (bookwhen < '.qw($stop).' AND stoptime >= '.qw($stop).') '
        .'OR (bookwhen >= '.qw($start).' AND stoptime <= '.qw($stop).')';
    $row = db_get_single($q, $this->fatal_sql);
    if (is_array($row)) {
      // then the booking actually overlaps another!
      $this->_removeTempBooking($tmpid);
      $doubleBook = 1;
      $this->errorMessage = '<div class="error">'
                          .'Sorry, the instrument is not free at this time.<br /><br />'
                          .'Instrument booked by ' .$row['username']
                          .' (booking #' .$row['bookid']. ')<br />'
                          .'from '.$row['bookwhen'].' until ' .$row['stoptime']
                          .'</div>';
      echo $this->errorMessage;
      #preDump($row);
    } else {
      // then the new booking should take over this one, and we delete the old one.
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
    #echo "BookingEntry::_checkGranularity\n";
    $starttime = new SimpleDate($this->starttime->getValue());
    $stoptime = $starttime;
    $stoptime->addTime($this->duration->getValue());
    return $this->slotrules->isValidSlot($starttime, $stoptime);
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
    $row = new DBRow('bookings', $tmpid, 'id');
    $row->delete();
  }
  
  /**
   *  delete the entry         .(($this->restriction !== '') ? ' AND '.$this->restriction : '')
by marking it as deleted, don't actually delete the 
   *
   *  Note, this function returns false on success
   */
  function delete() {
    $sql_result = -1;
        $q = "UPDATE $this->table "
            ."SET $vals "
            ."WHERE $this->idfield=".qw($this->id)
            .(($this->restriction !== '') ? ' AND '.$this->restriction : '');
    $q = "UPDATE $this->table "
        ."SET deleted='TRUE' "
        ."WHERE $this->idfield=".qw($this->id)
        ." LIMIT 1";
    $sql_result = db_quiet($q, $this->fatal_sql);
    return $sql_result;
  }
  
     
} //class BookingEntry
