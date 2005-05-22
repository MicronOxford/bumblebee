<?php
  # $Id$
  # Content heading, branding etc
  
  // You can customise the menu system here or in pageheader.php
  
  $MENUCONTENTS = $usermenu->getMenu();
  
?>
<body>

<div id="header">
  <div id="headerLeft">
    <a href='http://www.pfpc.unimelb.edu.au/' title="PFPC">
      <img src='<?php echo $BASEPATH ?>/theme/images/pfpc.png' alt="PFPC logo" />
    </a>
  </div>
  <div id="headerRight">
    <h1>PFPC Instrument Bookings</h1>
  </div>
</div>

<div id='fmenu'>
  <?php echo $MENUCONTENTS ?>
</div>
