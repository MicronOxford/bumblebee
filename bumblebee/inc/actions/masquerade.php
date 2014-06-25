<?php
/**
* Allow the admin user to masquerade as another user to make some bookings. A bit like "su".
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Actions
*
* path (bumblebee root)/inc/actions/masquerade.php
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

/** user object */
require_once 'inc/bb/user.php';
/** list of choices */
require_once 'inc/formslib/anchortablelist.php';
/** parent object */
require_once 'inc/actions/actionaction.php';

/**
* Allow the admin user to masquerade as another user to make some bookings. A bit like "su".
*
* @package    Bumblebee
* @subpackage Actions
*/
class ActionMasquerade extends ActionAction {

  /**
  * Initialising the class
  *
  * @param  BumblebeeAuth $auth  Authorisation object
  * @param  array $pdata   extra state data from the call path
  * @return void nothing
  */
  function ActionMasquerade($auth, $pdata) {
    parent::ActionAction($auth, $pdata);
    $this->mungeInputData();
  }

  function go() {
    if (! isset($this->PD['id'])) {
      $this->selectUser();
    } elseif ($this->PD['id'] == -1) {
      $this->removeMasquerade();
      echo "<br /><br /><a href='".makeURL('masquerade')."'>".T_('Return to user list')."</a>";
    } else {
      $this->assumeMasquerade();
      echo "<br /><br /><a href='".makeURL('masquerade')."'>".T_('Return to user list')."</a>";
    }
  }

  /**
  * Print an HTML list of users to allow the user to masquerade as for making bookings
  *
  */
  function selectUser() {
    $select = new AnchorTableList(T_('Users'), T_('Select which user to masquerade as'));
    $select->connectDB('users', array('id', 'name', 'username'),'id!='.qw($this->auth->uid));
    $select->hrefbase = makeURL('masquerade', array('id'=>'__id__'));
    $select->setFormat('id', '%s', array('name'), ' %s', array('username'));
    if ($this->auth->amMasqed()) {
      $select->list->prepend(array('-1', T_('End current Masquerade')));
      echo T_('Currently wearing the mask of:')
        .'<blockquote class="highlight">'
        .xssqw($this->auth->ename).' ('.xssqw($this->auth->eusername).')</blockquote>';
    }
    echo $select->display();
  }

  /**
  * Put on the selected mask
  */
  function assumeMasquerade() {
    if ($row = $this->auth->assumeMasq($this->PD['id'])) {
      echo '<h3>'. T_('Masquerade started') .'</h3>';
      echo sprintf(
              T_('<p>The music has started and you are now wearing the mask that looks like:</p><blockquote class="highlight">%s (%s)</blockquote><p>Is that a scary thought?</p>'),
                xssqw($row['name']), xssqw($row['username'])
                );
      echo '<p>'
           . T_('When you are tired of wearing your mask, remove it by returning to the "Masquerade" menu once more.')
           .'</p>';
      echo '<p>'
          . T_('Note that even with your mask on, you can only edit/create bookings on instruments for which you have administrative rights.')
          .'</p>';
    } else {
      echo '<div class="msgerror">';
      echo '<h3>'. T_('Masquerade Error!') .'</h3>';
      echo '<p>'
         . T_('Sorry, but if you\'re comming to a masquerade ball, you really should wear a decent mask! Masquerade didn\'t start properly: mask failed to apply and music didn\'t start.')
          .'</p>';
      echo '<p>'
          . T_('Are you sure you\'re allowed to do this?')
          .'</p>';
      echo '</div>';
    }
  }

  /**
  * Remove the mask
  */
  function removeMasquerade() {
    $this->auth->removeMasq();
    echo '<h3>' . T_('Masquerade finished') . '</h3>';
    echo '<p>'
        . T_('Oh well. All good things have to come to an end. The music has stopped and you have taken your mask off. Hope you didn\'t get too much of a surprise when everyone else took their masks off too!')
        . '</p>';
  }

} //ActionMasquerade
?>
