<?
# Create data structures that can describe both the action-word to be acted
# on, as well as the title to be reflected in the HTML title tag.

$userfunctions = array(
  "login=Login",
  "main=Main menu",
  "view=View instrument bookings",
  "book=Create or edit instrument bookings"
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

/*
$act=array();
$act['login']=1;
$act['main']=2;
$act['view']=3;
$act['book']=3;
#admin only functions
$act['groups']=1000;
$act['projects']=1001;
$act['users']=1002;
$act['instruments']=1003;
$act['consumables']=1004;
$act['consume']=1005;
$act['masquerade']=1006;
$act['costs']=1007;
$act['specialcosts']=1008;
$act['bookmeta']=1009;
$act['adminconfirm']=1010;
$act['emaillist']=1011;
$act['billing']=1012;*/
?> 
