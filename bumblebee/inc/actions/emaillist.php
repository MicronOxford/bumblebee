<?php
# $Id$
# return a list of the email addresses depending on what we've been asked
# for... e.g. per instrument for the "announce" list.

include_once 'inc/formslib/checkbox.php';
include_once 'inc/formslib/checkboxtablelist.php';
include_once 'inc/formslib/dblist.php';
include_once 'inc/bb/exporttypes.php';
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
    $select->addSelectAllFooter(true);
    $selectRow->addElement($select);
    $separator = new TextField('separator', 'Value separator',
                'Separates the returned values so you can paste them into your email client');
    $separator->defaultValue = ',';
    $separator->setattr(array('size' => '2'));
    $selectRow->addElement($separator);
    echo $selectRow->displayInTable(4);
    echo '<input type="hidden" name="selectlist" value="1" />';
    echo '<input type="submit" name="submit" value="Select" />';
  }

  function returnLists() {
    $where = array('0');  //start the WHERE with 0 in case nothing was selected (always get valid SQL)
    $namebase = 'Instrument-';
    for ($j=0; isset($this->PD[$namebase.$j.'-row']); $j++) {
      $instr = issetSet($this->PD,$namebase.$j.'-instrument');
      //echo "$j ($instr) => ($unbook, $announce)<br />";
      if (issetSet($this->PD,$namebase.$j.'-announce')) {
        $where[] ='(permissions.instrid='.qw($instr).' AND permissions.announce='.qw(1).')' ;
      }
      if (issetSet($this->PD,$namebase.$j.'-unbook')) {
        $where[] = '(permissions.instrid='.qw($instr).' AND permissions.unbook='.qw(1).')' ;
      }
    }
    #echo "Gathering email addresses: $q<br />";
    $fields = array(new sqlFieldName('email', 'Email Address'));
    $list = new DBList('permissions', $fields, join($where, ' OR '), true);
    $list->join[] = (array('table' => 'users', 'condition' => 'users.id=permissions.userid'));
    $list->setFormat('%s', array('email'));
    $list->fill();
    if (count($list->data) == 0) {
      echo '<p>No email addresses found</p>';
    } else {
      $list->formatList();
      $this->PD['separator'] = stripslashes($this->PD['separator']);
      if ($this->PD['separator'] == '\n') {
        $this->PD['separator'] = "\n";
      } elseif ($this->PD['separator'] == '\t') {
        $this->PD['separator'] = "\t";
      }
      echo join($list->formatdata, xssqw($this->PD['separator']).'<br />');
    }
  }

}
?> 
