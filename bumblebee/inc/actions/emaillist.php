<?php
# $Id$
# return a list of the email addresses depending on what we've been asked
# for... e.g. per instrument for the "announce" list.

include_once 'inc/dbforms/checkbox.php';
include_once 'inc/dbforms/checkboxtablelist.php';
include_once 'action/actionaction.php';

class ActionEmailList extends ActionAction {
  var $fatal_sql = 1;

  function ActionEmailList($auth, $pdata) {
    parent::ActionAction($auth, $pdata);
    $this->mungePathData();
  }

  function go() {
    if (! isset($this->PD['selectlist'])) {
      $this->selectLists();
    } else {
      $this->returnLists();
    }
  }

  function selectLists() {
    global $BASEURL;
    $select = new CheckBoxTableList('Instrument', 'Select which instrument to view');
    $hidden = new TextField('instrument');
    $select->addFollowHidden($hidden);
    $announce = new CheckBox('announce', 'Announce');
    $select->addCheckBox($announce);
    $unbook = new CheckBox('unbook', 'Unbook');
    $select->addCheckBox($unbook);
    $select->numSpareCols = 1;
    $select->connectDB('instruments', array('id', 'name', 'longname'));
    $select->setFormat('id', '%s', array('name'), " %s", array('longname'));
    $select->addFooter("(<a href='#' onclick='return deselectsome(%d,2);'>deselect all</a>)<br />".
                       "(<a href='#' onclick='return selectsome(%d,2);'>select all</a>)");
    #echo $groupselect->list->text_dump();
    echo $select->display();
    echo "<input type='hidden' name='selectlist' value='1' />";
    echo "<input type='submit' name='submit' value='Select' />";
  }

  function returnLists() {
    // start constructing the query, the 'WHERE 0 OR ...' is a pretty ugly hack
    // to ensure that we always have a valid conditional
    $q = 'SELECT DISTINCT users.email '
        .'FROM permissions '
        .'LEFT JOIN users ON users.id=permissions.userid '
        .'WHERE 0 ';
    
    $where = "";
    $namebase = 'Instrument-';
    for ($j=0; isset($this->PD[$namebase.$j.'-']); $j++) {
      $instr = issetSet($this->PD,$namebase.$j.'-instrument');
      $announce = issetSet($this->PD,$namebase.$j.'-announce');
      $unbook = issetSet($this->PD,$namebase.$j.'-unbook');
      #echo "$j ($instr) => ($unbook, $announce)<br />";
      #$where .= "OR (permissions.announce='1' AND permissions.instrid='$instr') ";
      $instr = qw($instr);
      $where .= $announce ? "OR (permissions.instrid=$instr AND permissions.announce='1') " : "";
      $where .= $unbook ? "OR (permissions.instrid=$instr AND permissions.unbook='1') " : "";
    }
    $q .= $where;
    #echo "Gathering email addresses: $q<br />";
    $sql = db_get($q, $this->fatal_sql);
    if (mysql_num_rows($sql)==0) {
      echo "<p>No email addresses found</p>";
    }
    while ($g = mysql_fetch_array($sql)) {
      echo $g['email'] ."<br />";
    }
  }
}
?> 
