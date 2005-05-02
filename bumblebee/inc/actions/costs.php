<?php
# $Id$
# edit the groups

include_once 'inc/costs.php';
include_once 'inc/dbforms/anchortablelist.php';

class ActionCosts extends ActionAction {

  function ActionCosts($auth, $pdata) {
    parent::ActionAction($auth, $pdata);
    $this->mungePathData();
  }

  function go() {
    global $BASEURL;
    if (! isset($this->PD['userclass'])) {
      $this->selectUserClass();
    } else {
      $this->editCost();
    }
    echo "<br /><br /><a href='$BASEURL/costs'>Return to costs list</a>";
  }
  
  function mungePathData() {
    $this->PD = array();
    foreach ($_POST as $k => $v) {
      $this->PD[$k] = $v;
    }
    if (isset($this->PDATA[1])) {
      $this->PD['userclass'] = $this->PDATA[1];
    }
    echoData($this->PD, 0);
  }

  function selectUserClass() {
    global $BASEURL;
    $select = new AnchorTableList("Cost", "Select which user class to view usage costs", 1);
    $select->connectDB("userclass", array("id", "name"));
    $select->list->prepend(array("-1","Create new user class"));
    $select->hrefbase = "$BASEURL/costs/";
    $select->setFormat("id", "%s", array("name"));
    //echo $select->list->text_dump();
    echo $select->display();
  }

  function editCost() {
    $classCost = new ClassCost($this->PD['userclass']);
    $classCost->update($this->PD);
    $classCost->checkValid();
    $classCost->sync();
    #echo $group->text_dump();
    echo $classCost->display();
    if ($classCost->id < 0) {
      $submit = "Create new user class";
      $delete = "0";
    } else {
      $submit = "Update entry";
      $delete = "Delete entry";
    }
    echo "<input type='submit' name='submit' value='$submit' />";
    if ($delete) echo "<input type='submit' name='delete' value='$delete' />";
  }
  
}

?> 
