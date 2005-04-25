<?php
# $Id$
# edit/create instruments

include_once 'inc/instrument.php';
include_once 'inc/dbforms/anchortablelist.php';

class ActionInstruments extends ActionAction {

  function ActionInstruments($auth, $pdata) {
    parent::ActionAction($auth, $pdata);
    $this->mungePathData();
  }

  function go() {
    global $BASEURL;
    if (! isset($this->PD['id'])) {
      $this->selectInstruments();
    } elseif (isset($this->PD['delete'])) {
      $this->deleteInstruments($this->PD['id']);
    } else {
      $this->editInstruments();
    }
    echo "<br /><br /><a href='$BASEURL/groups'>Return to instruments list</a>";
  }

  function editInstruments() {
    $instrument = new Instrument($this->PD['id']);
    $instrument->update($this->PD);
    $instrument->checkValid();
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
    $select = new AnchorTableList("Instrument", "Select which instrument to view");
    $select->connectDB("instruments", array("id", "name", "longname"));
    $select->list->prepend(array("-1","Create new instrument"));
    $select->hrefbase = "$BASEURL/instruments/";
    $select->setFormat("id", "%s", array("name"), " %30.30s", array("longname"));
    #echo $groupselect->list->text_dump();
    echo $select->display();
  }

  function deleteInstruments()   {
    $instrument = new Instrument($this->PD['id']);
    $instrument->delete();
  }
}
?> 
