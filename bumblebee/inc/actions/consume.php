<?php
# $Id$
# record consumables usage on a per-use basis

include_once 'inc/consumableuse.php';
include_once 'inc/consumable.php';
include_once 'inc/dbforms/date.php';
include_once 'inc/dbforms/anchortablelist.php';

class ActionConsume extends ActionAction {

  function ActionConsume($auth, $pdata) {
    parent::ActionAction($auth, $pdata);
    $this->mungePathData();
  }

  function go() {
    global $BASEURL;
    if (isset($this->PD['list']) && isset($this->PD['consumableid'])) {
      $this->listConsumeConsumable($PD['consumableid']);
    } elseif (isset($this->PD['list']) && isset($this->PD['user'])) {
      $this->listConsumeUser($this->PD['user']);
    } elseif (isset($this->PD['delete'])) {
      $this->deleteConsumeRecord();
    } elseif (  
                (! isset($this->PD['id'])) && 
                ( 
                  (! isset($this->PD['user'])) || (! isset($this->PD['consumableid'])) 
                )
             ) {
      if (! isset($this->PD['user'])) {
        $this->selectConsumeUser();
      }
      if (! isset($this->PD['consumableid'])) {
        $this->selectConsumeConsumable();
      }
    } else {
      $this->editConsumeRecord();
    }
    echo "<br /><br /><a href='$BASEURL/consume'>Return to consumable use list</a>";
  }

  function mungePathData() {
    $this->PD = array();
    foreach ($_POST as $k => $v) {
      $PD[$k] = $v;
    }
    $lPDATA = $this->PDATA;
    array_shift($lPDATA);
    while (count($lPDATA)) {
      if (isset($lPDATA[0]) && $lPDATA[0]=='user' && is_numeric($lPDATA[1])) {
        array_shift($lPDATA);
        $this->PD['user'] = array_shift($lPDATA);
      } elseif (isset($lPDATA[0]) && $lPDATA[0]=='consumable' && is_numeric($lPDATA[1])) {
        array_shift($lPDATA);
        $this->PD['consumableid'] = array_shift($lPDATA);
      } elseif (isset($lPDATA[0]) && $lPDATA[0]=='list') {
        $this->PD['list'] = 1;
        array_shift($lPDATA);
      } elseif (isset($lPDATA[0]) && is_numeric($lPDATA[0])) {
        $this->PD['id'] = array_shift($lPDATA);
      } else {
        //this record is unwanted... drop it
        array_shift($lPDATA);
      }
    }
    #$PD['defaultclass'] = 12;
    preDump($this->PD);
  }

  function selectConsumeUser() {
    global $BASEURL;
    $extrapath = '';
    $listpath = '';
    if (isset($this->PD['consumableid'])) {
      $extrapath =  "consumable/$this->PD[consumableid]/";
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

  function selectConsumeConsumable() {
    global $BASEURL;
    $extrapath = '';
    $listpath = '';
    if (isset($this->PD['user'])) {
      $extrapath =  "user/$PD[user]/";
      $listpath = "$BASEURL/consume/${extrapath}list";
    }
    $consumableselect = new AnchorTableList('Consumables', 'Select which Consumables to use');
    $consumableselect->connectDB('consumables', array('id', 'name', 'longname'));
    $consumableselect->hrefbase = "$BASEURL/consume/${extrapath}consumable/";
    $consumableselect->setFormat('id', '%s', array('name'), ' %s', array('longname'));
    
    if ($listpath) {
      echo "<p><a href='$listpath'>View listing</a> "
          .'for selected user</p>'."\n";
    }
    echo $consumableselect->display();
  }

  function editConsumeRecord() {
    $recordid = isset($this->PD['id']) ? $this->PD['id'] : -1;
    $userid   = isset($this->PD['user']) ? $this->PD['user'] : -1;
    $consumableid = isset($this->PD['consumableid']) ? $this->PD['consumableid'] : -1;
    $uid = $this->auth->uid;
    $ip = $this->auth->getRemoteIP();
    $today = new SimpleDate(time());
    $rec = new ConsumableUse($recordid, $userid, $consumableid,
                              $uid, $ip, $today->datestring);
    $rec->update($this->PD);
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

  function deleteConsumeRecord() {
    $rec = new ConsumableUse($this->PD['id']);
    $rec->delete();
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

}

?> 
