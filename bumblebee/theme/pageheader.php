<?php
# $Id$

/* 
  Note that three CSS files are used:
    1. bumblebee.css    
                contains the specific classes that are used for bumblebee markup
    2. bumblebee-custom-colours.css
                contains customisations of the default ones (mainly for colour customisation)
    2. pagelayout.css   
                contains other classes that are used by your own layout
*/

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
  <title><?=$pagetitle?></title>
  <link rel="stylesheet" href="<?=$BASEPATH?>/theme/bumblebee.css" type="text/css" />
  <link rel="stylesheet" href="<?=$BASEPATH?>/theme/bumblebee-custom-colours.css" type="text/css" />
  <link rel="stylesheet" href="<?=$BASEPATH?>/theme/pagelayout.css" type="text/css" />
  <link rel="icon" href="<?=$BASEPATH?>/theme/images/favicon.ico" />
  <link rel="shortcut icon" href="<?=$BASEPATH?>/theme/images/favicon.ico" />
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  
<?php
  include 'inc/jsfunctions.php'
?>
</head>
