<?php
# $Id$

class ActionAction {
  var $auth;
  var $PDATA;
  var $PD;
  
  function ActionAction($auth,$pdata) {
    $this->auth = $auth;
    $this->PDATA = $pdata;
  }

  function go() {
  }
  
  function mungePathData() {
    $this->PD = array();
    foreach ($_POST as $k => $v) {
      $this->PD[$k] = $v;
    }
    if (isset($this->PDATA[1])) {
      $this->PD['id'] = $this->PDATA[1];
    }
    #$PD['defaultclass'] = 12;
    echoData($this->PD);
  }

} //class ActionAction
 
?>
