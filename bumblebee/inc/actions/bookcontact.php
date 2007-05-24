<?php
/**
* Let the user either log in or fill in a contact form to make a booking.
*
* @author     Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Actions
*
* path (bumblebee root)/inc/actions/bookcontact.php
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

require_once 'inc/bb/configreader.php';

/** uses the login class for the login form */
require_once 'inc/actions/login.php';
/** date/time handline */
require_once 'inc/date.php';

/**
* Let the user either log in or fill in a contact form to make a booking.
* @package    Bumblebee
* @subpackage Actions
*/
class ActionBookContact extends ActionAction {

  /**
  * Initialising the class
  *
  * @param  BumblebeeAuth $auth  Authorisation object
  * @param  array $pdata   extra state data from the call path
  * @return void nothing
  */
  function ActionBookContact($auth, $PDATA) {
    parent::ActionAction($auth, $PDATA);
    $this->mungeInputData();
  }

  function go() {
    if (isset($this->PD['contactme']) && ! $this->readOnly) {
      $this->sendContactRequest();
    } else {
      // show both a contact form and a change-user form
      $this->showContactLoginForm();
    }
  }

  function sendContactRequest() {
    if ($this->sendEmail()) {
      echo '<div>'
          .T_('Thank you for your enquiry; it will be dealt with soon.')
          .'</div>';
    } else {
      echo '<div>'
          .T_('I\'m sorry, I couldn\'t send your request. Please try again or contact the system administrator directly.')
          .'</div>';
    }
  }

  function sendEmail() {
    $conf = ConfigReader::getInstance();
    $instrument = quickSQLSelect('instruments', 'id', $this->PD['contact-instrid']);
    $emails = array();
    if (empty($instrument['supervisors'])) {
      $emails[0] = $conf->AdminEmail;
    } else {
      foreach(preg_split('/,\s*/', $instrument['supervisors']) as $username) {
        preDump($username);
        $user = quickSQLSelect('users', 'username', $username);
        $emails[] = $user['email'];
      }
    }

    $eol = "\r\n";
    $from = $instrument['name'].' '.$conf->value('instruments', 'emailFromName')
            .' <'.$conf->value('main', 'SystemEmail').'>';
    $replyto = $this->PD['contact-name'].' <'.$this->PD['contact-email'].'>';
    $to   = join($emails, ',');
    srand(time());
    $id   = '<bumblebee-'.time().'-'.rand().'@'.$_SERVER['SERVER_NAME'].'>';

    $headers  = 'From: '.$from .$eol;
    $headers .= 'Reply-To: '.$replyto.$eol;
    $headers .= 'Message-id: ' .$id .$eol;
    $subject = $instrument['name']. ': '. ($conf-value('instruments', 'emailSubject')
                    ? $conf->value('instruments', 'emailSubject') : 'Instrument booking notification');
    $message = $this->_getEmailText($this->PD);

    $ok = @ mail($to, $subject, $message, $headers);

    return $ok;

  }

  function _getEmailText($data) {
    $conf = ConfigReder::getInstance();
    $fh = fopen($conf->value('instruments', 'emailRequestTemplate'), 'r');
    $txt = fread($fh, filesize($conf->value('instruments', 'emailRequestTemplate')));
    fclose($fh);
    $replace = array(
            '/__instrumentname__/'      => $data['contact-instrument'],
            '/__start__/'               => $data['contact-start'],
            '/__finish__/'              => $data['contact-stop'],
            '/__name__/'                => $data['contact-name'],
            '/__email__/'               => $data['contact-email'],
            '/__phone__/'               => $data['contact-phone'],
            '/__organisation__/'        => $data['contact-organisation'],
            '/__comments__/'            => $data['contact-comments'],
            '/__host__/'                => makeAbsURL()
                    );
    $txt = preg_replace(array_keys($replace),
                        array_values($replace),
                        $txt);
    return $txt;
  }


  function showContactLoginForm() {
    echo '<p>'
        .T_('Please login to continue or use this form to send us an email to request the instrument')
        .'</p>';

    echo '<fieldset>';
    echo '<h2>Login details</h2>';
    ActionPrintLoginForm::printLoginForm();
    $data = $this->PD;
    $data['changeuser']=1;
    ActionPrintLoginForm::printDataReflectionForm($data);
    echo '<input type="hidden" name="changeuser" value="1" />';
    echo '<input type="hidden" name="forceaction" value="book" />';
    echo '</fieldset>';

    echo '<br /><br />';
    echo formEnd();
    echo formStart(makeURL('bookcontact'), $this->auth->makeValidationTag(), 'contactform', false);

    echo '<fieldset>';
    echo '<h2>Contact us</h2>';
    $this->printContactForm();
    echo '</fieldset>';

    echo '<br /><br />';
  }

  function printContactForm() {
    $instrument = $this->PD['instrid'];
    $row = quickSQLSelect('instruments', 'id', $instrument);
    $start = new SimpleDate($this->PD['startticks']);
    $stop  = new SimpleDate($this->PD['stopticks']);

    printf ('<input type="hidden" name="contact-instrid" value="%s" />', $instrument);
    print  '<table>';
    printf ('<tr><td>%s</td><td><input type="text" name="contact-name" /></td></tr>', T_('Name'));
    printf ('<tr><td>%s</td><td><input type="text" name="contact-email" /></td></tr>', T_('Email address'));
    printf ('<tr><td>%s</td><td><input type="text" name="contact-phone" /></td></tr>', T_('Phone'));
    printf ('<tr><td>%s</td><td><input type="text" name="contact-organisation" /></td></tr>', T_('Organisation'));
    printf ('<tr><td>%s</td><td><input type="hidden" name="contact-instrument" value="%s" />%s</td></tr>', T_('Instrument'), $row['longname'], $row['longname']);
    printf ('<tr><td>%s</td><td><input type="hidden" name="contact-start" value="%s" />%s</td></tr>', T_('Start time'), $start->datetimestring(), $start->datetimestring());
    printf ('<tr><td>%s</td><td><input type="hidden" name="contact-stop" value="%s" />%s</td></tr>', T_('Finish'), $stop->datetimestring(), $stop->datetimestring());
    printf ('<tr><td>%s</td><td><textarea rows="10" cols="40" name="contact-comments"></textarea></td></tr>', T_('Comments'));
    printf ('<tr><td></td><td><input type="submit" name="contactme" value="%s" /></td></tr>', T_('Send requeset'));
    print '</table>';
  }


} // class ActionView

?>
