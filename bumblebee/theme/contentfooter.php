<?php
/**
* Footer HTML that is included on every page
*  
* this is only a sample implementation giving credit to the BumbleBee project and some
* feedback on what BumbleBee has been managing.
*
* This is GPL'd software, so it is *not* a requirement that you give credit to BumbleBee,
* link to the site etc. In fact, this is in the theme/ directory to allow you to customise
* it easily, without having to delve into the rest of the code.
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage theme
*/

  /** use the SystemStats object to create some nice info about what the system does */
  include 'inc/systemstats.php';
  $stat = new SystemStats;
?>

<div id='bumblebeefooter'>
  <p>
    Email the <a href="mailto:<?php echo $ADMINEMAIL ?>">system administrator</a>
    for help.
  </p>
 </div>
