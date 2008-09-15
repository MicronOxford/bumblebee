<?php
/**
* View a bookings calendar and make bookings
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Actions
*
* path (bumblebee root)/inc/actions/calendar.php
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

require_once 'inc/bb/configreader.php';

/** calendar object */
require_once 'inc/bb/calendar.php';
/** date maniuplation objects */
require_once 'inc/date.php';
/** parent object */
require_once 'inc/actions/viewbase.php';
/** Data reflector object */
require_once 'inc/formslib/datareflector.php';
/** manage user settings for the calendar */
require_once 'inc/formslib/nondbrow.php';
/** user settings for the calendar: list of checkboxes */
require_once 'inc/formslib/checkboxtablelist.php';
/** user settings for the calendar: checkboxes for input */
require_once 'inc/formslib/checkbox.php';
/** user settings for the calendar: text fields for input */
require_once 'inc/formslib/textfield.php';

/**
* View a bookings calendar and make bookings
* @package    Bumblebee
* @subpackage Actions
*/
class ActionCalendar extends ActionViewBase {
  /** @var boolean   logged in user can modify booking  */
  var $_haveWriteAccess = false;

  /**
  * Initialising the class
  *
  * @param  BumblebeeAuth $auth  Authorisation object
  * @param  array $pdata   extra state data from the call path
  * @return void nothing
  */
  function ActionCalendar($auth, $PDATA) {
    parent::ActionViewBase($auth, $PDATA);
    #$this->mungeInputData();

    // catch bad arguments and send them back to the front page straight away
    if (! isset($this->PD['instrid'])
          || $this->PD['instrid'] < 1
          || $this->PD['instrid'] == '') {
      redirectUser(makeAbsURL());
      // never returns
    }
  }

  function go() {
    if (! $this->auth->permitted(BBROLE_VIEW_CALENDAR, $this->instrument)) {
      $this->_viewCalendarForbidden();
      return;
    }

    if (! $this->_loadMultiInstrument()) return;

    if (isset($this->PD['listview'])) {
      $this->instrumentListView();
      echo $this->_calendarViewLink($this->instrument);
      echo "<br /><br /><a href='".makeURL('view')."'>". T_('Return to instrument list') ."</a>";
    } elseif (isset($this->PD['isodate']) &&
                    ! isset($this->PD['bookid']) && ! isset($this->PD['startticks']) ) {
      echo $this->_makeCalendarConfigDialogue();
      $this->instrumentDay();
      echo $this->_calendarViewLink($this->instrument);
      echo $this->_calendarListViewLink($this->instrument);
      echo "<br /><br /><a href='".makeURL('view')."'>". T_('Return to instrument list') ."</a>";
    } else {
      echo $this->_makeCalendarConfigDialogue();
      $this->instrumentMonth();
      echo $this->_calendarListViewLink($this->instrument);
      echo "<br /><br /><a href='".makeURL('view')."'>". T_('Return to instrument list') ."</a>";
    }
  }

  function mungeInputData() {
    parent::mungeInputData();
    if (isset($this->PD['caloffset']) && preg_match("/\d\d\d\d-\d\d-\d\d/", $this->PD['caloffset'])) {
      $then = new SimpleDate($this->PD['caloffset']);
      $now = new SimpleDate(time());
      $this->PD['caloffset'] = floor($then->dsDaysBetween($now));
    }
    if (strpos($this->instrument, ',')) {
      $this->instrument = explode(',', $this->instrument);
    } else {
      $this->instrument = array($this->instrument);
    }
    if (isset($this->PD['configureview'])) {
      $this->instrument = array($this->instrument[0]);
      foreach ($this->PD as $key => $value) {
        if (preg_match('@^Instrument-(\d+)-include$@', $key, $matches)) {
          $id = $matches[1];
          if ($value && isset($this->PD["Instrument-$id-instrument"])) {
            $this->instrument[] = $this->PD["Instrument-$id-instrument"];
          }
        }
        $data = array();
      }

      /// FIXME: forcing a reload with a Location: header seems ugly and possibly fragile.
      // force a reload of the page, converting from a POST to a GET so that
      // later refreshes of the calendar can happen without generating a warning to the user
      if (isset($this->PD['caloffset'])) {
        $d['caloffset'] = $this->PD['caloffset'];
      }
      $d['instrid'] = join($this->instrument, ',');
      redirectUser(makeURL('calendar', $d, false));
      // never returns
    }
    echoData($this->PD, 0);
  }

