<?php
# $Id$
# Booking object

include_once 'dbforms/dbrow.php';
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
  
  function BookingEntry($id, $auth, $instrumentid, $ip, $start, $duration, $granlist) {
    $this->slotrules = new TimeSlotRule($granlist);
    $isadmin = $auth->isSystemAdmin() || $auth->isInstrumentAdmin($instrumentid);
    if ($id > 0 && $isadmin) {
      $row = quickSQLSelect('bookings', 'id', $id);
      $euid = $row['userid'];
    } else {
      $euid = $auth->getEUID();
    }
    $this->DBRow('bookings', $id);
    $this->editable = 1;
    $f = new TextField('id', 'Booking ID');
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
    # FIXME can we truncate longname in some way? %15.15s?
    $f->setFormat('id', '%s', array('name'), ' (%s)', array('longname'));
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
    /*$f = new CheckBox('ishalfday', 'Half-day booking');
    $f->editable = $isadmin;
    $f->hidden = !$isadmin;
    $this->addElement($f);
    $f = new CheckBox('isfullday', 'Full-day booking');
    $f->editable = $isadmin;
    $f->hidden = !$isadmin;
    $this->addElement($f);*/
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
  **/
  function update($data) {
    parent::update($data);
    if ($this->changed) {
      //FIXME
//       $this->_checkGranularity();
    }
    return $this->changed;
  }

  /** 
   * override the default checkValid() method with a custom one that also checks that the
   * booking is permissible (i.e. the instrument is indeed free)
  **/
  function checkValid() {
    parent::checkValid();
    $this->isValid = $this->isValid && $this->_checkIsFree();
    $this->isValid = $this->isValid && $this->_legalSlot();
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
   * FIXME: this is still a race condition!
  **/
  function _checkIsFree() {
    #preDump($this);
    $doubleBook = 0;
    $instrument = $this->fields['instrument']->getValue();
    $start = $this->fields['bookwhen']->getValue();
    $d = new SimpleDate($start,1);
    $d->addTime(new SimpleTime($this->fields['duration']->getValue(),1));
    $stop = $d->datetimestring;
    $q = 'SELECT id, bookwhen, duration, '
        .'DATE_ADD( bookwhen, INTERVAL duration HOUR_SECOND ) AS stoptime '
        .'FROM bookings '
        .'WHERE instrument='.qw($instrument).' '
        .'AND id<>'.qw($this->id).' '
        .'HAVING (bookwhen <= '.qw($start).' AND stoptime > '.qw($start).') '
        .'OR (bookwhen < '.qw($stop).' AND stoptime >= '.qw($stop).') '
        .'OR (bookwhen >= '.qw($start).' AND stoptime <= '.qw($stop).')';
    $row = db_get_single($q, $this->fatal_sql);
    if (is_array($row)) {
      // then the booking actually overlaps another!
      $doubleBook = 1;
      $this->errorMessage = "Sorry, the instrument is not free at this time";
      echo $this->errorMessage;
      preDump($row);
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
    
/*
    $row = quickSQLSelect('instruments', 'id', $this->fields['instrument']->getValue());
    $g = new SimpleTime($row['granularity'],1);
    $start = new SimpleDate($this->fields['bookwhen']->getValue(),1);
    $start->floorTime($g);
    $this->fields['bookwhen']->set($start->datetimestring);
    $duration = new SimpleTime($this->fields['duration']->getValue(),1);
    $duration->ceilTime($g);
    $this->fields['duration']->set($duration->timestring);
    */
  }

  
} //class BookingEntry
