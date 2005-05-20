<?php
# $Id$

include_once 'inc/statuscodes.php';
include_once 'inc/actions/actionaction.php';

class BufferedAction extends ActionAction  {
  var $bufferedStream;
  var $filename;
  var $errorMessage;
  var $mimetype = 'text/plain';
  
  function BufferedAction($auth, $pdata) {
    parent::ActionAction($auth, $pdata);
    $this->mungePathData();
    $this->ob_flush_ok = 0;
  }

  function unbuffer() {
    // only run this once.
    if (! $this->ob_flush_ok) {
      $this->ob_flush_ok = 1;
      ob_end_flush();
    }
  }
  
  function unbufferForError($err) {
    $this->errorMessage = '<p>'.$err.'</p>';
    $this->unbuffer();
    return STATUS_ERR;
  }
  
  function sendBufferedStream() {
    $this->outputTextFile($this->filename, $this->bufferedStream);
  }
  
  function startOutputTextFile($filename) {
    header('Content-type: '.$this->mimetype); 
    header("Content-Disposition: attachment; filename=$filename");                    
  }
  
  function outputTextFile($filename, $stream) {
    $this->startOutputTextFile($filename);
    echo $stream;
  }

  function saveTextFile($filename, $stream) {
    $fp = fopen($filename, 'w');
    fputs($fp, $stream);
    fclose($fp);
  }

  function getFilename($action, $what, $ext) {
    global $CONFIG;
    $name = $CONFIG['export']['filename'];
    $name = preg_replace('/__date__/', strftime('%Y%m%d-%H%M%S', time()), $name);
    $name = preg_replace('/__action__/', $action, $name);
    $name = preg_replace('/__what__/', $what, $name);
    return $name.'.'.$ext;
  }

} //class BufferedAction
 
?>
