<?php
/**
* Output a file to the browser
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Installer
*/

/**
* Dump the generated file to the user to save and upload to the server
*/
function outputTextFile($filename, $stream) {
  // Output a text file
  header("Content-type: text/plain"); 
  header("Content-Disposition: attachment; filename=$filename");
  echo $stream;
}

?>
