<?php
// $Id$

// specify an alternate config location (e.g. in /etc) here:
$CONFIGLOCATION = 'config/';

$CONFIG = parse_ini_file($CONFIGLOCATION.'bumblebee.ini',1);

$ADMINEMAIL = $CONFIG['main']['AdminEmail'];
$BASEPATH   = $CONFIG['main']['BasePath'];
$BASEURL    = $CONFIG['main']['BaseURL'];

$VERBOSESQL = $CONFIG['error_handling']['VerboseSQL'];
$VERBOSEDATA = $CONFIG['error_handling']['VerboseData'];

ini_set("session.use_only_cookies",1); #don't permit ?PHPSESSID= stuff
#ini_set("session.cookie_lifetime",60*60*1); #login expires after x seconds

if ($CONFIG['error_handling']['AllWarnings']) {
  //this is nice for development but probably turn it off for production
  ini_set("error_reporting",E_ALL); #force all warnings to be echoed
} else {
  ini_set("error_reporting",E_ERROR); #force all warnings to be echoed
}

$BUMBLEBEEVERSION = '0.9.5.1';

?> 
