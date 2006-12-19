<?php
/**
* a textfield widget designed to handle time date
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage FormsLibrary
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

/** parent object */
require_once 'field.php';

/** Time field is in "FIXED" (uneditable) format */
define('TF_FIXED', 0);
/** Time field is in "DROP" (dropdown list) format */
define('TF_DROP', 1);
/** Time field is in "FREE" (type-in text box) format */
define('TF_FREE', 2);
/** Time field is in "FREE" (type-in text box) format always */
define('TF_FREE_ALWAYS', 3);
/** Time field is in "AUTO" (any of the above as appropriate) format */
define('TF_AUTO', -1);

/**
* a textfield widget designed to handle time date
*
* @package    Bumblebee
* @subpackage FormsLibrary
*/
class TimeField extends Field {
  /** @var SimpleTime    time object for this field */
  var $time;
  /** @var TimeSlotRule  rules for the times that are permitted    */
  var $list;
  /** @var boolean       field is the start time of a slot object    */
  var $isStart=0;
  /** @var SimpleDate    the date on which this time is located     */
  var $slotStart;
  /** @var TimeSlot      the actual time slot to which this time is associated    */
  var $slot;
  /** @var integer       how this field is to be rendered (see TF_* options)  */
  var $representation;
  /** @var integer       force this field to be rendered in a particular way (see TF_* options)    */
  var $_manualRepresentation = TF_AUTO;
  /** @var DropList      DropList object for dropdown list representation of the field    */
  var $droplist;
  /** @var boolean       is the time *really* editable ... ?? FIXME  */
  var $editableOutput=1;
  /** @var integer       maximum number of booking slots to include in the dropdown */
  var $maxSlotsDropDown = 20;
  /** @var boolean       go beyond the end of the slots that are part of a group for booking */
  var $extendDropDown = true;
  /** @var integer       max period to be included in the dropdown in seconds */
  var $maxPeriodDropDown = 86400;
  /** @var SimpleDate    max date for the dropdown to go to */
  var $maxDateDropDown = null;

  /**
  *  Create a new field object, designed to be superclasses
  *
  * @param string $name   the name of the field (db name, and html field name
  * @param string $longname  long name to be used in the label of the field in display
  * @param string $description  used in the html title or longdesc for the field
  */
  function TimeField($name, $longname='', $description='') {
    parent::Field($name, $longname, $description);
    $this->time = new SimpleTime(0);
    $this->slotStart = new SimpleDate(0);
    #$this->DEBUG=10;
  }

  function displayInTable($cols=3) {
    $errorclass = ($this->isValid ? '' : "class='inputerror'");
    $t = "<tr $errorclass><td>$this->longname</td>\n"
        ."<td title='$this->description'>";
    $t .= $this->getdisplay();
    $t .= "</td>\n";
    for ($i=0; $i<$cols-2; $i++) {
      $t .= '<td></td>';
    }
    $t .= '</tr>';
    return $t;
  }

  /**
  * render the HTML version of the widget
  *
  * @return string
  */
  function getDisplay() {
    $t = '';
    if ($this->editable && $this->editableOutput && ! $this->hidden) {
      $t .= $this->selectable();
    } else {
      if (!$this->hidden) $t .= xssqw($this->value);
      $t .= $this->hidden();
    }
    if ($this->duplicateName) {
      $t .= "<input type='hidden' name='$this->duplicateName' "
             ."value='".xssqw($this->value)."' />";
    }
    return $t;
  }

  function selectable() {
    #echo "TIME=".$this->time->timeString()."\n";
    $this->_determineRepresentation();
    $this->setTime(parent::getValue());
    $t = '';
    switch ($this->representation) {
      case TF_DROP:
        $this->_prepareDropDown();
        $t .= $this->droplist->selectable();
        break;
      case TF_FREE:
        $fixedname = $this->namebase.$this->name.'-fixed';
        $varname   = $this->namebase.$this->name.'-var';
        $t .= "<span id='$fixedname'>";
        if ($this->isStart) {
          $t .= $this->time->timeString();
          $t .= $this->hidden();
        } else {
          $this->_prepareDropDown();
          $t .= $this->droplist->selectable();
        }
        $t .= $this->_makeHiddenSwitch($fixedname, $varname);
        $t .= "</span>";
        $t .= "<span id='$varname' style='display: none'>";
        $t .= $this->_prepareFreeField('-varfield');
        $t .= "</span>";
        break;
      case TF_FREE_ALWAYS:
        $t .= $this->_prepareFreeField();
        break;
      case TF_FIXED:
        $t .= $this->time->timeString();
        $t .= $this->hidden();
        break;
    }
    return $t;
  }

