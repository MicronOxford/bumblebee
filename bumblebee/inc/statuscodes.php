<?php
/**
* Status codes for actions
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Misc
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

/** Status: there was nothing to do (NO-OP) */
define('STATUS_NOOP',      0);
/** Status: Everything was OK. */
define('STATUS_OK',        1);
/** Status: Proceeded OK but generated warnings */
define('STATUS_WARN',      2);
/** Status: Did not proceed, generated errors */
define('STATUS_ERR',       4);
/** Status: Did not proceed, action is forbidden */
define('STATUS_FORBIDDEN', STATUS_ERR | 8);

?>
