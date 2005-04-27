<?php
# $Id$
# test the timeslotrule functions

$INC = ini_get('include_path');
ini_set('include_path', $INC.':../../../');

include_once '../timeslotrule.php';
include_once 'inc/dbforms/typeinfo.php';

$p = array (
    '[0-6]<00:00-08:00/*,08:00-13:00/1,13:00-18:00/1,18:00-24:00/*>',
    '[0]<00:00-24:00/0>[1-5]<00:00-09:00/0,09:00-17:00/8,17:00-24:00/0>[6]<>',
    '[0]<>[1-5]<00:00-09:00/*,09:00-17:00/8,17:00-24:00/*>[6]<>',
    '[0]<>[1-5]<00:00-09:00/0,09:00-13:00/4,13:00-17:00/2,17:00-33:00/1>[6]<>',
    '[0]<>[1-5]<09:00-13:00/4,13:00-17:00/2,17:00-33:00/1>[6]<>'
    );

foreach ($p as $pic) { 
  $at = new TimeSlotRule($pic);
  echo $at->dump(0);
}

$findtest = new TimeSlotRule($p[4]);