  /**
   * set the representation of this field
   *
   * @param integer $flag (TF_* types from class TimeField constants)
   */
  function setManualRepresentation($flag) {
    $this->log("Manual representation set to $flag",10);
    $this->_manualRepresentation = $flag;
  }

  /**
   * Determine what sort of representation is appropriate
   */
  function _determineRepresentation() {
    //preDump($this);
    //$this->DEBUG = 10;
    $this->_findExactSlot();
    if (! isset($this->slot) && $this->slot != 0) {
      $this->log('No slot found, TF_FIXED');
      $this->representation = TF_FIXED;
      return;
    }
    //preDump($this->slot);
    if ($this->_manualRepresentation != TF_AUTO) {
      $this->log('Slot manually set');
      $this->representation = $this->_manualRepresentation;
    } elseif (! $this->editable) {
      $this->representation = TF_FIXED;
    } elseif ($this->slot->isFreeForm) {
      $this->representation = TF_FREE_ALWAYS;
/*    } elseif ($this->isStart || $this->slot->numslotsFollowing < 1) {
      $this->log('Starting slot or none following, TF_FIXED');
      $this->representation = TF_FIXED;*/
    } elseif (($duration = new SimpleTime($this->getValue()))
              && $this->slot->start->ticks + $duration->ticks != $this->slot->stop->ticks) {
      //$this->log($this->slot->start->ticks.' + '.$duration->ticks.' != '.$this->slot->stop->ticks);
      $this->log('Not exactly following slots, TF_FIXED');
      $this->representation = TF_FIXED;
    } elseif ($this->_fixedTimeSlots()) {
      $this->representation = TF_DROP;
    } else {
      $this->representation = TF_FREE_ALWAYS;
    }
    $this->log('Determined representation was '. $this->representation, 10);
  }

  /**
   * Calculate data for the dropdown list of permissible times
   *
   * @access private
   */
  function _prepareDropDown() {
    $ends = $this->slot->allSlotEnds();
    $nextSlot = $this->slot;
    $nextStart = $ends[count($ends)-1];
    $max = clone($this->slot->start);
    $max->addSecs($this->maxPeriodDropDown);

    while ($this->extendDropDown && count($ends) < $this->maxSlotsDropDown && is_object($nextSlot)) {
      #echo "Looking for next start. ";
      #echo $nextStart->dateTimeString()." ";
      #echo "Limit is ". $this->maxDateDropDown->dateTimeString()."<br />";
      $nextSlot = $this->list->findSlotByStart($nextStart);
      if (! is_object($nextSlot)) {
        // this should never really happen....
        break;
      }
      $nextStart = $nextSlot->stop;
      if (! $nextSlot->isAvailable) {
        #echo "Not available <br />";
        break;
      }
      if ($this->slot->start->ticks + $this->maxPeriodDropDown < $nextSlot->stop->ticks) {
        #echo "Past max allowable ".$nextSlot->stop->dateTimeString()."<br />";
        break;
      }
      if ($this->maxDateDropDown != null && $this->maxDateDropDown->ticks < $nextSlot->stop->ticks) {
        #echo "Conflicting with booking ".$nextSlot->stop->dateTimeString()."<br />";
        break;
      }

      $ends[] = $nextSlot->stop;
      #echo "Got an end: ". $nextSlot->stop->dateTimeString() ." ";
    }

    $dropVals = array();

    foreach($ends as $d) {
      $duration = new SimpleTime($d->ticks - $this->slot->start->ticks);
      #echo $d->datetimeString() . " -- ".$duration->timestring()."<br />";
      $dropVals[$duration->timeString()] = sprintf(T_("%s (until %s)"), $duration->timeString(), $d->datetimeString());
    }
    #preDump($dropVals);


    $this->droplist = new DropList($this->name, $this->description);
    $this->droplist->setValuesArray($dropVals, 'id', 'iv');
    $this->droplist->setFormat('id', '%s', array('iv'));
    //preDump($durations);
    //for ($j = count($durations)-1; $j >=0 && $durations[$j] != $this->value; $j--) {
    //}
    //$this->droplist->setDefault($j);
    $this->droplist->setDefault($this->value);
  }

  /**
   * Free-form field entry
   *
   * @access private
   * @param string   $append  append this to the name of the input (optional)
   * @param string   $hidden  show this as hidden by default
   * @return string html for field
   */
  function _prepareFreeField($append='', $hidden=false) {
    $t  = "<input type='text' name='{$this->namebase}{$this->name}$append' "
        ."value='".xssqw($this->time->timeString())."' ";
    $t .= (isset($this->attr['size']) ? "size='".$this->attr['size']."' " : "");
    $t .= (isset($this->attr['maxlength']) ? "maxlength='".$this->attr['maxlength']."' " : "");
    $t .= ($hidden) ? "style='display: none;' " : "";
    $t .= "/>";
    return $t;
  }

