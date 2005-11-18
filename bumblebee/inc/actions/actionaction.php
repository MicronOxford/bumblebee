<?php
/**
* Base class inherited by all actions from the action-triage
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Actions
* @abstract
*/

include_once 'inc/statuscodes.php';

/**
* Base class inherited by all actions from the action-triage
*  
* An Action is a single operation requested by the user. What action is to be performed
* is determined by the action-triage mechanism in the class ActionFactory.
*/
class ActionAction {
  /**
  * Authorisation object
  * @var    BumbleBeeAuth
  */
  var $auth;
  /**
  * Unparsed path data from the CGI call
  * @var    array
  */
  var $PDATA;
  /**
  * Parsed input data (combined PATH data and POST data)
  * @var    array
  */
  var $PD;
  /**
  * Permit normal HTML output
  *
  * Allows previous output (from the HTML template) to be flushed or suppress it so 
  * a PDF can be output.
  * @var    boolean
  */
  var $ob_flush_ok = 1;
  /**
  * Default status messages that are returned to the user.
  * @var    array
  */
  var $stdmessages = array(
      STATUS_NOOP => '',
      STATUS_OK   => 'Operation completed successfully',
      STATUS_WARN => 'Warnings produced during operation',
      STATUS_ERR  => 'Error. Could not complete operation',
    );
  
  /**
  * Turn on debugging messages from the Action* classes
  * @var    integer
  */
  var $DEBUG=0;
  
  /**
  * Initialising the class 
  * 
  * Variable assignment only in this constructor, the child class would normally:
  * - use parent's constructor
  * - parse input data 
  * 
  * @param  BumbleBeeAuth $auth  Authorisation object
  * @param  array $pdata   extra state data from the call path
  * @return void nothing
  */
  function ActionAction($auth,$pdata) {
    $this->auth = $auth;
    $this->PDATA = $pdata;
  }

  /**
  * Actually perform the action that this Action* class is to perform
  * 
  * this is an abstract class and this function <b>must</b> be overridden
  * 
  * @return void nothing
  */
  function go() {
  }
  
  /**
  * Parse the input data sources
  * 
  * @return void nothing
  */
  function mungePathData() {
    $this->PD = array();
    foreach ($_POST as $k => $v) {
      $this->PD[$k] = $v;
    }
    if (isset($this->PDATA[1]) && $this->PDATA[1] !== '') {
      if ($this->PDATA[1] != 'showdeleted') {
        $this->PD['id'] = $this->PDATA[1];
      } else {
        $this->PD['showdeleted'] = true;
      }
    }
    #$PD['defaultclass'] = 12;
    echoData($this->PD);
  }
  
  /**
  *  Reports to the user whether an action was successful 
  *
  *   @param integer $status   success or otherwise of action
  *   @param array $messages  (optional) messages to be reported, indexed by $status
  *
  *   $status codes as per file statuscodes.php
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
  
  /**
  * Select which item to edit for this action
  * 
  * @param boolean $deleted  (optional) show deleted items 
  * @return void nothing
  */
  function select($deleted=false) {
  }
  
  /**
  * Edit the selected item
  * 
  * @return void nothing
  */
  function edit() {
  }
  
  /**
  * Delete the selected item
  * 
  * @return void nothing
  */
  function delete() {
  }

  /**
  *  Generic logging function for use by all Action classes
  *
  *   @param string $message  the message to be logged to the browser
  *   @param integer $priority  (optional) the priority level of the message
  *
  *   The higher the value of $priority, the less likely the message is to
  *   be output. The normal range for priority is [1-10] with messages with
  *   ($priority <= $this->DEBUG) being displayed.
  */
  function log ($string, $prio=10) {
    if ($prio <= $this->DEBUG) {
      echo $string."<br />\n";
    }
  }


} //class ActionAction
 
?>
