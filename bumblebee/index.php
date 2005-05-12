<?php
// $Id$

// prevent output for the moment to permit session headers
ob_start();

include 'config/config.php'; 
// start the database session
include 'inc/db.php'; 
// check the user's credentials, create a session to record them
include 'inc/bb/auth.php';
$auth = new BumbleBeeAuth();


// create the action factory to interpret what we are supposed to do
include_once 'inc/actions/actionfactory.php';
$action = new ActionFactory($auth);
if ($action->ob_flush_ok()) {
  // some actions will dump back a file, so we might not actually want to reflush the output.
  // all is ready to roll now, start the output again.
  ob_end_flush();
}

//FIXME -- streamline these includes?
include_once 'inc/adminmenu.php';
// include_once 'action/masquerade.php';

// $pagetitle can be used in the pageheader.php from the theme
$pagetitle = $action->title . ' - ' . $CONFIG['main']['SiteTitle'];
include 'theme/pageheader.php';
include 'theme/contentheader.php';

if ($auth->isLoggedIn() && $action->_verb != 'logout') {
  ?>
    <div class='fmenu'>
      <h3>Menu</h3>
      <ul>
        <li><a href='<?=$BASEURL?>/'>Main</a></li>
        <?
           if ($auth->localLogin) {
             echo '<li><a href="'.$BASEURL.'/passwd">Change Password</a></li>'."\n";
           }
        ?>
        <li><a href='<?=$BASEURL?>/logout'>Logout</a></li>
      </ul>
    <?
      if ($auth->isadmin) printAdminMenu();  //FIXME: oo-ify this?
      #if (($act[$action] != $act['masquerade']) && $ISADMIN) checkMasquerade();
    ?>
    </div>
  <?
}
?>
  <div class="content">
    <form method="post" action="<?=$action->nextaction?>" >
    <?
      if (! $auth->isLoggedIn()) {
        echo $auth->loginError();
      }
      $action->go();
    ?>
    </form>
  </div>
<?
include 'theme/contentfooter.php';
include 'theme/pagefooter.php';

if (! $action->ob_flush_ok()) {
  // some actions will dump back a file, so we might never want this stuff to get to a file...
  ob_end_clean();
  $action->returnBufferedStream();
}

?>
