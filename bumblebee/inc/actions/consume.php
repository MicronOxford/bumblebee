<?php
# $Id$
# record consumables usage

include_once 'inc/consumableuse.php';
include_once 'inc/consumable.php';
include_once 'inc/dbforms/date.php';
include_once 'inc/networkfunctions.php';
include_once 'inc/dbforms/anchortablelist.php';


  function actionConsume($auth) {
    global $BASEURL;
    $PD = consumeMungePathData();
    if (isset($PD['list']) && isset($PD['consumableid'])) {
      listConsumeConsumable($PD['consumableid']);
    } elseif (isset($PD['list']) && isset($PD['user'])) {
      listConsumeUser($PD['user']);
    } elseif (isset($PD['delete'])) {
      deleteConsumeRecord($PD['id']);
    } elseif (  
                (! isset($PD['id'])) && 
                ( 
                  (! isset($PD['user'])) || (! isset($PD['consumableid'])) 
                )
             ) {
      if (! isset($PD['user'])) {
        selectConsumeUser($PD);
      }
      if (! isset($PD['consumableid'])) {
        selectConsumeConsumable($PD);
      }
    } else {
      editConsumeRecord($PD, $auth);
    }
    echo "<br /><br /><a href='$BASEURL/consume'>Return to consumable use list</a>";
  }

  function consumeMungePathData() {
    global $PDATA;
    $PD = array();
    foreach ($_POST as $k => $v) {
      $PD[$k] = $v;
    }
    $lPDATA = $PDATA;
    array_shift($lPDATA);
    while (count($lPDATA)) {
      if (isset($lPDATA[0]) && $lPDATA[0]=='user' && is_numeric($lPDATA[1])) {
        array_shift($lPDATA);
        $PD['user'] = array_shift($lPDATA);
      } elseif (isset($lPDATA[0]) && $lPDATA[0]=='consumable' && is_numeric($lPDATA[1])) {
        array_shift($lPDATA);
        $PD['consumableid'] = array_shift($lPDATA);
      } elseif (isset($lPDATA[0]) && $lPDATA[0]=='list') {
        $PD['list'] = 1;
        array_shift($lPDATA);
      } elseif (isset($lPDATA[0]) && is_numeric($lPDATA[0])) {
        $PD['id'] = array_shift($lPDATA);
      } else {
        //this record is unwanted... drop it
        array_shift($lPDATA);
      }
    }
    #$PD['defaultclass'] = 12;
    preDump($PD);
    return $PD;
  }

  function selectConsumeUser($PD) {
    global $BASEURL;
    $extrapath = '';
    $listpath = '';
    if (isset($PD['consumableid'])) {
      $extrapath =  "consumable/$PD[consumableid]/";
      $listpath = "$BASEURL/consume/${extrapath}list";
    }
    #$extrapath = (isset($PD['consumableid']) ? "consumable/$PD[consumableid]/" : "");
    $userselect = new AnchorTableList('Users', 'Select which user is consuming');
    $userselect->connectDB("users", array('id', 'name', 'username'));
    $userselect->hrefbase = "$BASEURL/consume/${extrapath}user/";
    $userselect->setFormat('id', '%s', array('name'), ' %s', array('username'));

    if ($listpath) {
      echo "<p><a href='$listpath'>View listing</a> "
          ."for selected consumable</p>\n";
    }
    echo $userselect->display();
  }

  function selectConsumeConsumable($PD) {
    global $BASEURL;
    $extrapath = '';
    $listpath = '';
    if (isset($PD['user'])) {
      $extrapath =  "user/$PD[user]/";
      $listpath = "$BASEURL/consume/${extrapath}list";
    }
    $projectselect = new AnchorTableList('Consumables', 'Select which Consumables to use');
    $projectselect->connectDB('consumables', array('id', 'name', 'longname'));
    $projectselect->hrefbase = "$BASEURL/consume/${extrapath}consumable/";
    $projectselect->setFormat('id', '%s', array('name'), ' %s', array('longname'));
    
    if ($listpath) {
      echo "<p><a href='$listpath'>View listing</a> "
          .'for selected user</p>'."\n";
    }
    echo $projectselect->display();
  }

  function editConsumeRecord($PD, $auth) {
    $recordid = isset($PD['id']) ? $PD['id'] : -1;
    $userid   = isset($PD['user']) ? $PD['user'] : -1;
    $consumableid = isset($PD['consumableid']) ? $PD['consumableid'] : -1;
    $uid = $auth->uid;
    $ip = getRemoteIP();
    $today = new SimpleDate(time());
    $rec = new ConsumableUse($recordid, $userid, $consumableid,
                              $uid, $ip, $today->datestring);
    $rec->update($PD);
    $rec->checkValid();
    #$project->fields['defaultclass']->invalid = 1;
    $rec->sync();
    echo $rec->display();
    if ($rec->id < 0) {
      $submit = 'Record consumable use';
      $delete = '0';
    } else {
      $submit = 'Update entry';
      $delete = 'Delete entry';
    }
    echo "<input type='submit' name='submit' value='$submit' />";
    if ($delete) echo "<input type='submit' name='delete' value='$delete' />";
  }

  function deleteConsumeRecord($id) {
    $q = "DELETE FROM consumables_use WHERE id='$id'";
    db_quiet($q, 1);
  }

  function listConsumeConsumable($consumableID) {
    global $BASEURL;
    $extrapath = '';
    $listpath = '';
    $consumable = new Consumable($consumableID);
    echo '<p>Consumption records for '
        .$consumable->fields['name']->value."</p>\n";
    $recselect = new AnchorTableList('Consumption Record', 'Select the consumption record to view',3);
    $recselect->setTableHeadings(array('Date', 'User','Quantity'));
    $recselect->connectDB('consumables_use',
                          array(array('consumables_use.id','conid'), 'consumable', 'usewhen', 'username', 'name', 'quantity'),
                          'consumable='.qw($consumableID),
                          'usewhen',
                          array('consumables_use.id','conid'),
                          NULL,
                          array('users'=>'userid=users.id'));
    $recselect->hrefbase = "$BASEURL/consume/";
    $recselect->setFormat('conid', '%s', array('usewhen'), ' %s (%s)', array('name', 'username'), '%s', array('quantity'));

    echo $recselect->display();
  }

  function listConsumeUser($userID) {
    global $BASEURL;
    $extrapath = '';
    $listpath = '';
    $user = new User($userID);
    echo '<p>Consumption records for '
        .$user->fields['username']->value
        .' ('.$user->fields['name']->value.")</p>\n";
    $recselect = new AnchorTableList('Consumption Record', 'Select the consumption record to view',3);
    $recselect->setTableHeadings(array('Date', 'Item','Quantity'));
    $recselect->connectDB('consumables_use',
                          array(array('consumables_use.id','conid'), 'consumable', 'usewhen', 'name', 'longname', 'quantity'),
                          'userid='.qw($userID),
                          'usewhen',
                          array('consumables_use.id','conid'),
                          NULL,
                          array('consumables'=>'consumable=consumables.id'));
    $recselect->hrefbase = "$BASEURL/consume/";
    $recselect->setFormat('conid', '%s', array('usewhen'), ' %s (%s)', array('name', 'longname'), '%s', array('quantity'));

    echo $recselect->display();
  }

?> 
