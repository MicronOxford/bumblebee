<?php
# $Id$
# Some javascript functions for use in the pages
?>
<script type='text/javascript'>
function selectall () {
  return setallcheckboxes(true);
}

function deselectall () {
  return setallcheckboxes(false);
}

function setallcheckboxes (setval) {
  //alert("start");
  for (var i=0; i<document.forms[0].length; i++) {
    if (document.forms[0].elements[i].type == "checkbox")
      document.forms[0].elements[i].checked=setval;
    //alert(document.forms[0].elements[i].value);
  } 
  return false;
}
</script>
