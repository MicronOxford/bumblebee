<?php
# $Id$

ob_start();

include 'config.php'; 
#start the database session
include 'db.php'; 
include 'inc/auth.php';
#var $auth = new Auth();
$auth = new Auth;

#ALL is ready to roll now, start the output again.
ob_end_flush();

$VERBOSESQL;
$nextaction;
$PDATA = array();

include_once 'actions.php';
include_once 'determineaction.php';

$action = checkActions($auth);
checkLogout($auth, $action);

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

$pagetitle = $actiontitles[$action] . ' - ' . $sitetitle;


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title><?=$pagetitle; ?></title>
  <link rel="stylesheet" href="<?=$BASEPATH?>/theme/babs.css" type="text/css" />
  <link rel="icon" href="<?=$BASEPATH?>/theme/favicon.ico" />
  <link rel="shortcut icon" href="<?=$BASEPATH?>/theme/favicon.ico" />
<?php
  include 'jsfunctions.php'
?>
</head>

<body>
<?
  include 'theme/header.php'
?>

  <?
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
    <form method="post" action="<?=$nextaction?>" >
    <?
      #echo "decide what happens here: $action (". $act[$action] .")<br />";
      if (! $auth->isLoggedIn()) {
        echo $auth->loginError();
      }
      performAction($auth, $action);
    ?>
    </form>
  </div>
  <?
    include 'theme/footer.php'
  ?>
</body>
</html>
