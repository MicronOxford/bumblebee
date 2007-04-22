<?php
/**
* Permission codes for actions
*
* @author     Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Misc
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

// Activity:
/** Activity: no-op  */
define('BBROLE_NONE',                    $role = 1);
/** Activity: change own password */
define('BBROLE_PASSWD',                 ($role<<=1));
/** Activity: log out */
define('BBROLE_LOGOUT',                 ($role<<=1));
/** Activity: view list  */
define('BBROLE_VIEW_LIST',              ($role<<=1));
/** Activity: view instrument calendar */
define('BBROLE_VIEW_CALENDAR',          ($role<<=1));
/** Activity: view instrument booking sheet into the distant future */
define('BBROLE_VIEW_CALENDAR_FUTURE',   ($role<<=1) | BBROLE_VIEW_CALENDAR);
/** Activity: view instrument bookings */
define('BBROLE_VIEW_BOOKINGS',          ($role<<=1));
/** Activity: view admin details of bookings on the instrument */
define('BBROLE_VIEW_BOOKINGS_DETAILS',  ($role<<=1) | BBROLE_VIEW_BOOKINGS);
/** Activity: make bookings */
define('BBROLE_MAKE_BOOKINGS',          ($role<<=1));
/** Activity: Book instrument any time into the future */
define('BBROLE_MAKE_BOOKINGS_FUTURE',   ($role<<=1) | BBROLE_MAKE_BOOKINGS);
/** Activity: edit all bookings on instrument */
define('BBROLE_EDIT_ALL',               ($role<<=1) | BBROLE_MAKE_BOOKINGS);
/** Activity: Book instrument without timeslot restrictions */
define('BBROLE_MAKE_BOOKINGS_FREE',     ($role<<=1) | BBROLE_MAKE_BOOKINGS);
/** Activity: Delete own bookings with appropriate notice */
define('BBROLE_UNBOOK',                 ($role<<=1));
/** Activity: Delete own bookings without restrictions for appropriate notice  */
define('BBROLE_UNBOOK_PAST',            ($role<<=1) | BBROLE_UNBOOK);
/** Activity: Delete others' bookings */
define('BBROLE_UNBOOK_OTHER',           ($role<<=1) | BBROLE_UNBOOK);

/** Activity: edit groups */
define('BBROLE_ADMIN_GROUPS',           ($role<<=1));
/** Activity: edit projects */
define('BBROLE_ADMIN_PROJECTS',         ($role<<=1));
/** Activity: edit users */
define('BBROLE_ADMIN_USERS',            ($role<<=1));
/** Activity: edit instruments */
define('BBROLE_ADMIN_INSTRUMENTS',      ($role<<=1));
/** Activity: edit consumables */
define('BBROLE_ADMIN_CONSUMABLES',      ($role<<=1));
/** Activity: record consumable usage */
define('BBROLE_ADMIN_CONSUME',          ($role<<=1));
/** Activity: edit costs */
define('BBROLE_ADMIN_COSTS',            ($role<<=1));
/** Activity: view deleted bookings */
define('BBROLE_ADMIN_DELETEDBOOKINGS',  ($role<<=1));
/** Activity: masquerade as another */
define('BBROLE_ADMIN_MASQ',             ($role<<=1));
/** Activity: collect email lists  */
define('BBROLE_ADMIN_EMAILLIST',        ($role<<=1));
/** Activity: export data */
define('BBROLE_ADMIN_EXPORT',           ($role<<=1));
/** Activity: send out billing reports  */
define('BBROLE_ADMIN_BILLING',          ($role<<=1));
/** Activity: backup database */
define('BBROLE_ADMIN_BACKUPDB',         ($role<<=1));
/** Activity: change system config */
define('BBROLE_ADMIN_CONFIG',           ($role<<=1));

/** Activity: Admin activity flag */
define('BBROLE_ADMIN_BASE',             BBROLE_ADMIN_GROUPS);


// SYSTEM LEVEL FUNCTIONS: NORMAL USERS
/** Permission: user can view all instruments on list */
define('BBPERM_USER_VIEW_LIST_ALL',       BBROLE_VIEW_LIST);
/** Permission: user can view all instrument calendars */
define('BBPERM_USER_VIEW_CALENDAR_ALL',   BBROLE_VIEW_CALENDAR);
/** Permission: user can view all instrument bookings */
define('BBPERM_USER_VIEW_BOOKINGS_ALL',   BBROLE_VIEW_BOOKINGS);
/** Permission: user do all VIEW actions on all instruments */
define('BBPERM_USER_VIEW_ALL',            BBPERM_USER_VIEW_LIST_ALL | BBPERM_USER_VIEW_CALENDAR_ALL | BBPERM_USER_VIEW_BOOKINGS_ALL);
/** Permission: user can make bookings */
define('BBPERM_USER_MAKE_BOOKINGS_ALL',   BBROLE_MAKE_BOOKINGS);
/** Permission: user can change their own password */
define('BBPERM_USER_PASSWD',              BBROLE_PASSWD);
/** Permission: user log out */
define('BBPERM_USER_LOGOUT',              BBROLE_LOGOUT);
/** Permission: sensible, basic user permissions */
define('BBPERM_USER_BASIC',               BBPERM_USER_LOGOUT | BBPERM_USER_PASSWD);
/** Permission: readonly user permissions */
define('BBPERM_USER_READONLY',            BBPERM_USER_LOGOUT);