  /**
  * Display a list view for the selected instrument
  */
  function instrumentListView() {
    $conf = ConfigReader::getInstance();
    // Show a window $row['calendarlength'] weeks long starting $row['calendarhistory'] weeks
    // before the current date. Displayed week starts on configured day of week.
    $offset = issetSet($this->PD, 'caloffset');
    if ($offset == 'today') $offset = 0;

    $now = new SimpleDate(time());
    $now->dayRound();
    $start = clone($now);
    $start->addDays($offset);

    $callength = 7 * $this->row['callength'];

    $week_offset = $conf->value('language', 'week_offset', 1);
    $start->weekRound();
    if ($now->dow() < $week_offset) $start->addDays(-7);
    $start->addDays($week_offset - 7*$this->row['calhistory']);

    $stop = clone($start);
    $stop->addDays($callength);

    // check to see if this is an allowable calendar view (not too far into the future)
    $futureView = $this->BookingPastNormalCalendar($stop);

    if ($futureView < 0 && ! $this->auth->permitted(BBROLE_VIEW_CALENDAR_FUTURE, $this->instrument)) {
      $off = ceil($futureView / 7) * 7;
      $start->addDays($off);
      $stop->addDays($off);
      $offset += $off;
    }


    $cal = new Calendar($start, $stop, $this->instrument);

    $cal->zoomhref = makeURL('calendar', array('instrid'=>$this->instrument));
    $cal->bookhrefCallback = array(&$this, 'MakeBookingHref');

    $cal->freeBusyOnly = ! $this->auth->permitted(BBROLE_VIEW_BOOKINGS, $this->instrument);

    $cal->setOutputStyles('', $conf->value('calendar', 'todaystyle'),
                preg_split('{/}',$conf->value('calendar', 'monthstyle')), 'm');

    echo $this->displayInstrumentHeader();
    echo $this->_linksForwardBack($start,
                                  ($offset-$callength),
                                  ($offset+$callength),
                                  $callength,
                                  array('listview'=>1));
    echo $cal->displayMonthAsList();
    echo $this->displayInstrumentFooter();
  }

  /**
  * Display the monthly calendar for the selected instrument
  */
  function instrumentMonth() {
    $conf = ConfigReader::getInstance();
    // Show a window $row['calendarlength'] weeks long starting $row['calendarhistory'] weeks
    // before the current date. Displayed week starts on configured day of week.
    $offset = issetSet($this->PD, 'caloffset');
    if ($offset == 'today') $offset = 0;

    $now = new SimpleDate(time());
    $now->dayRound();
    $start = clone($now);
    $start->addDays($offset);

    $callength = 7 * $this->row['callength'];

    $week_offset = $conf->value('language', 'week_offset', 1);
    $start->weekRound();
    if ($now->dow() < $week_offset) $start->addDays(-7);
    $start->addDays($week_offset - 7*$this->row['calhistory']);

    $stop = clone($start);
    $stop->addDays($callength);

    // check to see if this is an allowable calendar view (not too far into the future)
    $futureView = $this->BookingPastNormalCalendar($stop);

    if ($futureView < 0 && ! $this->auth->permitted(BBROLE_VIEW_CALENDAR_FUTURE, $this->instrument)) {
      $off = ceil($futureView / 7) * 7;
      $start->addDays($off);
      $stop->addDays($off);
      $offset += $off;
    }

    $cal = new Calendar($start, $stop, $this->instrument);

    $daystart    = new SimpleTime($this->row['usualopen']);
    $daystop     = new SimpleTime($this->row['usualclose']);
    //configure the calendar view granularity (not the same as booking granularity)
    $granularity = $this->row['calprecision'];
    $timelines   = $this->row['caltimemarks'];
    $cal->setTimeSlotPicture($this->row['timeslotpicture']);
    
    $cal->bookhrefCallback = array(&$this, 'MakeBookingHref');

    $cal->zoomhref = makeURL('calendar', array('instrid'=>$this->instrument));

    $cal->freeBusyOnly = ! $this->auth->permitted(BBROLE_VIEW_BOOKINGS, $this->instrument);
    $cal->isAdminView = $this->auth->permitted(BBROLE_MAKE_BOOKINGS_FREE, $this->instrument);
    $cal->setOutputStyles('', $conf->value('calendar', 'todaystyle'),
                preg_split('{/}',$conf->value('calendar', 'monthstyle')), 'm');
    echo $this->displayInstrumentHeader();
    echo $this->_linksForwardBack($start,
                                  ($offset-$callength),
                                  ($offset+$callength),
                                  $callength);
    echo $cal->displayMonthAsTable($daystart,$daystop,$granularity,$timelines);
    echo $this->displayInstrumentFooter();
  }

