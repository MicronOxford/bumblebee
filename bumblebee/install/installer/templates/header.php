<?php
/**
* HTML header file included before the output and forms from the installer
*
* @author     Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Installer
*/

?>
  <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
  <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
      <head>
        <title><?php echo $title; ?></title>
        <style type="text/css">
              .good  { 
                color: green;  
                font-weight: bolder; 
              }
              .warn  { 
                color: orange; 
                font-weight: bolder; 
              }
              .error { 
                color: red;    
                font-weight: bolder; 
              }
              blockquote {
                border: 1px solid #333399; 
                margin: 1em; 
                padding: 1em;
              }
              fieldset { 
                background-color: #f9f9ff;
              }
              fieldset fieldset {
                background-color: #f9ffff;
              }
              body {
                font-family: "Bitstream Vera Sans", Verdana, Helvetica, Arial, sans-serif;
              }
              h1 {
                font-style: italic;
                color: #3333aa;
                padding-left: 100px;
                background-image: url("installer/templates/logo.png");
                background-repeat: no-repeat;
                height: 75px;
              }
              h2 { 
                padding-top: 0; 
                margin-top: 0;
              }
              input:focus {
                background-color: #eeeeff;
              }
              #stepbar {
                text-align: center;
              }
              #stepbar input, .navButtons input {
                background-color: #eeeeff;
                color: #000099;
                height: 3em;
              }
              #stepbar input:enabled, .navButtons input:enabled  {
                cursor: pointer;
              }
              #stepbar input:disabled, .navButtons input:disabled  {
                background-color: #aaaaaa;
                color: #ffffff;
              }
              #stepnum {
                margin-bottom: 1em;
                margin-left: 2em;
                color: #3333aa;
              }
              .navButtons {
                text-align: center;
              }
              .navButtons input:enabled {
                cursor: pointer;
              }
              .footer {
                font-size: 60%;
                margin-top: 3em;
                border-top: 1px solid #999999;
                padding-top: 0.5em;
              }
        </style>
        <script type='text/javascript'>
        //<![CDATA[
          function enableButton(id) {
            var but = document.getElementById(id);
            but.disabled = false;
            return true;
          }
        //]]>
        </script>
        <?php echo $head; ?>
      </head>
      <body>
