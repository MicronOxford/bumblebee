<?php
# $Id$

ob_start();

include 'config.php'; 
#start the database session
include 'db.php'; 

$VERBOSESQL;
$nextaction;
$ERRORMSG;
$UID;
$EPASS;
$ISADMIN;
$USERNAME;
$MASQUID;
$MASQUSER;
$ISLOGGEDIN;
$PDATA = array();

include 'actions.php';
include 'determineaction.php';
include 'login.php';
checkLogin();
$action = checkActions();

include 'view.php';
include 'book.php';

#admin functions
include 'adminmenu.php';
include 'groups.php';
include 'projects.php';
include 'users.php';
include 'instruments.php';
include 'consumables.php';
include 'consume.php';
include 'masquerade.php';
include 'costs.php';
include 'specialcosts.php';
include 'adminconfirm.php';
include 'emaillist.php';
include 'billing.php';
include 'unknownaction.php';

$pagetitle = $actiontitles[$action] . ' - ' . $sitetitle;

#ALL is ready to roll now, start the output again.
ob_end_flush();

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
    if (isLoggedIn()) {
      ?>
        <div class='fmenu'>
          <h3>Menu</h3>
          <ul>
            <li><a href='<?=$BASEURL?>/'>Main</a></li>
            <li><a href='<?=$BASEURL?>/logout'>Logout</a></li>
          </ul>
        <?
          if ($ISADMIN) printAdminMenu();
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
      if (isset($ERRORMSG)) echo $ERRORMSG;

      performAction($action);
    ?>
    </form>
  </div>
<?
  include 'theme/footer.php'
?>
</body>
</html>
