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

$VERBOSESQL;
$nextaction="";
$PDATA = array();

include_once 'action/actionfactory.php';

$action = new ActionFactory($auth);
checkLogout($auth, $action);//FIXME??

/*
include_once 'login.php';
include_once 'view.php';
include_once 'book.php';

#admin functions
include_once 'adminmenu.php';
include_once 'groups.php';
include_once 'projects.php';
include_once 'users.php';
include_once 'instruments.php';
include_once 'consumables.php';
include_once 'consume.php';
include_once 'masquerade.php';
include_once 'costs.php';
include_once 'specialcosts.php';
include_once 'adminconfirm.php';
include_once 'emaillist.php';
include_once 'billing.php';
include_once 'unknownaction.php';
*/
$pagetitle = $action->title . ' - ' . $CONFIG['main']['SiteTitle'];

//FIXME
include_once 'adminmenu.php';
include_once 'masquerade.php';

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
