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

  # first, we need to determine if we are actually logged in or not
  # if we are not logged in, the the action *has* to be 'login'
  if (! isLoggedIn()) return 'login';
 
  #protect admin functions
  if ($act[$action] > 999 && ! $ISADMIN) return "forbidden!";

  # We also need to check to see if we are trying to change privileges
  if (isset($_POST['changemasq']) && $_POST['changemasq']) return 'masquerade';

  return $_POST['action'];
}

function performAction($action) {
  global $act;
  global $ISADMIN;

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
}
  
?> 
