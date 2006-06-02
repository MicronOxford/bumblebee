<?php
/**
* Upgrade the database to the latest version used by Bumblebee
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Installer
*/


/**
* Work out a db.ini from the defaults and the user input
*/
function constructini($source, $defaults) {
  $eol = "\n";
  $s = '[database]'.$eol
      .'host = "'.$defaults['sqlHost'].'"'.$eol
      .'username = "'.$defaults['sqlUser'].'"'.$eol
      .'passwd = "'.$defaults['sqlPass'].'"'.$eol
      .'database = "'.$defaults['sqlDB'].'"'.$eol
      .'tableprefix = "'.$defaults['sqlTablePrefix'].'"'.$eol;
  return $s;
}


?>
