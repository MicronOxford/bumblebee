<?php
# $Id$

ob_start();

include 'config.php'; 
#start the database session
include 'db.php'; 
include 'inc/auth.php';
#var $auth = new Auth();
$auth = new BumbleBeeAuth();

#ALL is ready to roll now, start the output again.
ob_end_flush();

include_once 'action/actionfactory.php';

$action = new ActionFactory($auth);

$pagetitle = $action->title . ' - ' . $CONFIG['main']['SiteTitle'];

//FIXME -- streamline these includes?
include_once 'adminmenu.php';
// include_once 'action/masquerade.php';

include 'theme/pageheader.php';
include 'theme/contentheader.php';

if ($auth->isLoggedIn()) {
  ?>
    <div class='fmenu'>
      <h3>Menu</h3>
      <ul>
        <li><a href='<?=$BASEURL?>/'>Main</a></li>
        <li><a href='<?=$BASEURL?>/logout'>Logout</a></li>
      </ul>
    <?
      if ($auth->isadmin) printAdminMenu();
      #if (($act[$action] != $act['masquerade']) && $ISADMIN) checkMasquerade();
    ?>
    </div>
  <?
}
?>
  <div class="content">
    <form method="post" action="<?=$action->nexthref?>" >
    <?
      #echo "decide what happens here: $action (". $act[$action] .")<br />";
      if (! $auth->isLoggedIn()) {
        echo $auth->loginError();
      }
      $action->go();//($auth, $action);
    ?>
    </form>
  </div>
<?
  include 'theme/contentfooter.php';
  include 'theme/pagefooter.php';
?>
