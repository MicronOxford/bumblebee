<?php
/**
* View a list of instruments that are available
*
* @author     Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Actions
*
* path (bumblebee root)/inc/actions/view.php
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

/** list of choices object */
require_once 'inc/formslib/anchortablelist.php';
/** translated string type */
require_once 'inc/formslib/bbstring.php';
/** parent object */
require_once 'inc/actions/viewbase.php';
/** list of bookings */
require_once 'inc/actions/bookinglist.php';

/**
* View a list of instruments so the user can view and make bookings
* @package    Bumblebee
* @subpackage Actions
*/
class ActionView extends ActionViewBase {

  var $defaultListingLength = 14;

  /**
  * Initialising the class
  *
  * @param  BumblebeeAuth $auth  Authorisation object
  * @param  array $pdata   extra state data from the call path
  * @return void nothing
  */
  function ActionView($auth, $PDATA) {
    parent::ActionViewBase($auth, $PDATA);
    $this->mungeInputData();
  }

  function go() {
    $this->selectInstrument();

	if(!$this->auth->anonymous) {
		$this->showMyBookings();
		$this->showMyProjectBookings();
		$this->showMyGroupBookings();
	} //end if
  }

  /**
  * Select which instrument for which the calendar should be displayed
  */
  function selectInstrument() {
    $conf = ConfigReader::getInstance();
    
    $instrselect = new AnchorTableList('Instrument', T_('Select which instrument to view'), 4);

    $headings = array(new bbString('name', T_('name')),
                      new bbString('longname', T_('description')),
                      new bbString('location', T_('location')),
                      new bbString('view', T_('view')),
                     );

    $instrselect->setTableHeadings($headings);
    $instrselect->sortByHeadings(true, $this->PD);
    $instrselect->sortByHref = makeUrl('view', array($instrselect->sortbyKey => '__sortby__'));

    if ($this->auth->permitted(BBROLE_VIEW_LIST)) {
      $instrselect->connectDB('instruments',
                            array('id', 'name', 'longname', 'location')
                            );
    } else {
      $instrselect->connectDB('instruments',
                            array('id', 'name', 'longname', 'location'),
                            'userid='.qw($this->auth->getEUID()),
                            'name',
                            'id',
                            NULL,
                            array('permissions'=>'instrid=id'));
    }
    $instrselect->hrefbase = makeURL('calendar', array('instrid'=>'__id__'));
    
    $listurl   = preg_replace('/__id__/', '%d', makeURL('calendar', array('instrid'=>'__id__', 'listview'=>1)));
    $linksview = sprintf("</a><a href='%s' style='display:inline'><img src='%s/theme/images/view_icon.png' width='16' height='16' alt='%s' /></a>&nbsp;<a href='%s' style='display:inline'><img src='%s/theme/images/view_text.png' width='16' height='16' alt='%s' /></a><a>",
                          $listurl, $conf->BasePath, T_("calendar view"), $listurl, $conf->BasePath, T_("list view")
                        );
    $instrselect->setFormat('id', '%s', array('name'),
                                  ' %50.50s', array('longname'),
                                  ' %20.20s', array('location'),
#                                  '<a href="%s">list</a>', array(makeURL('calendar', array('instrid'=>'__id__', 'listview'=>1)))
                                  $linksview, array('id', 'id')
                            );
    echo '<h2>' . T_('Please select an instrument to view') . '</h2>';
    echo $instrselect->display();
  }

  function showMyBookings() {
    $list = new ActionBookingList($this->auth, null);
    $list->tableCaption = '<h2>' . T_('My bookings') . '</h2>';
    $list->noneFoundNotice = T_('You have no bookings between %s and %s.');
    $list->user = $this->auth->getEUID();
    $list->setDefaultRestrictions($this->defaultListingLength);
    $list->showBookings();
  }

  function showMyGroupBookings() {
    global $TABLEPREFIX;

    $list = new ActionBookingList($this->auth, null);
    $list->tableCaption = '<h2>' . T_('My group\'s bookings') . '</h2>';
    $list->noneFoundNotice = T_('Your group has no bookings between %s and %s.');

    $projects = array();
    $userid = $this->auth->getEUID();
    $q = "SELECT userprojects.projectid as projectid "
        ."FROM {$TABLEPREFIX}projectgroups AS projectgroups "
        ."LEFT JOIN {$TABLEPREFIX}userprojects AS userprojects "
            ."ON userprojects.projectid=projectgroups.projectid "
        ."WHERE userprojects.userid=".qw($userid);
    $sql = db_get($q, false);
    while ($g = db_fetch_array($sql)) {
      $projects[] = $g['projectid'];
    }
    $list->projects = $projects;

    $list->setDefaultRestrictions($this->defaultListingLength);
    $list->showBookings();
  }

  function showMyProjectBookings() {
    global $TABLEPREFIX;

    $list = new ActionBookingList($this->auth, null);
    $list->tableCaption = '<h2>' . T_('My project\'s bookings') . '</h2>';
    $list->noneFoundNotice = T_('Your project has no bookings between %s and %s.');

    $projects = array();
    $userid = $this->auth->getEUID();
    $q = "SELECT projectid FROM {$TABLEPREFIX}userprojects WHERE userid=".qw($userid);
    $sql = db_get($q, false);
    while ($g = db_fetch_array($sql)) {
      $projects[] = $g['projectid'];
    }
    $list->projects = $projects;
    $list->setDefaultRestrictions($this->defaultListingLength);
    $list->showBookings();
  }

} // class ActionView

?>
