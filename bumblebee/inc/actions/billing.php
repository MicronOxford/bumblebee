<?php
/**
* Send billing data by email
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Actions
*/

include_once 'inc/actions/export.php';
include_once 'inc/export/exporttypes.php';
include_once 'inc/exportcodes.php';
include_once 'inc/formslib/dblist.php';

/**
* Send billing data by email
*
*  Find out what sort of report is required and generate it
*/

class ActionBilling extends ActionExport {
  /**
  * should all group leaders be send the email or just the logged in user
  * @var boolean
  */
  var $emailIndividuals = false;
  /**
  * enable additional debugging information for PDF generation
  * @var boolean
  */
  var $DEBUG_PDF = false;
  
  /**
  * Initialising the class 
  * 
  * @param  BumbleBeeAuth $auth  Authorisation object
  * @param  array $pdata   extra state data from the call path
  * @return void nothing
  */
  function ActionBilling($auth, $pdata) {
    parent::ActionExport($auth, $pdata);
    //$this->format = EXPORT_FORMAT_VIEW;
    $this->format = EXPORT_FORMAT_PDF;
    $this->_verb = 'billing';
    $this->PD['what'] = 'billing';
    $this->reportSet = array('group' => 'users', 
                             'bookingbilling' => 'groups',
                             'consumablegroup' => 'users',
                             'billing' => '');
  }
  
  function mungePathData() {
    $this->emailIndividuals = issetSet($_POST, 'emailToGroups');
    //echo $this->emailIndividuals ? 'true' : 'false';
    parent::mungePathData();
  }

  /**
  * convenience function to generate a submit button
  */  
  function _goButton() {
    echo '<label>'
          .'<input type="radio" name="emailToGroups" value="0" checked="checked" />'
          .' Email report to me ('.$this->auth->email.')'
        .'</label><br/>';
    echo '<label>'
          .'<input type="radio" name="emailToGroups" value="1" />'
          .' Email report to each group leader'
        .'</label><br/><br/>';
    parent::_goButton();
  }
  
  /**
  * get all the data and send it back to the user
  */
  function returnExport() {
    //$this->DEBUG=10;
    $lists = array();
    $pdfs = array();
    $nopdfs = array();
    foreach ($this->reportSet as $report => $pivot) {
      $this->PD['pivot'] = $pivot;
      $lists[] = $this->_getDataList($report);
    }
    $groups = $this->_limitationSet(array('groups'), count($this->reportSet)-1, false);
    #preDump($groups);
    $noData = true;
    foreach ($groups['groups'] as $g) { 
      $noGroupData = true;
      $exportArray = new ArrayExport($lists[0], '');
      $exportArray->header = $this->_reportHeader();
      $exportArray->author = $this->auth->name;
      for ($r = 0; $r < count($lists); $r++) {
        // put a restriction on what to return for this incarnation
        $l = $lists[$r];
        $l->unionrestriction[] = 'groups.id = '.$g;   // $g is already qw($g)
        $l->restriction[] = 'groups.id = '.$g;   // $g is already qw($g)
        $l->fill();
        $noData = $noData && ! count($l->data);
        $noGroupData = $noGroupData && ! count($l->data);
        $this->log('Found '. count($l->data) .' rows');
        
        if (count($l->data)) {
          // start rendering the data
          $l->outputFormat = $this->format;
          $l->formatList();   
          $this->log('Creating new AE');
          //preDump($lists[$r]);
          $ea = new ArrayExport($l, $l->breakfield);
          $ea->header = $this->_reportHeader();
          $ea->makeExportArray();
          $this->log('Appending EA');
          #preDump(count($exportArray->export));
          $exportArray->appendEA($ea);
          #preDump(count($exportArray->export));
        }
      }
      //preDump($exportArray);
      $who = quickSQLSelect('groups', 'id', unqw($g));
      if (! $noGroupData) {
        $filename = $this->_getFilename($who['name']);
        $pdfExport = $this->_preparePDFExport($exportArray);
        $pdfExport->filename = $filename;
        $pdfExport->useBigTable = false;
        if ($this->DEBUG_PDF) {
          $pdfExport->writeToFile = true;
        } else {
          $pdfExport->writeToFile = false;
        }
        $pdfExport->makePDFBuffer();
        $pdfs[] = array('filename'  => $filename, 
                        'mimetype'  => $this->mimetype, 
                        'data'      => $pdfExport->export,
                        'groupdata' => $who);
      } else {
        // if there was no data, then record that fact for later reporting.
        $nopdfs[] = array('groupdata' => $who);
      }
    }
    if ($noData) {
      return $this->unbufferForError('<p>No data found for those criteria</p>');
    } else {
      // dump out the files via email or a zip file....
      $this->unbuffer();
      if ($this->emailIndividuals) {
        $success = 1;
        for ($group=1; $group<count($pdfs); $group++) {
          $success &= $this->_sendPDFbyEmail($pdfs[$group]['groupdata']['name'],
                                            $pdfs[$group]['groupdata']['email'],
                                            $pdfs[$group]['groupdata'],
                                            array($pdfs[$group]));
        }
      } else {
        $grouplist = array();
        for ($group=1; $group<count($pdfs); $group++) { 
          $grouplist[] = $pdfs[$group]['groupdata']['longname'];
        }
        $gpinfo = array('name' => $this->auth->name, 'longname' => "\n".join($grouplist, "\n"));
        $success = $this->_sendPDFbyEmail($this->auth->name, $this->auth->email, $gpinfo, $pdfs);
      }
      if ($success) {
        $s = '<div class="msgsuccess">'
            .'Reports sent by email.'.'</div>';
        echo $s;
      } else {
        $s = '<div class="msgerror">'
            .'Unknown error sending reports by email.'.'</div>';
        echo $s;
      }
      if (count($nopdfs)) {
        $s = '<div class="msgerror">'
            .'<b>Note:</b> no billing data was found for some groups.<br/><br/>';
        foreach ($nopdfs as $group) {
          $s .= $group['groupdata']['name'].': '.$group['groupdata']['longname'].'<br/>';
        }
        $s .= '</div>';
        echo $s;
      }
      echo '<div>Regenerate reports:<br/><br/>';
      $this->_goButton();
      echo '</div>';
    }
  }
  