  /**
  * Generate back | today | forward links for the calendar
  * @return string html for links
  */
  function _linksForwardBack($centre, $back, $forward, $calLength, $extra=array()) {
    $links = array();
    $script = 'JumpCalendarTo';
    $today = new SimpleDate(time());
    $today->dayRound();
    $centreOffset = $today->dsDaysBetween($centre);

    $links[] = '<a href="'
                  .makeURL('calendar',
                            array_merge(
                            array('instrid'=>$this->instrument, 'caloffset'=>$back),
                            $extra)
                           )
                .'">&laquo; '. T_('earlier') .'</a>';

    $links[] = $this->_makeQuickJumpLinks(T_('jump back to:'), -1, $calLength, $centre, $centreOffset, $script);

    $links[] = '<a href="'
                  .makeURL('calendar',
                          array_merge(
                              array('instrid'=>$this->instrument, 'caloffset'=>'today'),
                              $extra)
                           )
              .'">'. T_('today') .'</a> ';

    if ($this->ViewCalendarPermitted($forward)) {
      $select = $this->_makeQuickJumpLinks(T_('jump forward to:'), 1, $calLength, $centre, $centreOffset, $script);

      if ($select !== '') $links[] = $select;

      $links[] = '<a href="'
                  .makeURL('calendar',
                          array_merge(
                              array('instrid'=>$this->instrument, 'caloffset'=>$forward),
                              $extra)
                           )
                  .'">'. T_('later') .' &raquo;</a>';
    }

    $linkList = join($links, ' | ');
    $location = makeURL('calendar',
                        array_merge(
                            array('instrid'=>$this->instrument),
                            $extra),
                         false);
    $js = "
          <script type='text/javascript'>
            function $script(offset) {
              newLocation = '$location' + '&caloffset=' + offset;
              document.location.href = newLocation;
            }
          </script>";
    return '<div style="text-align:center">' . $js . $linkList . '</div>';
  }


  function _makeQuickJumpLinks($caption, $direction, $calLength, $centre, $centreOffset, $script) {
    $nonEmpty = false;
    $backJumps = array('<option value="0">'. $caption .'</option>');
    for ($i=1; $i <= 20; $i++) {
      $offset = $direction * $i * $calLength;
      $offsetDate = clone($centre);
      $offsetDate->addDays($offset);
      $offset -= $centreOffset;
      if ($direction < 0 || $this->ViewCalendarPermitted($offset)) {
        $nonEmpty = true;
        $date = $offsetDate->dateString();
        $backJumps[] = "<option value='$offset'>$date</option>";
      } else {
        break;
      }
    }
    if ($nonEmpty) {
      return "<select name='backLink' onChange='$script(this.value)'>"
                  .join($backJumps, "\n")
                  .'</select>';
    } else {
      return '';
    }
  }

  function _makeCalendarConfigDialogue() {
    $t = '';
    $reflector = new DataReflector();
    $reflector->excludeLogin();
    $reflector->excludeRegex('@^Instrument-(\d+)-.+@');
    $t .= $reflector->display($this->PD);

    $div = 'bumblebeeCalendarControls';
    $func = $div.'_toggler';
    $t .=  "
      <script type='text/javascript'>
        function $func(show) {
          if (show) {
            hideDiv('toggle$div');
            showDiv('$div');
          } else {
            hideDiv('$div');
            showDiv('toggle$div');
          }
        }
      </script>";

    $t .= '<div id="bumblebeeCalendarControls" style="display: none;">';

    $t .= "<div style='text-align: right'><a href='javascript:$func(false)'>".T_('close').'</a></div>';

    $t .= '<fieldset>'
          .'<legend>'.T_('Select Instruments').'</legend>'
          . $this->_makeInstrumentAddDialogue()
          .'</fieldset>'
          . '<input type="submit" name="configureview" value="'.T_('Apply').'" />';

    $t .= '</div>';

    $t .= '<div id="bumblebeeCalendarControlsSwitch">';
    $t .= "<div id='toggle$div'>"
          ."<a href='javascript:$func(true)'>".T_('Calendar controls').'</a>'
          ."</div>";
    $t .= '</div>';
    return $t;
  }

