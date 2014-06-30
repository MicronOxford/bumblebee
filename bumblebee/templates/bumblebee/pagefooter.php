<?php
/**
* Footer HTML that is included on every page
*
* @author     Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage theme
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

require_once 'inc/config.php';
$conf = ConfigReader::getInstance();
$template = $conf->value('display', 'template');

/** there can be a contentfooter too */
include "templates/$template/contentfooter.php";

?>

</body>
</html>