  /**
  * obtain an appropriate filename for the data export
  */
  function _getFilename($who) {
    global $CONFIG;
    $name = $CONFIG['billing']['filename'];
    $this->mimetype = 'application/pdf';
    $who = urlencode($who);
    $name = preg_replace('/__date__/', strftime('%Y%m%d-%H%M%S', time()), $name);
    $name = preg_replace('/__who__/', $who, $name);
    return $name;
  }

  /**
  * send the PDF to the appropriate person by email
  * the logged in user is always included in the To list for their records
  *
  * @param string $toName  name of the person to receive the email
  * @param string $toEmail  email addr of recipient
  * @param string $group   name of the group for which this report is being generated
  * @param string $data    PDF data to be sent
  */
  function _sendPDFbyEmail($toName, $toEmail, $group, $data) {
    global $CONFIG;
    $eol = "\r\n";
    $from = $CONFIG['billing']['emailFromName']
            .' <'.$CONFIG['main']['SystemEmail'].'>';
    $replyto = $this->auth->name.' <'.$this->auth->email.'>';
    $cc   = $this->auth->name.' <'.$this->auth->email.'>';    // CC a copy to the sender
    $to   = $toName.' <'.$toEmail.'>';
    srand(time());
    $id   = '<bumblebee-'.time().'-'.rand().'@'.$_SERVER['SERVER_NAME'].'>';
    
    $headers  = 'From: '.$from .$eol;
    //$headers .= 'To: '.$to .$eol;    // automatically added by mail()
    $headers .= 'Reply-To: '.$replyto.$eol;
    $headers .= 'Message-id: ' .$id .$eol;

    $start = $this->_daterange->getStart();
    $stop  = $this->_daterange->getStop();
    
    $textmessage = $this->_getEmailText($group, $start->datestring, $stop->datestring);
    
    //$textmessage = 'Please find attached PDF billing summaries for instrument usage.';
    $subject = ($CONFIG['billing']['emailSubject'] 
                    ? $CONFIG['billing']['emailSubject'] : 'Instrument usage summary');

    //Having read in the data for the file attachment, 
    //we need to set up the message headers to send a multipart/mixed message:

    // Generate a boundary string
    $semi_rand = md5(time());
    $mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";
 
    // Add the headers for a file attachment
    $headers .= 'MIME-Version: 1.0'.$eol
               .'Content-Type: multipart/mixed;'.$eol
               .' boundary="'.$mime_boundary.'"'.$eol;

    // Add a multipart boundary above the plain message
    $message = 'This is a multi-part message in MIME format.'.$eol .$eol
               .'--'.$mime_boundary.$eol
               .'Content-Type: text/plain; charset="iso-8859-1"'.$eol
               .'Content-Transfer-Encoding: 7bit' .$eol.$eol
               .$textmessage .$eol.$eol;

    for ($att=0; $att<count($data); $att++) {
      //echo strlen($data[$att]['data']);
      // Base64 encode the file data
      $attdata = chunk_split(base64_encode($data[$att]['data']));
  
      // Add file attachment to the message
      $message .= '--'.$mime_boundary . $eol
                 .'Content-Type: '.$data[$att]['mimetype'].';'.$eol
                 .' name="'.$data[$att]['filename'].'"'.$eol
                 .'Content-Disposition: attachment;'.$eol
                 .' filename="'.$data[$att]['filename'].'"'.$eol
                 .'Content-Transfer-Encoding: base64'.$eol.$eol
                 .$attdata . $eol.$eol;
    }
    $message .= '--'.$mime_boundary.'--'.$eol;

    // Send the message
/*    $fh = fopen('/tmp/mail.mbox', 'w');
    fwrite($fh, $headers.$eol.$message);
    fclose($fh);
    return 1;*/
    $ok = @mail("$to, $cc", $subject, $message, $headers);
    #print "sent mail to $to, $cc\n";
    return $ok;
  }
  
  /**
  * Reads the boilerplate text for the email and customise it for this email
  *
  * @param string $group   name of the group for which this report is being generated
  * @param string $start   the start date for the report
  * @param string $stop    the stop date for the report
  */
  function _getEmailText($group, $start, $stop) {
    global $CONFIG;
    global $BASEURL;
    $fh = fopen($CONFIG['billing']['emailTemplate'], 'r');
    $txt = fread($fh, filesize($CONFIG['billing']['emailTemplate']));
    fclose($fh);
    $replace = array(
            '/__name__/'      => $group['name'],
            '/__groupname__/' => $group['longname'],
            '/__start__/'     => $start,
            '/__stop__/'      => $stop,
            '/__host__/'      => 'http://'.$_SERVER['SERVER_NAME'].$BASEURL
                    );
    $txt = preg_replace(array_keys($replace),
                        array_values($replace),
                        $txt);
    return $txt;
  }
  
} // ActionBilling

?>