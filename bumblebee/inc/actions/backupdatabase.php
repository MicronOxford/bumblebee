<?php
# $Id$
# print out a login form

include_once 'inc/actions/bufferedaction.php';
include_once 'inc/statuscodes.php';

class ActionBackupDB extends BufferedAction {
  
  function ActionBackupDB($auth, $pdata) {
    parent::BufferedAction($auth, $pdata);
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
    // get a MySQL dump of the database
    global $CONFIG;
    $output = array();
    $retstring = exec($this->_mysqldump_invocation() .' 2>&1',
                $output,
                $returnError);
    $dump = join($output, "\n");
    if ($returnError) {
      return $this->unbufferForError($dump);
    } else {
      // $dump now contains the data stream.
      // let's work out a nice filename and dump it out
      $this->filename = $this->getFilename('backup', $CONFIG['database']['dbname'], 'sql');
      $this->bufferedStream = $dump;
      // the data itself will be dumped later by the action driver (index.php)
    }
  }

  function godirect() {
    $this->startOutputTextFile($filename);
    system($this->_mysqldump_invocation(),
                $returnError);
  }
  
  
  function _mysqldump_invocation() {
    global $CONFIG;
    return $CONFIG['sqldump']['mysqldump'].' '
                .$CONFIG['sqldump']['options']
                .' --host='.escapeshellarg($CONFIG['database']['dbhost'])
                .' --user='.escapeshellarg($CONFIG['database']['dbusername'])
                .' --password='.escapeshellarg($CONFIG['database']['dbpasswd'])
                .' '.escapeshellarg($CONFIG['database']['dbname'])
            ;
  }
}

?> 
