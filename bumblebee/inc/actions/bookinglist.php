<?php
/**
* View a list of bookings for a given user or instrument in tabular format
*
* @author     Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Actions
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

/** list of choices object */
require_once 'inc/formslib/anchortablelist.php';
/** parent object */
require_once 'inc/actions/viewbase.php';

/**
* View a list of bookings for a given user or instrument in tabular format
* @package    Bumblebee
* @subpackage Actions
*/
class ActionBookingList extends ActionViewBase {

  var $restrictions = array();
  var $startListing;
  var $stopListing;

  //restrictions that might be placed on the listing
  var $instrument;
  var $user;
  var $groups;
  var $projects;

  var $showUser = true;

  var $tableCaption = '';
  var $noneFoundNotice = '%s - %s';

  /**
  * Initialising the class
  *
  * @param  BumblebeeAuth $auth  Authorisation object
  * @param  array $pdata   extra state data from the call path
  * @return void nothing
  */
  function ActionBookingList($auth, $PDATA) {
    parent::ActionViewBase($auth, $PDATA);
    $this->mungeInputData();
  }

  function go() {
    $this->selectInstrument();
  }

  function setDefaultRestrictions($window) {
    $this->startListing = new SimpleDate(time());
    $this->startListing->dayRound();
    $this->stopListing = clone($this->startListing);
    $this->stopListing->addDays($window);
  }


  function _makeRestrictions() {
    $this->restrictions[] = 'bookwhen >= '.qw($this->startListing->dateTimeString());
    $this->restrictions[] = 'bookwhen <= '.qw($this->stopListing->dateTimeString());

    if (isset($this->user) && ! empty($this->user)) {
      $this->restrictions[] = 'userid='.qw($this->user);
      $this->showUser = false;
    }

    if (isset($this->instrument) && ! empty($this->instrument)) {
      $this->restrictions[] = 'instrument='.qw($this->instrument);
    }

/*    if (is_array($this->groups)) {
      if (count($this->groups) > 0) {
        $this->restrictions[] = 'groupid in ('.join(',', array_qw($this->groups)).')';
      } else {
        $this->restrictions[] = '0';
      }
    }*/

    if (is_array($this->projects)) {
      if (count($this->projects) > 0) {
        $this->restrictions[] = 'projectid in ('.join(',', array_qw($this->projects)).')';
      } else {
        $this->restrictions[] = '0';
      }
    }
  }

  /**
  *
  */
  function showBookings() {

    $this-> _makeRestrictions();
    $restriction = join($this->restrictions, ' AND ');

    $blist = new AnchorTableList('Bookings', T_('Upcoming bookings'), 4);
    $blist->hrefbase = makeURL('book', array('bookid'=>'__id__'));

    $blist->connectDB('bookings',
                          array('bookings.id', 'bookwhen', 'duration',
                                'instruments.name AS instrumentname', 'username', 'users.name'),
                          $restriction,
                          'bookwhen',
                          array('bookings.id', 'bookid'),
                          NULL,
                          array('users'       => 'userid=users.id',
                                'instruments' => 'instrument=instruments.id'));

    $headings = array(T_('Date &amp; Time'),
                      T_('Duration'),
                      T_('Instrument')
                      );

    if ($this->showUser) {
      $blist->setFormat('id',
                        '%s', array('bookwhen'),
                        '%s', array('duration'),
                        '%s', array('instrumentname'),
                        '%s (%s)', array('name', 'username'));
      $headings[] = T_('User');
    } else {
      $blist->setFormat('id',
                        '%s', array('bookwhen'),
                        '%s', array('duration'),
                        '%s', array('instrumentname')
                        );
      $blist->numcols--;
    }
    $blist->setTableHeadings($headings);

    echo $this->tableCaption;

    if ($blist->list->length <=0) {
      printf($this->noneFoundNotice,
                    $this->startListing->dateString(),
                    $this->stopListing->dateString());
    } else {
      echo $blist->display();
    }
  }

} // class ActionView

?>
