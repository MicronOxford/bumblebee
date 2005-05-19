<?php
# $Id$
# print out a login form

include_once 'inc/actions/actionaction.php';
include_once 'inc/statuscodes.php';

class ActionBackupDB extends ActionAction {
  var $bufferedStream;
  var $filename;
  var $errorMessage;

  function ActionBackupDB($auth, $pdata) {
    parent::ActionAction($auth, $pdata);
    $this->ob_flush_ok = 0;
  }

  function go() {
    $success = $this->makeDump();
    //echo $success;
    //echo $this->errorMessage;
    echo $this->reportAction($success, 
              array(STATUS_ERR =>  'Error making backup: '.$this->errorMessage
                   )
               );
  }

  
  function makeDump() {
    global $CONFIG;
    // get a MySQL dump of the database
    $output = array();
    $retstring = exec('mysqldump -h '.$CONFIG['database']['dbhost']
                .' --user='.escapeshellarg($CONFIG['database']['dbusername'])
                .' --password='.escapeshellarg($CONFIG['database']['dbpasswd'])
                .' '.escapeshellarg($CONFIG['database']['dbname'])
                .' 2>&1',
                $output,
                $returnError);
    $dump = join($output, "\n");
    if ($returnError) {
      $this->errorMessage = '<p>'.$dump.'</p>';
      $this->ob_flush_ok = 1;
      ob_end_flush();
      return STATUS_ERR;
    } else {
      // $dump now contains the filename.
      // let's work out a nice filename and dump it out
      $this->filename = 'bumblebee-backup-'.$CONFIG['database']['dbname'].'-'
                      .strftime('%Y%m%d-%H%M%S', time())
                      .'.sql';
      $this->bufferedStream = $dump;
      //echo $dump;
      //$this->outputTextFile($filename, $dump);
      //$this->saveTextFile('/tmp/'.$filename, $dump);
    }
  }

  function sendBufferedStream() {
    $this->outputTextFile($this->filename, $this->bufferedStream);
  }
  
  function godirect() {
    $this->startOutputTextFile($filename);
    system('mysqldump -h localhost --user='.escapeshellarg($CONFIG['database']['dbusername'])
                .' --password='.escapeshellarg($CONFIG['database']['dbpasswd'])
                .' '.escapeshellarg($CONFIG['database']['dbname']),
                $returnError);
  }
  
  function startOutputTextFile($filename) {
    // Output a text file
    //ob_end_clean();
    header('Content-type: text/plain'); 
    header("Content-Disposition: attachment; filename=$filename");                    
  }
  
  function outputTextFile($filename, $stream) {
    // Output a text file
    //ob_end_clean();
    header("Content-type: text/plain"); 
    header("Content-Disposition: attachment; filename=$filename");                    
    echo $stream;
  }

  function saveTextFile($filename, $stream) {
    $fp = fopen($filename, 'w');
    fputs($fp, $stream);
    fclose($fp);
  }

  
}

?> 
