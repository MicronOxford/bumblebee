<?php
# $Id$

# name of the site
$sitetitle  = 'PFPC instrument booking';
$ADMINEMAIL = 's.prescott@unimelb.edu.au';
$BASEPATH   = '/pfpc/bumblebee';
$BASEURL    = "$BASEPATH/index.php";

$VERBOSESQL = 0;
$VERBOSESQL = 1;

ini_set("session.use_only_cookies",1); #don't permit ?PHPSESSID= stuff
#ini_set("session.cookie_lifetime",60*60*1); #login expires after x seconds

#this is nice for development but probably turn it off for production
ini_set("error_reporting",E_ALL); #force all warnings to be echoed
                  

?> 
