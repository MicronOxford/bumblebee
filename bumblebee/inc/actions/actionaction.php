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
*
* path (bumblebee root)/inc/actions/actionaction.php
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

/** status codes for success/failure of database actions */
require_once 'inc/statuscodes.php';

/**
* Base class inherited by all actions from the action-triage
*
* An Action is a single operation requested by the user. What action is to be performed
* is determined by the action-triage mechanism in the class ActionFactory.
*
*
* Since http is a stateless protocol, what seems like just one activity
* (e.g. "edit a booking") actually includes multiple http requests
* ("show the user the current values" and "sync changes to disk"). Additionally,
* the one application suite will have many different things to do (e.g. edit/create
* users, edit/create foobar objects). There are two approaches to this multiple
* functions problem: have many .php files that are called directly by the user
* for each function (i.e. links to user.php and foobar.php) but then you can end up
* with a lot of repeated code in each file to control the page layout, load themes,
* control login etc. Alternatively, you can use just the one index.php and include
* an extra control variable in each link that decides what the script should do this time.
*
* In either case, it is convenient to have a standard "action" object that can be created
* by some ActionFactory which then obeys a standard "action" interface to then
* be used by the internals of the application.
*
* Typical usage:
* <code>
* $action = new ActionFactory($params);
* $action->go();
* </code>
*
* @abstract
* @package    Bumblebee
* @subpackage Actions
*/
class ActionAction {
  /**
  * Authorisation object
  * @var    BumblebeeAuth
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
  * The action should be read-only; no data should be changed
  * @var    boolean
  */
  var $readOnly = true;
  /**
  * Default status messages that are returned to the user.
  * @var    array
  */
  var $stdmessages;

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
  * @param  BumblebeeAuth $auth  Authorisation object
  * @param  array $pdata   extra state data from the call path
  * @return void nothing
  */
  function ActionAction($auth,$pdata) {
    $this->auth = $auth;
    $this->PDATA = $pdata;
    $this->stdmessages = array(
      STATUS_NOOP => '',
      STATUS_OK   => T_('Operation completed successfully'),
      STATUS_WARN => T_('Warnings produced during operation'),
      STATUS_ERR  => T_('Error. Could not complete operation'),
    );
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
  function mungeInputData() {
    $this->PD = $this->PDATA;
/*    foreach ($_POST as $k => $v) {
      $this->PD[$k] = $v;
    }*/
    if (isset($this->PD['id']) && $this->PD['id'] == 'showdeleted') {
      $this->PD['showdeleted'] = true;
      unset($this->PD['id']);
    }
/*    if (isset($this->PDATA[1]) && $this->PDATA[1] !== '') {
      if ($this->PDATA[1] != 'showdeleted') {
        $this->PD['id'] = $this->PDATA[1];
      } else {
        $this->PD['showdeleted'] = true;
      }
    }*/
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
      $message = T_('Unknown status code. Error:').' '. $status;
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
  *   The higher the value of $priority, the less likely the message is to
  *   be output. The normal range for priority is [1-10] with messages with
  *   ($priority <= $this->DEBUG) being displayed.
  *
  *   @param string $message  the message to be logged to the browser
  *   @param integer $priority  (optional) the priority level of the message
  */
  function log ($message, $priority=10) {
    if ($priority <= $this->DEBUG) {
      echo $message."<br />\n";
    }
  }

  /**
  * Display a message to the user explaining that the requested action cannot be
  * performed as the object is "readonly".
  *
  * This is designed to be used with the magic token verification system that requires
  * that the form that is submitted by the user contains some additional secret data that
  * was provided by the installation. This is designed to prevent attacks through getting
  * users to visit specially crafted URLs or to submit malicious forms.
  *
  * @param  string    $message    message to show to the user explaining that the action cannot be processed
  * @see    http://www.debian-administration.org/articles/465
  */
  function readOnlyError($message=null) {
    if ($message !== null) {
      print $message;
    } else {
      printf('<div class="error">%s</div>',
          T_('The requested objects are read-only as I can\'t verify the validity of the form you submitted.')
        );
    }
  }

  /**
  * Cleanse the input data of all fields except for the specifed whitelisted fields
  *
  * Removes all user-submitted data fields from the $ActionAction::PD array except for
  * the fields that are explicitly whitelisted in the function call.
  *
  * @param mixed   $fields     single field name or list of fields to whitelist
  */
  function _dataCleanse($fields) {
    if (! is_array($fields)) $fields = array($fields);
    $data = $this->PD;
    $this->PD = array();

    foreach ($fields as $f) {
      if (isset($data[$f])) {
        $this->PD[$f] = $data[$f];
      }
    }
  }

} //class ActionAction

?>
