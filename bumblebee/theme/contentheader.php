<?php
/**
* Content heading, branding etc
*
* This is only a sample header file.
* You can customise the menu system here or in pageheader.php
*
* @author    Stuart Prescott
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

$MENUCONTENTS = $usermenu->getMenu();
$BasePath     = $conf->BasePath;
?>
<body>

<div id="header">
  <div id="headerLeft">
    <a href='http://bumblebeeman.sf.net/' title="Bumblebee">
      <img src='<?php echo $BasePath ?>/theme/images/logo.png' alt="Bumblebee logo" />
    </a>
  </div>
  <div id="headerRight">
    <h1><?php print T_('Bumblebee Instrument Bookings'); ?></h1>
  </div>
</div>

<div id='fmenu'>
  <?php echo $MENUCONTENTS ?>
</div>
