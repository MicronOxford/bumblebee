<?php
/**
* Internationali[sz]ation of Bumblebee
*
* Loads tools for translating strings.
* Makes use of PHP's gettext() functions if they are available
* and the locale is installed on the server and can be found.
* Since this is rarely the case, particularly on shared servers,
* the fallback has to be a good one.
*
* The php-gettext project provides the base functionality required.
*
* See
*        http://savannah.nongnu.org/projects/php-gettext/
*
* (tested with php-gettext version 1.0.7)
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Misc
*
* path (bumblebee root)/inc/i18nconfig.php
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

/** php-gettext base file for gettext emulation */
include_once 'php-gettext/gettext.inc';

// get PHP to send a UTF-8 header for the content-type charset rather
// than the PHP4 default iso8859-1
ini_set('default_charset', 'utf-8');

?>
