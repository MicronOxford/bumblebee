<?php
# $Id$
# allow the admin user to masquerade as another user to make some 
# bookings. A bit like "su".

  function actionMasquerade() {
    if (! isset($_POST['user'])) {
      echo "<h2>User masquerading</h2>";
      selectuser('masquerade','No masquerade','Masquerade');
      return;
    } elseif ($_POST['user'] >= 0) {
      echo "<input type='hidden' name='masquerade' value='".$_POST['user']."' />";
    }
    actionRestart("");
  }

  ### FIXME this is broken. needs to become cookies
  function checkMasquerade() {
    global $MASQUID, $MASQUSER;
    #displayPost();
    if (isset($_POST['masquerade'])) {
      $MASQUID = $_POST['masquerade'];
      $MASQUSER = getUsername($MASQUID);
      echo "<div class='masquerade'>"
          ."Masquerading as ".$MASQUSER[1] ." (" .$MASQUSER[0] .") ";
      echo "<input type='hidden' name='masquerade' value='".$_POST['masquerade']."' />";
      #echo "<button type='submit' name='changemasq' value='1'>Change Masquerade</button>";
      echo "</div>";
    }
  }
?>
