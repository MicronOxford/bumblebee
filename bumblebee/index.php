<?php
# $Id$
include 'config.php'; 
#start the database session
include 'db.php'; 

$actiontitle;
$ERRORMSG;
$UID;
$EPASS;
$ISADMIN;
$USERNAME;

include 'actions.php';
include 'determineaction.php';
include 'login.php';
$action = checkActions();

include 'main.php';
include 'view.php';

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
include 'savelogin.php';

$pagetitle = $actiontitles[$action] . ' - ' . $sitetitle;


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title><?=$pagetitle; ?></title>
  <link rel="stylesheet" href="babs.css" type="text/css" />
  <link rel="icon" href="favicon.ico" />
  <link rel="shortcut icon" href="favicon.ico" />
<?php
include 'jsfunctions.php'
?>
</head>

<body>
  <form method="post">
<?php
  echo "decide what happens here: $action (". $act[$action] .")<br />";
  if (isset($ERRORMSG)) echo $ERRORMSG;

  if ($ISADMIN) checkMasquerade();

  if ($act[$action] > 999 && ! $ISADMIN) $action="forbidden!";
  switch ($act[$action]) {
    case $act['login']:
      printLoginForm();
      break;
    case $act['main']:
      if ($ISADMIN) printAdminMenu();
      actionMain();
      break;
    case $act['view']:
      actionView();
      break;
    case $act['groups']:
      actionGroup();
      break;
    case $act['projects']:
      actionProjects();
      break;
    case $act['users']:
      actionUsers();
      break;
    case $act['instruments']:
      actionInstruments();
      break;
    case $act['consumables']:
      actionConsumables();
      break;
    case $act['consume']:
      actionConsume();
      break;
    case $act['masquerade']:
      actionMasquerade();
      break;
    case $act['costs']:
      actionCosts();
      break;
    case $act['specialcosts']:
      actionSpecialCosts();
      break;
    case $act['bookmeta']:
      actionBookmeta();
      break;
    case $act['adminconfirm']:
      actionAdminconfirm();
      break;
    case $act['emaillist']:
      actionEmaillist();
      break;
    case $act['billing']:
      actionBilling();
      break;
    default:
      actionUnknown($action);
  }
  
  $query="SELECT * FROM users";
  if(!$sql = mysql_query($query)) die(sql_error());
  echo "<p>".mysql_num_rows($sql)." users registered.</p>";
  while ($row=mysql_fetch_row($sql)) {
    echo "$row[0] $row[1] $row[2]<br />";
  }
  if(!$sql = mysql_query($query)) die(sql_error());
  while ($row=mysql_fetch_array($sql)) {
    echo $row['id']. $row['name']. $row['email']."<br />";
  }
  if ($action != 'login')
  {
    printSaveLogin();
  }
?>
  </form>
</body>
</html>
