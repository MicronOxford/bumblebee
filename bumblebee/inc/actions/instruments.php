<?php
# $Id$
# edit/create instruments

include_once 'inc/instrument.php';
include_once 'inc/dbforms/anchortablelist.php';

  function actionInstruments() {
    global $BASEURL;
    $PD = instrumentsMungePathData();
    if (! isset($PD['id'])) {
      selectInstruments();
    } elseif (isset($PD['delete'])) {
      deleteInstruments($PD['id']);
    } else {
      editInstruments($PD);
    }
    echo "<br /><br /><a href='$BASEURL/groups'>Return to instruments list</a>";
  }

  function instrumentsMungePathData() {
    global $PDATA;
    $PD = array();
    foreach ($_POST as $k => $v) {
      $PD[$k] = $v;
    }
    if (isset($PDATA[1])) {
      $PD['id'] = $PDATA[1];
    }
    #echo "<pre>".print_r($PD,1)."</pre>";
    return $PD;
  }

  function editInstruments($PD) {
    $instrument = new Instrument($PD['id']);
    $instrument->update($PD);
    $instrument->sync();
    #echo $instrument->text_dump();
    echo $instrument->display();
    if ($instrument->id < 0) {
      $submit = "Create new instrument";
      $delete = "0";
    } else {
      $submit = "Update entry";
      $delete = "Delete entry";
    }
    #$submit = ($PD['id'] < 0 ? "Create new" : "Update entry");
    echo "<input type='submit' name='submit' value='$submit' />";
    if ($delete) echo "<input type='submit' name='delete' value='$delete' />";
  }

  function selectInstruments() {
    global $BASEURL;
    $groupselect = new AnchorTableList("Instrument", "Select which instrument to view");
    $groupselect->connectDB("instruments", array("id", "name", "longname"));
    $groupselect->list->prepend(array("-1","Create new instrument"));
    $groupselect->hrefbase = "$BASEURL/instruments/";
    $groupselect->setFormat("id", "%s", array("name"), " %s", array("longname"));
    #echo $groupselect->list->text_dump();
    echo $groupselect->display();
  }

  function deleteInstruments($gpid)
  {
    $q = "DELETE FROM instruments WHERE id='$gpid'";
    db_quiet($q,1);
  }

?> 
