<?php
# $Id$
# return a list of the email addresses depending on what we've been asked
# for... e.g. per instrument for the "announce" list.

include_once 'inc/formslib/checkbox.php';
include_once 'inc/formslib/checkboxtablelist.php';
include_once 'inc/actions/actionaction.php';

/**
 *  Find out what instruments the lists should be prepared for and then return the email list.
 *
 *   Perhaps, this class should be split some more, with some of the details abstracted?
 */

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
    $selectRow = new nonDBRow('listselect', 'Select email lists', 
              'Select which email lists you want to return');
    $select = new CheckBoxTableList('Instrument', 'Select which instrument to view');
    $hidden = new TextField('instrument');
    $select->addFollowHidden($hidden);
    $announce = new CheckBox('announce', 'Announce');
    $select->addCheckBox($announce);
    $unbook = new CheckBox('unbook', 'Unbook');
    $select->addCheckBox($unbook);
    //$select->numSpareCols = 1;
    $select->connectDB('instruments', array('id', 'name', 'longname'));
    $select->setFormat('id', '%s', array('name'), " %50.50s", array('longname'));
    $select->addFooter('(<a href="#" onclick="return deselectsome(%d,2);">deselect all</a>)<br />'.
                       '(<a href="#" onclick="return selectsome(%d,2);">select all</a>)');
    $selectRow->addElement($select);
    $separator = new TextField('separator', 'Value separator',
                'Separates the returned values so you can paste them into your email client');
    $separator->defaultValue = ',';
    $separator->setattr(array('size' => '2'));
    $selectRow->addElement($separator);
    //echo '<table><tr><td colspan=2>';
    //echo $select->display();
    //echo '</td></tr>';
    //echo $separator->displayInTable(2);
    echo $selectRow->displayInTable(4);
    //echo '</table>';
    echo '<input type="hidden" name="selectlist" value="1" />';
    echo '<input type="submit" name="submit" value="Select" />';
  }

  function returnLists() {
    global $TABLEPREFIX;
    // start constructing the query, the 'WHERE 0 OR ...' is a pretty ugly hack
    // to ensure that we always have a valid conditional
    $q = 'SELECT DISTINCT users.email '
        .'FROM '.$TABLEPREFIX.'permissions AS permissions '
        .'LEFT JOIN '.$TABLEPREFIX.'users AS users ON users.id=permissions.userid '
        .'WHERE 0 ';
    
    $where = '';
    $namebase = 'Instrument-';
    for ($j=0; isset($this->PD[$namebase.$j.'-row']); $j++) {
      $instr = issetSet($this->PD,$namebase.$j.'-instrument');
      $announce = issetSet($this->PD,$namebase.$j.'-announce');
      $unbook = issetSet($this->PD,$namebase.$j.'-unbook');
      #echo "$j ($instr) => ($unbook, $announce)<br />";
      #$where .= "OR (permissions.announce='1' AND permissions.instrid='$instr') ";
      $where .= $announce 
                ? 'OR (permissions.instrid='.qw($instr).' AND permissions.announce='.qw(1).') ' 
                : '';
      $where .= $unbook 
                ? 'OR (permissions.instrid='.qw($instr).' AND permissions.unbook='.qw(1).') ' 
                : '';
    }
    $q .= $where;
    #echo "Gathering email addresses: $q<br />";
    $sql = db_get($q, $this->fatal_sql);
    // FIXME: mysql specific functions
    if (mysql_num_rows($sql)==0) {
      echo '<p>No email addresses found</p>';
    } else {
      $separator = $this->PD['separator'];
      $list = array();
      while ($g = mysql_fetch_array($sql)) {
        $list[] = xssqw($g['email']);
      }
      echo join($list,  xssqw($separator).'<br />');
    }
  }
}
?> 
