<?
# $Id$
# work out what it was we were supposed to be doing
# $action is set based on what we are supposed to do
# this is then acted upon later in the page

function is_alphabetic($var) {
  return preg_match("/^\w+$/", $var);
}

function checkActions() {
  global $act;
  global $ISADMIN;
  global $PDATA;
  global $BASEURL, $nextaction;

  # first, we need to determine if we are actually logged in or not
  # if we are not logged in, the the action *has* to be 'login'
  if (! isLoggedIn()) return 'login';

  #We can have action verbs past to us in three different ways.
  # 1. first PATH_INFO
  # 2. explicit PATH_INFO action=
  # 3. action= form fields 
  # Later specifications are used in preference to earlier ones

  $PDATA = eatPathInfo();
  
  $explicitaction = $PDATA['forceaction'];
  $pathaction = $PDATA['action'];
  $formaction = $_POST['action'];

  if (isset($explicitaction)) $action = $explicitaction;
  if (isset($pathaction)) $action = $pathaction;
  if (isset($formaction)) $action = $formaction;

  #protect admin functions
  if ($act[$action] > 999 && ! $ISADMIN) return "forbidden!";
  if ($act[$action] == $act["logout"]) logout();

  # We also need to check to see if we are trying to change privileges
  #if (isset($_POST['changemasq']) && $_POST['changemasq']) return 'masquerade';

  $nextaction = "$BASEURL/$action";

  return $action;
}

function actionRestart($newaction) {
  global $action;
  #$_POST['action']=$newaction;
  $action=$newaction;
  performAction($action);
}

function eatPathInfo() {
  $pd = array();
  $pathinfo = $_SERVER['PATH_INFO'];
  if (isset($pathinfo)) {
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

function performAction($action) {
  global $act;
  global $ISADMIN;

  switch ($act[$action]) {
    case $act['login']:
      printLoginForm();
      break;
    case $act['logout']:
      actionLogout();
      break;
    case $act['view']:
      actionView();
      break;
    case $act['book']:
      actionBook();
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
}
  
?> 