// SYSTEM LEVEL FUNCTIONS: ADMIN USERS
/** Permission: Permission to edit groups */
define('BBPERM_ADMIN_GROUPS',             BBROLE_ADMIN_GROUPS);
/** Permission: Permission to edit projects */
define('BBPERM_ADMIN_PROJECTS',           BBROLE_ADMIN_PROJECTS);
/** Permission: Permission to edit users */
define('BBPERM_ADMIN_USERS',              BBROLE_ADMIN_USERS);
/** Permission: Permission to edit instruments */
define('BBPERM_ADMIN_INSTRUMENTS',        BBROLE_ADMIN_INSTRUMENTS);
/** Permission: Permission to edit consumables */
define('BBPERM_ADMIN_CONSUMABLES',        BBROLE_ADMIN_CONSUMABLES);
/** Permission: Permission to record consumable usage */
define('BBPERM_ADMIN_CONSUME',            BBROLE_ADMIN_CONSUME);
/** Permission: Permission to edit costs */
define('BBPERM_ADMIN_COSTS',              BBROLE_ADMIN_COSTS);
/** Permission: Permission to view deleted bookings */
define('BBPERM_ADMIN_DELETEDBOOKINGS',    BBROLE_ADMIN_DELETEDBOOKINGS);
/** Permission: user can masquerade as another user on any instrument */
define('BBPERM_ADMIN_MASQ',               BBROLE_ADMIN_MASQ);
/** Permission: Permission to collect email lists  */
define('BBPERM_ADMIN_EMAILLIST',          BBROLE_ADMIN_EMAILLIST);
/** Permission: Permission to export data */
define('BBPERM_ADMIN_EXPORT',             BBROLE_ADMIN_EXPORT);
/** Permission: Permission to send out billing reports  */
define('BBPERM_ADMIN_BILLING',            BBROLE_ADMIN_BILLING);
/** Permission: Permission to backup database */
define('BBPERM_ADMIN_BACKUPDB',           BBROLE_ADMIN_BACKUPDB);
/** Permission: Permission to change system settings */
define('BBPERM_ADMIN_CONFIG',             BBROLE_ADMIN_CONFIG);

/** Permission: Admin user can do anything */
define('BBPERM_ADMIN_ALL',               4294967295);    // 2^32 - 1

// FINE-GRAINED INSTRUMENT PERMISSIONS
/** Permission: View instrument booking sheet (free/busy only) */
define('BBPERM_INSTR_VIEW',             BBROLE_VIEW_CALENDAR);
/** Permission: View instrument booking sheet without restrictions on viewing future bookings */
define('BBPERM_INSTR_VIEW_FUTURE',      BBROLE_VIEW_CALENDAR_FUTURE);
/** Permission: View bookings on the instrument */
define('BBPERM_INSTR_VIEW_BOOKINGS',    BBROLE_VIEW_BOOKINGS);
/** Permission: View admin details of bookings on the instrument */
define('BBPERM_INSTR_VIEW_DETAILS',     BBROLE_VIEW_BOOKINGS_DETAILS);
/** Permission: May edit all bookings on instrument */
define('BBPERM_INSTR_EDIT_ALL',         BBROLE_EDIT_ALL);
/** Permission: Masquerade as a user on this instrument */
define('BBPERM_INSTR_MASQ',             BBROLE_ADMIN_MASQ);
/** Permission: Book instrument */
define('BBPERM_INSTR_BOOK',             BBROLE_MAKE_BOOKINGS);
/** Permission: Book instrument any time into the future */
define('BBPERM_INSTR_BOOK_FUTURE',      BBROLE_MAKE_BOOKINGS_FUTURE);
/** Permission: Book instrument without timeslot restrictions */
define('BBPERM_INSTR_BOOK_FREE',        BBROLE_MAKE_BOOKINGS_FREE);
/** Permission: Delete own bookings with appropriate notice */
define('BBPERM_INSTR_UNBOOK',           BBROLE_UNBOOK);
/** Permission: Delete own bookings without restrictions for appropriate notice  */
define('BBPERM_INSTR_UNBOOK_PAST',      BBROLE_UNBOOK_PAST);
/** Permission: Delete others' bookings */
define('BBPERM_INSTR_UNBOOK_OTHER',     BBROLE_UNBOOK_OTHER);
/** Permission: Edit an individual instrument's settings */
define('BBPERM_INSTR_EDIT_CONFIG',      BBROLE_ADMIN_INSTRUMENTS);
/** Permission: Basic instrument user permissions */
define('BBPERM_INSTR_BASIC',            BBPERM_INSTR_VIEW | BBPERM_INSTR_VIEW_BOOKINGS | BBPERM_INSTR_BOOK | BBPERM_INSTR_UNBOOK);
/** Permission: Read-only permissions */
define('BBPERM_INSTR_READONLY',         BBPERM_INSTR_VIEW);

/** Permission: Instrument admin all functions */
define('BBPERM_INSTR_ALL',               4294967295);     // 2^32 - 1

// Anonymous user's permissions:
// BBPERM_USER_LOGOUT


function bb_debug_list_perms() {
  print "<pre>";
  foreach (get_defined_constants() as $const => $value) {
    if (preg_match("/^BBPERM_/", $const) || preg_match("/^BBROLE_/", $const)) {
      printf("%40s => %s\n", $const, $value);
    }
  }
  print "</pre>";
}

?>