  function _makeInstrumentAddDialogue() {
    $t = '';
    $selectRow = new nonDBRow('instrumentselect', NULL,
              T_('Select which instruments you want to add to this calendar'));
    $select = new CheckBoxTableList(T_('Instrument'), T_('Select which instruments to add'));
    $hidden = new TextField('instrument');
    $select->addFollowHidden($hidden);
    $include = new CheckBox('include', T_('Show'));
    $select->addCheckBox($include);
    $select->connectDB('instruments', array('id', 'name'),
                   "id != {$this->instrument[0]}");
    $select->setFormat('id', '%s', array('name'));
    $select->valueList = $this->instrument;
    $selectRow->addElement($select);

    return $selectRow->displayInTable(4);
  }

  /**
  * Display a single day's calendar for the selected instrument
  *
  * @todo ///TODO: combine this function with instrumentMonth to reduce duplication
  */
  function instrumentDay() {
    $granularity = $this->row['calprecision'];
    $timelines   = $this->row['caltimemarks'];

    $offset = issetSet($this->PD, 'caloffset');
    $today = new SimpleDate(time());
    $today->dayRound();

    if ($offset == 'today') {
      $start = clone($today);
      $offset = 0;
    } else {
      $start = new SimpleDate($this->PD['isodate']);
      $start->dayRound();
      $start->addDays($offset);
    }

    $stop = clone($start);
    $stop->addDays(1);

    // check to see if this is an allowable calendar view (not too far into the future)
    $futureView = $this->BookingPastNormalCalendar($start);

    if ($futureView < 0 && ! $this->auth->permitted(BBROLE_VIEW_CALENDAR_FUTURE, $this->instrument)) {
      $start->addDays($futureView);
      $stop->addDays($futureView);
      $offset += $futureView;
    }


/*
    $totaloffset = $start->daysBetween($today);
    $maxfuture = $row['calfuture'] + 7 - $today->dow();
    //admin users are allowed to see further into the future.
    $this->_checkBookingAuth(-1);
    $this->log("Found total offset of $totaloffset, $maxfuture");

    $futureView = false;
    if ($totaloffset > $maxfuture && ! $this->auth->permitted(BBROLE_VIEW_CALENDAR_FUTURE, $this->instrument)) {
      #echo "Found total offset of $totaloffset, but only ".$row['calfuture']." is permitted. ";
      $delta = $maxfuture-$totaloffset;
      #echo "Changing offset by $delta\n";
      $start->addDays($delta);
      $futureView = true;
    }*/
    $cal = new Calendar($start, $stop, $this->instrument);
    $cal->setTimeSlotPicture($this->row['timeslotpicture']);

    /// FIXME: get this from the instrument table?
    $daystart    = new SimpleTime('00:00:00');
    $daystop     = new SimpleTime('24:00:00');
//     echo $cal->display();
    $cal->bookhrefCallback = array(&$this, 'MakeBookingHref');

    $cal->freeBusyOnly = ! $this->auth->permitted(BBROLE_VIEW_BOOKINGS, $this->instrument);
    $cal->isAdminView = $this->auth->permitted(BBROLE_MAKE_BOOKINGS_FREE, $this->instrument);
    $cal->setOutputStyles('', 'caltoday', array('monodd', 'moneven'), 'm');
    echo $this->displayInstrumentHeader();
    echo $this->_linksForwardBack($start,
                                  '-1', '+1', 1,
                                  array('isodate'=>$start->dateString()));
    echo $cal->displayDayAsTable($daystart,$daystop,$granularity,$timelines);
    echo $this->displayInstrumentFooter();
  }

  function _viewCalendarForbidden() {
    $this->_Forbidden(T_('Sorry, you are not permitted to view this instrument\'s calendar.'));
  }

} // class ActionCalendar
?>
