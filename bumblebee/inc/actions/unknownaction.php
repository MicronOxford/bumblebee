<?php
# $Id$
# an unknown action... ERROR!

class ActionUnknown extends ActionAction {
  var $action;

  function ActionUnknown($action) {
    parent::ActionAction('','');
    $this->action = $action;
  }

  function go() {
    global $ADMINEMAIL;
    echo "<h2>Error</h2>"
    ."<p>An unknown error occurred. I was asked to perform the "
    ."action '$this->action' but I don't know how to do that.</p>"
    ."<p>Please contact <a href='mailto:$ADMINEMAIL'>the system "
    ."administrator</a> to report this error.</p>";
  }
}
?> 