  /**
   * Convert a string into a js link that controls the behaviour of another div
   *
   * @access private
   * @param string $id1 default shown element
   * @param string $id2 default hidden element
   * @return string html for js link
   */
  function _makeHiddenSwitch($id1, $id2) {
    $func = preg_replace('/[^\w]/', '_', "hideunhide$id1");
    $t = "

      <input type='hidden' id='{$this->namebase}{$this->name}-switch' name='{$this->namebase}{$this->name}-switch' value='' />
      <script type='text/javascript'>
        function $func() {
          var id1 = document.getElementById('$id1');
          id1.style.display = 'none';
          var switchfield = document.getElementById('{$this->namebase}{$this->name}-switch');
          switchfield.value = 'varfield';
          var id2 = document.getElementById('$id2');
          id2.style.display = 'inline';
        }
      </script>
      <a href='javascript:$func();'>".T_('edit times')."</a>
      ";
    return $t;
  }

  /**
   * determine if a dropdown list appropriate here?
   *
   * @access private
   */
  function _fixedTimeSlots() {
    return true;
    if ($this->slotStart->ticks == 0) {
      $this->fixedTimeSlots = false;
    } else {
      $this->fixedTimeSlots = $this->slot->numslotsFollowing;
    }
    $this->log('Slot starts at '.$this->slotStart->dateTimeString()
                .', numFollowing: '.$this->fixedTimeSlots, 8);
    return $this->fixedTimeSlots;
  }

  /**
   * overload the parent's set() method as we need to do some extra processing
   */
  function set($value) {
    parent::set($value);
    if (strpos($value, '-') === false) {
      $this->setTime($value);
    } else {
      $this->setDateTime($value);
    }
  }

  /**
   * overload the parent's update method so that local calculations can be performed
   *
   * @param array $data html_name => value pairs
   *
   * @return boolean the value was updated
   */
  function update($data) {
    if (isset($data{$this->namebase.$this->name.'-switch'}) && $data{$this->namebase.$this->name.'-switch'}) {
      $data{$this->namebase.$this->name} = $data{$this->namebase.$this->name.'-varfield'};
    }
    if (parent::update($data)) {
      $this->setTime($this->value);
    }
    return $this->changed;
  }

  /**
   * Set the time (and value) from a Date-Time string
   *
   * @param string $time a date-time string (YYYY-MM-DD HH:MM:SS)
   */
  function setDateTime($time) {
    #echo "SETDATETIME = $time\n";
    $date = new SimpleDate($time);
    $this->date = $date;
    $this->setTime($date->timePart());
  }

  /**
   * Set the time (and value)
   *
   * @param string $time a time string (HH:MM)
   */
  function setTime($time) {
    #echo "SETTIME = $time\n";
    $this->time = new SimpleTime($time);
    $this->value = $this->time->timeString();
  }

  /**
   * associate a TimeSlotRule for validation of the times that we are using
   *
   * @param TimeSlotRule $list a valid TimeSlotRule
   */
  function setSlots($list) {
    $this->list = $list;
    //preDump($list);
  }

  /**
   * set the appropriate date that we are refering to for the timeslot rule validation
   *
   * @param string $date passed to the TimeSlotRule
   */
  function setSlotStart($date) {
    $this->slotStart = new SimpleDate($date);
  }

  function _findExactSlot() {
    $this->log('Looking for slot starting at '.$this->slotStart->dateTimeString(), 10);
    $this->slot = $this->list->findSlotByStart($this->slotStart);
    if (! $this->slot) {
      $this->slot = $this->list->findSlotFromWithin($this->slotStart);
    }
    $this->log('Found slot start '.$this->slot->start->dateTimeString(), 10);
    $this->log('Found slot stop '.$this->slot->stop->dateTimeString(), 10);
/*    if ($this->slot == '0') {
      $this->slot = $this->list->findSlotFromWithin($this->slotStart);
    }*/
  }

  /**
   *  isValid test (extend Field::isValid), looking at whether the string parsed OK
   */
  function isValid() {
    parent::isValid();
    $this->isValid = $this->isValid && $this->time->isValid;
    return $this->isValid;
  }

  /**
   * return a SQL-injection-cleansed string that can be used in an SQL
   * UPDATE or INSERT statement. i.e. "name='Stuart'".
   *
   * @return string  in SQL assignable form
   */
  function sqlSetStr($name='', $force=false) {
    if (empty($name)) {
      $name = $this->name;
    }
    if (! $this->sqlHidden) {
      return $name .'='. qw($this->time->getHMSstring());
    } else {
      return '';
    }
  }


} // class TimeField


?>
