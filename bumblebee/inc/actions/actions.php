<?
# $Id$
# Create data structures that can describe both the action-word to be acted
# on, as well as the title to be reflected in the HTML title tag.

$userfunctions = array(
  "view=View instrument bookings",
  "book=Create or edit instrument bookings",
  "login=Login",
  "logout=Logout"
);
#admin only functions
$adminfunctions = array(
  "groups=Manage groups",
  "projects=Manage projects",
  "users=Manage users and permissions",
  "instruments=Manage instruments",
  "consumables=Manage consumables",
  "consume=Record consumable usage",
  "masquerade=Masquerade as another user",
  "costs=Edit standard costs",
  "specialcosts=Edit or create special charges",
  "bookmeta=Points system and booking controls",
  "adminconfirm=Booking confirmation",
  "emaillist=Email lists",
  "billing=Prepare billing summaries"
);

$act=array();
$actiontitles=array();
$i=1;
createDefaultAction ($userfunctions);
createActionTranslate ($userfunctions, $i);
$i=1000;
createActionTranslate ($adminfunctions, $i);

function createActionTranslate($fns, $i) {
  global $act, $actiontitles;
  foreach ($fns as $fn) {
    preg_match("/(.+?)=(.+)/", $fn, $m);
    #echo "<!-- $m[1], $m[2] -->\n";
    $act[$m[1]]=$i++;
    $actiontitles[$m[1]]=$m[2];
  }
}

function createDefaultAction ($fns) {
  global $act, $actiontitles;
  $fn=$fns[0];
  preg_match("/(.+?)=(.+)/", $fn, $m);
  #echo "<!-- $m[1], $m[2] -->\n";
  $act[""]=1;
  $actiontitles[""]=$m[2];
  #echo $fn;
}

/*
echo "<pre>";
print_r($act);
echo "</pre>";
echo "<pre>";
print_r($actiontitles);
echo "</pre>";
*/
?> 
