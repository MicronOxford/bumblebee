<?php
/**
* Miscellaneous javascript functions to be included in each page
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Misc
*
* @todo //TODO: selectall code needs updating to include InstrumentGroups etc
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();
?>
<script type='text/javascript'>
<!--
function selectall () {
  return setcheckboxes(true, '', 0, 1);
}

function deselectall () {
  return setcheckboxes(false, '', 0, 1);
}

function selectsome (targetname, offset, mod) {
  return setcheckboxes(true, targetname, offset, mod);
}

function deselectsome (targetname, offset, mod) {
  return setcheckboxes(false, targetname, offset, mod);
}

function setcheckboxes (setval, targetname, offset, mod) {
  //alert("start");
  count = 0;
  rightForm = "bumblebeeform";
  for (var i=0; i<document.forms[rightForm].length; i++) {
    if (document.forms[rightForm].elements[i].type == "checkbox") {
      //alert('c='+count+'\no='+offset+'\nm='+mod+'\ny='+((count-offset)%mod));
      namestart = document.forms[rightForm].elements[i].name.substr(0, targetname.length);
      if (targetname == namestart) {
        if ((count-offset) % mod == 0) {
          document.forms[rightForm].elements[i].checked=setval;
        }
        count++;
      }
    }
    //alert(document.forms[0].elements[i].value);
  }
  return false;
}
-->
</script>
