<?
# $Id$
# work out what it was we were supposed to be doing
# $action is set based on what we are supposed to do
# this is then acted upon later in the page

include_once('inc/typeinfo.php');

function checkActions($auth) {
  global $act;
  global $PDATA;
  global $BASEURL, $nextaction;

  $action = "";

  # first, we need to determine if we are actually logged in or not
  # if we are not logged in, the the action *has* to be 'login'
  if (! $auth->isLoggedIn()) return 'login';

  #We can have action verbs past to us in three different ways.
  # 1. first PATH_INFO
  # 2. explicit PATH_INFO action=
  # 3. action= form fields 
  # Later specifications are used in preference to earlier ones

  $PDATA = eatPathInfo();
  
  $explicitaction = issetSet($PDATA, 'forceaction');
  $pathaction = issetSet($PDATA, 'action');
  $formaction = issetSet($_POST, 'action');
  #$pathaction = $PDATA['action'];
  #$formaction = $_POST['action'];

  if ($explicitaction) $action = $explicitaction;
  if ($pathaction) $action = $pathaction;
  if ($formaction) $action = $formaction;

  #protect admin functions
  if ($act[$action] > 999 && ! $auth->isadmin) return "forbidden!";

  # We also need to check to see if we are trying to change privileges
  #if (isset($_POST['changemasq']) && $_POST['changemasq']) return 'masquerade';

  $nextaction = "$BASEURL/$action";

  return $action;
}

function checkLogout(&$auth, $action) {
  if ($action == 'logout') {
    $auth->logout(); 
  }
}


function actionRestart($auth, $newaction) {
  global $action;
  #$_POST['action']=$newaction;
  $action=$newaction;
  performAction($auth, $action);
}

function eatPathInfo() {
  $pd = array();
  $pathinfo = issetSet($_SERVER, 'PATH_INFO');
  if ($pathinfo) {
    $path = explode('/', $pathinfo);
    $pd['action'] = $path[1];
    $actions = preg_grep("/^action=/", $path);
    $forceaction = array_keys($actions);
    if (isset($forceaction[0])) {
      preg_match("/^action=(.+)/",$path[$forceaction[0]],$m);
      $pd['forceaction'] = $m[1];
      $max = $forceaction[0];
    } else {
      $max = count($path);
    }
    for($i=2; $i<$max; $i++) {
      $pd[$i-1] = $path[$i];
    }
  }
  return $pd;
  /*
  echo "<pre>";
  print_r($pd);
  echo "</pre>";
  echo "<pre>";
  echo $pathinfo;
  #print_r($_SERVER);
  echo "</pre>";
  echo "<pre>";
  print_r($path);
  echo "</pre>";
  */
}

function performAction(&$auth, $action) {
  global $act;

  switch ($act[$action]) {
    case $act['login']:
      printLoginForm();
      break;
    case $act['logout']:
      actionLogout();
      break;
    case $act['view']:
      actionView($auth);
      break;
    case $act['book']:
      actionBook($auth);
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
      actionMasquerade($auth);
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
}
  
?> 
