<?php
# $Id$
# edit consumables

include_once 'inc/consumable.php';
include_once 'inc/dbforms/anchortablelist.php';

class ActionConsumables extends ActionAction {

  function ActionConsumables($auth, $PDATA) {
    parent::ActionAction($auth, $PDATA);
    $this->mungePathData();
  }

  function go() {
    global $BASEURL;
    if (! isset($this->PD['id'])) {
      $this->selectConsumables();
    } elseif (isset($this->PD['delete'])) {
      $this->deleteConsumables($PD['id']);
    } else {
      $this->editConsumables();
    }
    echo "<br /><br /><a href='$BASEURL/consumables'>Return to consumables list</a>";
  }

  function selectConsumables() {
    global $BASEURL;
    $projectselect = new AnchorTableList("Consumables", "Select which Consumables to view");
    $projectselect->connectDB("consumables", array("id", "name", "longname"));
    $projectselect->list->prepend(array("-1","Create new consumable"));
    $projectselect->hrefbase = "$BASEURL/consumables/";
    $projectselect->setFormat("id", "%s", array("name"), " %s", array("longname"));
    echo $projectselect->display();
  }

  function editConsumables() {
    global $BASEURL;
    $consumable = new Consumable($this->PD['id']);
    $consumable->update($this->PD);
    $consumable->checkValid();
    #$consumable->fields['defaultclass']->invalid = 1;
    $consumable->sync();
    echo $consumable->display();
    if ($consumable->id < 0) {
      $submit = "Create new consumable";
      $delete = "0";
    } else {
      $submit = "Update entry";
      $delete = "Delete entry";
    }
    echo "<input type='submit' name='submit' value='$submit' />";
    if ($delete) echo "<input type='submit' name='delete' value='$delete' />";
    echo "\n<p><a href='$BASEURL/consume/consumable/$consumable->id/list'>"
          ."View usage records</a> "
        ."for this consumable</p>\n";
  }

  function deleteConsumable() {
    $consumable = new Consumable($this->PD['id']);
    $consumable->delete();
  }
}

?> 
