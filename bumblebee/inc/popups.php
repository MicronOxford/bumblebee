<?php
/**
* Miscellaneous javascript functions to be included in each page
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

$conf = ConfigReader::getInstance();
$overlib = $conf->value('display', 'overlib_location', 'system-inc/overlib').'/overlib.js';
?>

<!--
Include the overlib javascript library:
    (c) Erik Bosrup
    http://www.bosrup.com/web/overlib/
-->
<div id="overDiv" style="position:absolute; visibility:hide;z-index:1000;"></div>
<script type="text/javascript" src="<?php echo $overlib; ?>"></script>

<script type="text/javascript">
  function showPopup(message, width, offsety) {
    var realdata=unescape(message);
    if (realdata.length > 0) return overlib(realdata, WIDTH, width, OFFSETY, offsety);
  }
  function hidePopup() {
    return nd();
  }

  // show/hide entire divs
  function showDiv(id) {
    var div = document.getElementById(id);
    div.style.display = 'inline';
  }
  function hideDiv(id) {
    var div = document.getElementById(id);
    div.style.display = 'none';
  }
</script>
