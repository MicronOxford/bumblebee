<?php
# $Id$
# edit the groups

include_once 'inc/bb/costs.php';
include_once 'inc/formslib/anchortablelist.php';
include_once 'inc/actions/actionaction.php';

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
    echo "<br /><br /><a href='$BASEURL/costs/'>Return to costs list</a><br /><br />";
    echo "<a href='$BASEURL/specialcosts/'>Edit special costs</a><br />";
    echo "<a href='$BASEURL/instrumentclass/'>Edit instrument classes</a><br />";
    echo "<a href='$BASEURL/userclass/'>Edit user classes</a><br />";
  }
  
  function mungePathData() {
    $this->PD = array();
    foreach ($_POST as $k => $v) {
      $this->PD[$k] = $v;
    }
    if (isset($this->PDATA[1]) && ! empty($this->PDATA[1])) {
      $this->PD['userclass'] = $this->PDATA[1];
    }
    echoData($this->PD, 0);
  }

  function selectUserClass() {
    global $BASEURL;
    $select = new AnchorTableList('Cost', 'Select which user class to view usage costs', 1);
    $select->connectDB('userclass', array('id', 'name'));
    //$select->list->prepend(array("-1","Create new user class"));
    $select->hrefbase = "$BASEURL/costs/";
    $select->setFormat('id', '%s', array('name'));
    //echo $select->list->text_dump();
    echo $select->display();
  }

  function editCost() {
    $classCost = new ClassCost($this->PD['userclass']);
    $classCost->update($this->PD);
    $classCost->checkValid();
    echo $this->reportAction($classCost->sync(), 
          array(
              STATUS_OK =>   ($this->PD['userclass'] < 0 ? 'Cost schedule created' : 'Cost schedule updated'),
              STATUS_ERR =>  'Cost schedule could not be changed: '.$classCost->errorMessage
          )
        );
    echo $classCost->display();
    echo '<input type="submit" name="submit" value="Update entry" />';
  }
  
}

?> 
