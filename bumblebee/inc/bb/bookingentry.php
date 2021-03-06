<?php
/**
* Booking entry object for creating/editing booking
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage DBObjects
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

require_once 'inc/bb/configreader.php';

/** parent object */
require_once 'inc/formslib/dbrow.php';
/** uses fields */
require_once 'inc/formslib/idfield.php';
require_once 'inc/formslib/textfield.php';
require_once 'inc/formslib/datetimefield.php';
require_once 'inc/formslib/timefield.php';
require_once 'inc/formslib/droplist.php';
require_once 'inc/formslib/referencefield.php';
require_once 'inc/formslib/dummyfield.php';
require_once 'inc/formslib/textfield.php';

/** uses time slot rules for management */
require_once 'inc/bookings/timeslotrule.php';
/** status codes for success/failure of database actions */
require_once 'inc/statuscodes.php';

/**
* Booking entry object for creating/editing booking
*
* @package    Bumblebee
* @subpackage DBObjects
*/
class BookingEntry extends DBRow {
  /** @var TimeSlotRule     rules for when the instrument can be booked    */
  var $slotrules;
  /** @var integer          EUID of booking user @see BumblebeeAuth  */
  var $euid;
  /** @var integer          UID of booking user @see BumblebeeAuth  */
  var $uid;
  /** @var BumblebeeAuth    auth object for checking user permissions */
  var $_auth;
  /** @var integer          minimum notice in hours to be given for unbooking an instrument  */
  var $minunbook;
  /** @var boolean          object not fully constructed (using short constructor for deleting booking only  */
  var $isShort = false;
  /** @var array            list of instrument id numbers */
  var $instrumentid;

