<?php
# $Id$
# view a bookings calendar

include_once 'inc/calendar.php';
include_once 'inc/dbforms/anchortablelist.php';
include_once 'inc/dbforms/date.php';

  function actionView($auth) {
    global $BASEURL;
    $PD = viewMungePathData();
    if (! isset($PD['instrument'])
          || $PD['instrument'] < 1
          || $PD['instrument'] == '') {
      viewSelectInstrument($auth, $PD);
      echo "<br /><br /><a href='$BASEURL/'>Return to instrument list</a>";
    } elseif (isset($PD['isodate'])) {
      viewInstrumentWeek($PD);
      echo "<br /><br /><a href='$BASEURL/'>Return to instrument list</a>";
    } elseif (isset($PD['instrument'])) {
      viewInstrumentMonth($PD);
    } else {
      # shouldn't get here
    }
  }

  function viewMungePathData() {
    global $PDATA;
    $PD = array();
    foreach ($_POST as $k => $v) {
      $PD[$k] = $v;
    }
    if (isset($PDATA[1])) {
      $PD['instrument'] = $PDATA[1];
    }
    for ($i=2; isset($PDATA[$i]); $i++) {
      if (preg_match("/o=(-?\d+)/", $PDATA[$i], $m)) {
        $PD['caloffset'] = $m[1];
      } elseif (preg_match("/\d\d\d\d-\d\d-\d\d/", $PDATA[$i], $m)) {
        $PD['isodate'] = $PDATA[$i];
      } elseif (preg_match("/(\d+)-(\d+)/", $PDATA[$i], $m)) {
        $PD['startticks'] = $m[1];
        $PD['stopticks'] = $m[2];
      } elseif (preg_match("/(\d+)/", $PDATA[$i], $m)) {
        $PD['bookid'] = $m[1];
      } else {
        echo "I don't know what to do with that data!";
      }
    }
    preDump($PD);
    return $PD;
  }

  function viewSelectInstrument($auth, $PD) {
    global $BASEURL;
    $instrselect = new AnchorTableList("Instrument", "Select which instrument to view");
    $instrselect->connectDB("instruments", array("id", "name", "longname"));
    $instrselect->hrefbase = "$BASEURL/view/";
    $instrselect->setFormat("id", "%s", array("name"), " %s", array("longname"));
    echo $instrselect->display();
  }

  function viewInstrumentMonth($PD) {
    # FIXME: get this from a configuration table or file?
    #Show a window 6 weeks long starting 2 weeks before the current date
    #Displayed week starts on Monday
    $offset = issetSet($PD, 'offset');
    $offset -= 8;
    $now = new SimpleDate(time());
    $day = date("w", $now); #the day of the week, 0=Sun, 6=Sat
    $start = $now;
    $start->addDays($offset+1-$day);
    $stop = $start;
    $stop->addDays(7*6-1);
    $cal = new Calendar($start, $stop, $PD['instrument']);

    # FIXME: get this from the instrument table?
    $daystart    = 8;
    $daystop     = 18;
    $granularity = 0.25;
    echo $cal->displayMonthAsTable($daystart,$daystop,$granularity);
  }

?> 
