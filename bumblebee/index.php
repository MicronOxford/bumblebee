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
$MASQUID;
$MASQUSER;

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
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1 Strict//EN"
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
  <form method="post" action="./" >
<?php
  #<form method="post" action="./" >
  echo "decide what happens here: $action (". $act[$action] .")<br />";
  if (isset($ERRORMSG)) echo $ERRORMSG;

  if (($act[$action] != $act['masquerade']) && $ISADMIN) checkMasquerade();

  performAction($action);
  
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
