<?php
# $Id$

include_once 'inc/statuscodes.php';

class ActionAction {
  var $auth;
  var $PDATA;
  var $PD;
  var $ob_flush_ok = 1;
  var $stdmessages = array(
      STATUS_NOOP => '',
      STATUS_OK   => 'Operation completed successfully',
      STATUS_WARN => 'Warnings produced during operation',
      STATUS_ERR  => 'Error. Could not complete operation',
    );
  
  var $DEBUG=0;
  
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
    if (isset($this->PDATA[1]) && $this->PDATA[1] !== '') {
      $this->PD['id'] = $this->PDATA[1];
    }
    #$PD['defaultclass'] = 12;
    echoData($this->PD);
  }
  
  /**
   *   reports to the user whether an action was successful 
   *
   *   @param $status integer  success or otherwise of action
   *   @param $messages array optional   messages to be reported, indexed by $status
   *
   *   $status codes as per statuscodes.php
   */
  function reportAction($status, $messages='') {
    //$this->log('ActionStatus: '.$status);
    #echo 'final='.$status;
    if ($status == STATUS_NOOP) return '';
    $message = '';
    if (isset($messages[$status])) {
      $message .= $messages[$status];
    } else {
      foreach ($messages as $code => $msg) {
        if ($status & $code) {
          $message .= $msg;
        }
      }
    }
    if (! $message) {
      foreach ($this->stdmessages as $code => $msg) {
        if ($status & $code) {
          $message .= $msg;
        }
      }
    }
    if (! $message) {
      $message = 'Unknown status code. Error: '. $status;
    }
    $t = '<div class="'.($status & STATUS_OK ? 'msgsuccess' : 'msgerror').'">'
         .$message
         ."</div>\n";
    return $t;
  }

  function log ($string, $prio=10) {
    if ($prio <= $this->DEBUG) {
      echo $string."<br />\n";
    }
  }
} //class ActionAction
 
?>
