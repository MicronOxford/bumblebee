<?php
# $Id$
# edit consumables

include_once 'inc/consumable.php';
include_once 'inc/dbforms/anchortablelist.php';


  function actionConsumables() {
    global $BASEURL;
    $PD = consumablesMungePathData();
    if (! isset($PD['id'])) {
      selectConsumables();
    } elseif (isset($PD['delete'])) {
      deleteConsumables($PD['id']);
    } else {
      editConsumables($PD);
    }
    echo "<br /><br /><a href='$BASEURL/consumables'>Return to consumables list</a>";
  }

  function consumablesMungePathData() {
    global $PDATA;
    $PD = array();
    foreach ($_POST as $k => $v) {
      $PD[$k] = $v;
    }
    if (isset($PDATA[1])) {
      $PD['id'] = $PDATA[1];
    }
    #$PD['defaultclass'] = 12;
    echo "<pre>".print_r($PD,true)."</pre>";
    return $PD;
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

  function editConsumables($PD) {
    global $BASEURL;
    $consumable = new Consumable($PD['id']);
    $consumable->update($PD);
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

  function deleteConsumable($gpid) {
    $q = "DELETE FROM consumables WHERE id='$gpid'";
    db_quiet($q, 1);
  }

?> 
