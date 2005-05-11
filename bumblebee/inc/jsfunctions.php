<?php
# $Id$
# Some javascript functions for use in the pages
?>
<script type='text/javascript'>
<!--
function selectall () {
  return setcheckboxes(true, 0, 1);
}

function deselectall () {
  return setcheckboxes(false, 0, 1);
}

function selectsome (offset, mod) {
  return setcheckboxes(true, offset, mod);
}

function deselectsome (offset, mod) {
  return setcheckboxes(false, offset, mod);
}

function setcheckboxes (setval, offset, mod) {
  //alert("start");
  count = 0;
  for (var i=0; i<document.forms[0].length; i++) {
    if (document.forms[0].elements[i].type == "checkbox") {
    //alert('c='+count+'\no='+offset+'\nm='+mod+'\ny='+((count-offset)%mod));
      if ((count-offset) % mod == 0) {
        document.forms[0].elements[i].checked=setval;
      }
      count++;
    }
    //alert(document.forms[0].elements[i].value);
  } 
  return false;
}
-->
</script>
