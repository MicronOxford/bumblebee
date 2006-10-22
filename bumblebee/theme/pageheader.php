<?php
/**
* Header HTML that is included on every page
*
* This is only a sample implementation. Feel free to monkey to your heart's delight.
*
*  Note that three CSS files are used:
*    1. bumblebee.css
*                contains the specific classes that are used for bumblebee markup
*    2. bumblebee-custom-colours.css
*                contains customisations of the default ones (mainly for colour customisation)
*    3. pagelayout.css
*                contains other classes that are used by your own layout
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
$BasePath     = $conf->BasePath;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title><?php echo $pagetitle?></title>
  <link rel="stylesheet" href="<?php echo $BasePath?>/theme/bumblebee.css" type="text/css" />
  <link rel="stylesheet" href="<?php echo $BasePath?>/theme/bumblebee-custom-colours.css" type="text/css" />
  <link rel="stylesheet" href="<?php echo $BasePath?>/theme/pagelayout.css" type="text/css" />
  <link rel="icon" href="<?php echo $BasePath?>/theme/images/favicon.ico" />
  <link rel="shortcut icon" href="<?php echo $BasePath?>/theme/images/favicon.ico" />

<?php
  include 'inc/jsfunctions.php'
?>
</head>
