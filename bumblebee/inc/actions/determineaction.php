<?
# $Id$
# work out what it was we were supposed to be doing
# $action is set based on what we are supposed to do
# this is then acted upon later in the page

function is_alphabetic($var)
{
  return preg_match("/^\w+$/", $var);
}

function checkActions()
{
  global $act;
  global $ISADMIN;
  # first, we need to determine if we are actually logged in or not
  # if we are not logged in, the the action *has* to be 'login'
  if (! isLoggedIn()) return 'login';
  return $_POST['action'];
}
?> 
