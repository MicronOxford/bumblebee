<?php
# $Id$
# allow the admin user to masquerade as another user to make some 
# bookings. A bit like "su".

  function actionMasquerade()
  {
    if (! isset($_POST['user'])) {
      echo "<h2>User masquerading</h2>";
      selectuser('masquerade','No masquerade','Masquerade');
    } else {
      echo "<input type='hidden' name='masquser' value='".$_POST['user']."' />";
      actionMain();
    }
  }

  function checkMasquerade()
  {
    if (isset($_POST['masquser'])) {
      echo "<input type='hidden' name='masquser' value='".$_POST['user']." />";
      echo "<button type='submit' name='action' value='masqurade'>Change Masquerade</button>";
    }
  }
?>
