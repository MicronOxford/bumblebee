<?php
# $Id$
# return a report of instrument usage

include_once 'inc/formslib/checkbox.php';
include_once 'inc/formslib/checkboxtablelist.php';
include_once 'inc/actions/export.php';
include_once 'inc/exportcodes.php';

/**
 *  Find out what sort of report is required and generate it
 *
 */

class ActionReport extends ActionExport {
  var $fatal_sql = 1;

  function ActionReport($auth, $pdata) {
    parent::ActionExport($auth, $pdata);
    $this->mungePathData();
    $this->format = EXPORT_FORMAT_HTML;
  }

//   function go() {
//   }
  
//   function mungePathData() {
//   }

}
?> 