  /**
  *  Create a new BookingEntry object
  *
  * @param integer       $id           booking id number (existing number or -1 for new)
  * @param BumblebeeAuth $auth         authorisation object
  * @param array         $instrumentid list of instrument id of instruments to be booked
  * @param integer       $minunbook    minimum notice to be given for unbooking (optional)
  * @param string        $ip           IP address of person making booking (for recording) (optional)
  * @param SimpleDate    $start        when the booking should start (optional)
  * @param SimpleTime    $duration     length of the booking (optional)
  * @param string        $granlist     timeslotrule picture (optional)
  */
  function BookingEntry($id, $auth, $instrumentid, $minunbook='', $ip='', $start='', $duration='', $granlist='') {
    $this->DEBUG = 0;
    $this->DBRow('bookings', $id);
    $this->deleteFromTable = 0;
    $this->_checkAuth($auth, $instrumentid);
    $this->minunbook = $minunbook;
    $this->instrumentid = $instrumentid;
    // check if lots of the input data is empty, then the constructor is only being used to delete the booking
    if ($ip=='' && $start=='' && $duration=='' && $granlist=='') {
      return $this->_bookingEntryShort($id, $instrumentid);
    }
    $this->slotrules = new TimeSlotRule($granlist);
    $this->editable = 1;
    $f = new IdField('id', T_('Booking ID'));
    $f->editable = 0;
    $f->duplicateName = 'bookid';
    $this->addElement($f);
    $f = new ReferenceField('instrument', T_('Instrument'));
    $f->extraInfo('instruments', 'id', 'name');
    $f->duplicateName = 'instrid';
    $f->defaultValue = join(',', $instrumentid);
    $this->addElement($f);
    $f = new TextField('startticks');
    $f->hidden = 1;
    $f->required = 1;
    $f->editable = 0;
    $f->sqlHidden = 1;
    $startticks = new SimpleDate($start);
    $f->value = $startticks->ticks;
    $this->addElement($f);

    $startf = new DateTimeField('bookwhen', T_('Start'));
//     $this->starttime = &$startf;
    $startf->required = 1;
    $startf->defaultValue = $start;
    $startf->isValidTest = 'is_valid_datetime';
    $attrs = array('size' => '24');
    $startf->setAttr($attrs);
    if ($this->_auth->permitted(BBROLE_MAKE_BOOKINGS_FREE, $instrumentid)) {
      $startf->setManualRepresentation($this->id == -1 ? TF_FREE : TF_FREE_ALWAYS);
    } else {
      $startf->setManualRepresentation(TF_AUTO);
    }
//     echo $f->manualRepresentation .'-'.$f->time->manualRepresentation."\n";
    $startf->setSlots($this->slotrules);
    $startf->setSlotStart($start);
    $startf->setEditableOutput(false, true);
    $this->addElement($startf);

    $durationf = new TimeField('duration', T_('Duration'));
//     $this->duration = &$durationf;
    $durationf->required = 1;
    $durationf->isValidTest = 'is_valid_nonzero_time';
    $durationf->defaultValue = $duration;
    if ($this->_auth->permitted(BBROLE_MAKE_BOOKINGS_FREE, $instrumentid)) {
      $durationf->setManualRepresentation($this->id == -1 ? TF_FREE : TF_FREE_ALWAYS);
    } else {
      $durationf->setManualRepresentation(TF_AUTO);
    }
//     echo $f->manualRepresentation .'-'.$f->time->manualRepresentation."\n";
    $durationf->setSlots($this->slotrules);
    $durationf->setSlotStart($start);

    $nextBooking = new NextBooking($start, $instrumentid);
    $durationf->maxDateDropDown = $nextBooking->booking;

    // load in instrument settings for how the dropdowns should be configured
    $instrrow = quickSQLSelect('instruments', 'id', $instrumentid);
    $durationf->extendDropDown    = issetSet($instrrow,   'bookacrossslots', true);
    $durationf->maxSlotsDropDown  = issetSet($instrrow,   'maxslotsbook',    20);
    $durationf->maxPeriodDropDown = issetSet($instrrow,   'maxbooklength',   86400);

    $this->addElement($durationf);

    $f = new DropList('projectid', T_('Project'));
    $f->connectDB('projects',
                  array('id', 'name', 'longname'),
                  'userid='.qw($this->euid),
                  'name',
                  'id',
                  NULL,
                  array('userprojects'=>'projectid=id'));
    $f->setFormat('id', '%s', array('name'), ' (%35.35s)', array('longname'));
    $f->isValidTest = 'is_valid_radiochoice';
    $this->addElement($f);
    $attrs = array('size' => '48');
    $f = new TextField('comments', T_('Comment to show on calendar'));
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('log', T_('Logbook Entry'));
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new ReferenceField('userid', T_('User'));
    $f->extraInfo('users', 'id', 'name');
    $f->value = $this->euid;
    $this->addElement($f);
    $f = new ReferenceField('bookedby', T_('Recorded by'));
    $f->extraInfo('users', 'id', 'name');
    $f->value = $auth->uid;
    $f->editable = $this->_auth->permitted(BBROLE_VIEW_BOOKINGS_DETAILS, $instrumentid);
    $f->hidden = ! $f->editable;
    $this->addElement($f);
    $f = new TextField('discount', T_('Discount (%)'));
    $f->isValidTest = 'is_number';
    $f->defaultValue = '0';
    $f->editable = $this->_auth->permitted(BBROLE_VIEW_BOOKINGS_DETAILS, $instrumentid);
    $f->hidden = ! $f->editable;
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('ip', T_('Computer IP'));
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
  * @param integer       $id           booking id number (existing number or -1 for new)
  * @param integer       $instrumentid instrument id of instrument to be booked
  */
  function _bookingEntryShort($id, $instrumentid) {
    $this->isShort = true;
    $f = new Field('id');
    $f->value = $id;
    $this->addElement($f);
    $f = new Field('instrument');   //not necessary, but for peace-of-mind.
    $f->value = $instrumentid;
    $this->addElement($f);
    $f = new Field('bookwhen');
    $this->addElement($f);
    $f = new Field('userid', T_('User'));
    $f->value = $this->euid;
    $this->addElement($f);
    $f = new Field('log', T_('Log'));
    $this->addElement($f);
    $this->fill();
  }

  /**
  *  check our admin status
  *
  * @param BumblebeeAuth $auth         authorisation object
  * @param integer       $instrumentid instrument id of instrument to be booked
  */
  function _checkAuth($auth, $instrumentid) {
    $this->_auth = $auth;
    $this->uid = $auth->uid;
    if ($this->id > 0 && $this->_auth->permitted(BBROLE_VIEW_BOOKINGS_DETAILS, $instrumentid)) {
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
    $this->_setDefaultDiscount();
    parent::update($data);
    $this->fields['bookwhen']->setSlotStart($this->fields['bookwhen']->getValue());
    $this->fields['duration']->setSlotStart($this->fields['bookwhen']->getValue());
    return $this->changed;
  }

  /**
  * override the default fill() method with a custom one that allows us to...
  * - work out what the startticks parameter is for generating links to the current calendar
  * - check permissions on whether we should be allowed to change the dates
  */
  function fill() {
    parent::fill();
    if (isset($this->fields['startticks']) && ! $this->fields['startticks']->value) {
      $this->fields['startticks']->value = $this->fields['bookwhen']->getValue();
    }
    // check whether we are allowed to modify time fields: this picks up existing objects immediately
    $this->_checkMinNotice();
  }

  /**
  * override the default sync() method with a custom one that allows us to...
  * - send a booking confirmation email to the instrument supervisors
  * - update the representation of times
  */
  function sync() {
    if (is_array($this->instrumentid) && count($this->instrumentid) > 1) {
      $this->log("Checking array of instrument ids");
      $status = STATUS_ERR;
      foreach ($this->children as $c) {
        $status = $c->sync();
        if ($status == STATUS_ERR) {
          $this->errorMessage .= $c->errorMessage;
          return $status;
        }
      }
      return $status;
    }

    $status = parent::sync();
    if ($status & STATUS_OK) {
      $this->_sendBookingEmail();
      if ($this->_auth->permitted(BBROLE_MAKE_BOOKINGS_FREE, $this->instrumentid)) {
        $this->fields['bookwhen']->setManualRepresentation($this->id == -1 ? TF_FREE : TF_FREE_ALWAYS);
        $this->fields['duration']->setManualRepresentation($this->id == -1 ? TF_FREE : TF_FREE_ALWAYS);
      }
    }
    return $status;
  }

  /**
  * Work out what the default discount for this timeslot is from the timeslotrules
  */
  function _setDefaultDiscount() {
    if ($this->isShort) return;

    $starttime = new SimpleDate($this->fields['bookwhen']->getValue());
    $slot = $this->slotrules->findSlotByStart($starttime);
    if (! $this->_auth->permitted(BBROLE_VIEW_BOOKINGS_DETAILS, $this->instrumentid)) {
      $this->fields['discount']->value = (isset($slot->discount) ? $slot->discount : 0);
      $this->log('BookingEntry::_setDefaultDiscount value '.$starttime->dateTimeString().' '.$slot->discount.'%');
      return;
    }

    if (! isset($this->fields['discount']->value)) {  // handle missing values in the submission
      //preDump($this->slotrules); preDump($slot);
      $this->fields['discount']->defaultValue = (isset($slot->discount) ? $slot->discount : 0);
      $this->log('BookingEntry::_setDefaultDiscount defaultValue '.$starttime->dateTimeString().' '.$slot->discount.'%');
    }
  }

  /**
  * make sure that a non-admin user is not trying to unbook the instrument with less than the minimum notice
  */
  function _checkMinNotice() {
    //$this->DEBUG=10;
    // get some cursory checks out of the way to save the expensive checks for later
    if ($this->_auth->permitted(BBROLE_UNBOOK_PAST, $this->instrumentid) || $this->id == -1) {
      //then we are unrestricted
      $this->log('Booking changes not limited by time restrictions as we are admin or new booking.',9);
      return;
    }
    $booking = new SimpleDate($this->fields['bookwhen']->getValue());
    $timeoffset = $this->minunbook*60*60;
    $booking->addTime(-1*$timeoffset);
    $now = new SimpleDate(time());
    $this->log('Booking times comparison: now='.$now->dateTimeString()
                  .', minunbook='.$booking->dateTimeString());
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
  *  if appropriate, send an email to the instrument supervisors to let them know that the
  *  booking has been made
  */
  function _sendBookingEmail() {
    $conf = ConfigReader::getInstance();

    //preDump($this->fields['instrument']);
    $instrument = quickSQLSelect('instruments', 'id', $this->fields['instrument']->getValue());
    if (! $instrument['emailonbooking']) {
      return;
    }

    $emails = array();
    foreach(preg_split('/,\s*/', $instrument['supervisors']) as $username) {
      $user = quickSQLSelect('users', 'username', $username);
      $emails[] = $user['email'];
    }
    $bookinguser = quickSQLSelect('users', 'id', $this->fields['userid']->value);
    $eol = "\r\n";
    $from = $instrument['name'].' '.$conf->value('instruments', 'emailFromName')
            .' <'.$conf->value('main', 'SystemEmail').'>';
    $replyto = $bookinguser['name'].' <'.$bookinguser['email'].'>';
    $to   = join($emails, ',');
    srand(time());
    $id   = '<bumblebee-'.time().'-'.rand().'@'.$_SERVER['SERVER_NAME'].'>';

    $headers  = 'From: '.$from .$eol;
    $headers .= 'Reply-To: '.$replyto.$eol;
    $headers .= 'Message-id: ' .$id .$eol;
    $subject = $instrument['name']. ': '. ($conf->value('instruments', 'emailSubject')
                    ? $conf->value('instruments', 'emailSubject') : 'Instrument booking notification');
    $message = $this->_getEmailText($instrument, $bookinguser);

    // Send the message
    #preDump($to);
    #preDump($subject);
    #preDump($headers);
    #preDump($message);
    $ok = @mail($to, $subject, $message, $headers);
    return $ok;

  }

  /**
  *  get the email text from the configured template with standard substitutions
  *
  * @param array  $instrument   instrument data (name => , longname => )
  * @param array  $user         user data (name => , username => )
  *
  * @todo //TODO:  graceful error handling for fopen, fread
  */
  function _getEmailText($instrument, $user) {
    $conf = ConfigReader::getInstance();
    $email_template = "templates/" . $conf->value('display', 'template') . "/export/emailnotificationtemplate.txt";

    $fh = fopen($email_template, 'r');
    $txt = fread($fh, filesize($email_template));
    fclose($fh);
    $start    = new SimpleDate($this->fields['bookwhen']->getValue());
    $duration = new SimpleTime($this->fields['duration']->getValue());
    $replace = array(
            '/__instrumentname__/'      => $instrument['name'],
            '/__instrumentlongname__/'  => $instrument['longname'],
            '/__start__/'               => $start->dateTimeString(),
            '/__duration__/'            => $duration->timeString(),
            '/__name__/'                => $user['name'],
            '/__username__/'            => $user['username'],
            '/__host__/'                => makeAbsURL()
                    );
    $txt = preg_replace(array_keys($replace),
                        array_values($replace),
                        $txt);
    return $txt;
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
    if (! $this->isValid) {
      $this->log('Fields are INVALID; bailing out');
      return $this->isValid;
    }
    $this->log('Individual fields are VALID');
    $this->isValid = $this->_auth->permitted(BBROLE_MAKE_BOOKINGS_FREE, $this->instrumentid)
                        || ($this->isValid && $this->_legalSlot() && $this->_permittedFuture());
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
  *
  * @global string prefix for table names
  **/
  function _checkIsFree() {
    global $TABLEPREFIX;

    if (is_array($this->instrumentid) && count($this->instrumentid) > 1) {
      $this->children = array();
      foreach ($this->instrumentid as $instr) {
        $clone = clone($this);
        $clone->instrumentid = $instr;
        $clone->fields['instrument']->value = $instr;
        $status = $clone->_checkIsFree();
        $this->children[] = $clone;
        if (! $status) {
          $this->errorMessage .= $clone->errorMessage;
          return $status;
        }
      }
      return $status;
    }

    if (! $this->changed) return 1;
    #preDump($this);
    $doubleBook = 0;
    $instrument = $this->fields['instrument']->getValue();
    $startdate = new SimpleDate($this->fields['bookwhen']->getValue());
    $start = $startdate->dateTimeString();
    $d = new SimpleDate($start);
    $duration = new SimpleTime($this->fields['duration']->getValue());
    $d->addTime($duration);
    $stop = $d->dateTimeString();

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
        .'AND bookings.deleted<>1 '        // old version of MySQL cannot handle true, use 1 instead
        .'HAVING (bookwhen <= '.qw($start).' AND stoptime > '.qw($start).') '
        .'OR (bookwhen < '.qw($stop).' AND stoptime >= '.qw($stop).') '
        .'OR (bookwhen >= '.qw($start).' AND stoptime <= '.qw($stop).')';
    $row = db_get_single($q, $this->fatal_sql);
    if (is_array($row)) {
      // then the booking actually overlaps another!
      $this->log('Overlapping bookings, error');
      $this->_removeTempBooking($tmpid);
      $doubleBook = 1;
      $this->errorMessage .= T_('Sorry, the instrument is not free at this time.').'<br /><br />'
                          .sprintf(T_('Instrument booked by %s (%s) from %s until %s.'),
                                  $row['username'],
                                  '<a href="'.
                                    makeURL('book',
                                      array('instrid' => $instrument,
                                            'bookid'  => $row['bookid'],
                                            'isodate' => $startdate->dateString())
                                           ).'">'
                                      .T_('booking #').$row['bookid'].'</a>',
                                  xssqw($row['bookwhen']),
                                  xssqw($row['stoptime']));
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
    #$this->DEBUG=10;
    $starttime = new SimpleDate($this->fields['bookwhen']->getValue());
    $stoptime = clone $starttime;
    $stoptime->addTime(new SimpleTime($this->fields['duration']->getValue()));
    $this->log('BookingEntry::_legalSlot '.$starttime->dateTimeString()
                  .' '.$stoptime->dateTimeString());
    $validslot = $this->slotrules->isValidSlot($starttime, $stoptime);
    if (! $validslot) {
      $this->log('This slot isn\'t legal so far... perhaps it is FreeForm?');
      $startslot = $this->slotrules->findSlotByStart($starttime);
      if (! $startslot) {
        $startslot = $this->slotrules->findSlotFromWithin($starttime);
      }
      //echo "now stop";
      $stopslot  = $this->slotrules->findSlotByStop($stoptime);
      if (! $stopslot) {
        $stopslot = $this->slotrules->findSlotFromWithin($stoptime);
      }
      #echo $startslot->start->dump();
      #echo $starttime->dump();
      #echo $stopslot->stop->dump();
      #echo $stoptime->dump();
      $validslot = $startslot->isFreeForm && $stopslot->isFreeForm;
      $this->log('It '.($validslot ? 'is' : 'is not').'!');
      if (! $validslot) {
        $this->log('Perhaps it is adjoining another booking with funny times?');
        $startok = ($startslot->start->ticks == $starttime->ticks);
        if (! $startok) {
          $this->log('Checking start time for adjoining stop');
          $startvalid = $this->_checkTimesAdjoining('stoptime', $starttime);
        }
        $stopok  = ($stopslot->stop->ticks  == $stoptime->ticks);
        if (! $stopok) {
          $this->log('Checking stop time for adjoining start');
          $stopvalid  = $this->_checkTimesAdjoining('bookwhen', $stoptime);
        }
        $validslot = ($startok || $startvalid) && ($stopok || $stopvalid);
        $this->log('It '.($validslot ? 'is' : 'is not').'!');
      }
    }
    if (! $validslot) {
      $this->errorMessage .= T_('Sorry, the timeslot you have selected is not valid, due to restrictions imposed by the instrument administrator.');
    }
    return $validslot;
  }

  /**
  * Check that the booking is not too far into the future
  *
  * @returns boolean    the booking is permitted
  */
  function _permittedFuture() {
    if ($this->_auth->permitted(BBROLE_MAKE_BOOKINGS_FUTURE, $this->instrumentid)) return true;

    $now = new SimpleDate(time());
    $now->dayRound();
    $now->addDays(1);

    $starttime = new SimpleDate($this->fields['bookwhen']->getValue());

    // permit bookings today or in the past
    if ($now->ticks > $starttime->ticks) return true;

    $row = quickSQLSelect('instruments', 'id', $this->instrumentid);
    $now = new SimpleDate(time());
    $now->weekRound();
    $now->addDays($row['calfuture'] + 7 + 1);

    if ($now->ticks < $starttime->ticks) {
      $this->errorMessage .= T_('Sorry, you cannot book that far into the future, due to restrictions imposed by the instrument administrator.');
    } else {
      return true;
    }
  }

  /**
  * check if this booking is adjoining existing bookings -- it can explain why the booking
  * is at funny times.
  *
  * @param string   $field        SQL name of the field to be checked (stoptime, bookwhen)
  * @param SimpleDate $checktime  time to check to see if it is adjoining the new booking
  *
  * @return boolean   there is a booking adjoining this time
  * @global string   prefix prepended to all table names in the db
  */
  function _checkTimesAdjoining($field, $checktime) {
      global $TABLEPREFIX;
      $instrument = $this->fields['instrument']->getValue();
      $time = $checktime->dateTimeString();
      $q = 'SELECT bookings.id AS bookid, bookwhen, duration, '
          .'DATE_ADD( bookwhen, INTERVAL duration HOUR_SECOND ) AS stoptime '
          .'FROM '.$TABLEPREFIX.'bookings AS bookings '
          .'WHERE instrument='.qw($instrument).' '
          .'AND userid<>0 '
          .'AND bookings.deleted<>1 '        // old version of MySQL cannot handle true, use 1 instead
          .'HAVING '.$field.' = '.qw($time);
      $row = db_get_single($q, $this->fatal_sql);
      $this->log(is_array($row) ? 'Found a matching booking' : 'No matching booking');
      return (is_array($row));
  }

  /**
  * make a temporary booking for this slot to eliminate race conditions for this booking
  *
  * @param integer  $instrument  instrument id
  * @param string   $start       date time string for the start of the booking
  * @param string   $duration    time string for the duration of the booking
  * @return integer  booking id number of the temporary booking
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
  * remove the temporary booking for this slot
  *
  * @param integer $tmpid   booking id number of the temporary booking
  */
  function _removeTempBooking($tmpid) {
    $this->log('Removing row, id='. $tmpid);
    $row = new DBRow('bookings', $tmpid, 'id');
    $row->delete();
  }

  /**
  *  delete the entry by marking it as deleted, don't actually delete the
  *
  *  @return integer  from statuscodes
  */
  function delete($unused=null) {
    $this->_checkMinNotice();
    if (! $this->deletable && ! $this->_auth->permitted(BBROLE_UNBOOK, $this->instrumentid)) {
      // we're not allowed to do so
      $this->errorMessage = T_('Sorry, this booking cannot be deleted due to booking policy.');
      return STATUS_FORBIDDEN;
    }
    $sql_result = -1;
    $today = new SimpleDate(time());
    $newlog = $this->fields['log']->value
                  .' '
                  .sprintf(T_('Booking deleted by %s (user #%s) on %s.'),
                        $this->_auth->username,
                        $this->uid,
                        $today->dateTimeString());
/*                  .'Booking deleted by '.$this->_auth->username
                  .' (user #'.$this->uid.') on '.$today->dateTimeString().'.';*/
    return parent::delete('log='.qw($newlog));
  }



} //class BookingEntry
