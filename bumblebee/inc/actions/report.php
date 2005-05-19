<?php
# $Id$
# return a report of instrument usage

include_once 'inc/formslib/checkbox.php';
include_once 'inc/formslib/checkboxtablelist.php';
include_once 'inc/actions/actionaction.php';

/**
 *  Find out what sort of report is required and generate it
 *
 */

class ActionReport extends ActionAction {
  var $fatal_sql = 1;

  function ActionReport($auth, $pdata) {
    parent::ActionAction($auth, $pdata);
    $this->mungePathData();
  }

  function go() {
    if (isset($this->PD['what'])) {
      $daterange = new DateRange('daterange', 'Select date range', 
                      'Enter the dates over which you want to report consumable use');
      $daterange->update($this->PD);
      $daterange->checkValid();
      if ($daterange->newObject || !$daterange->isValid) {
        $daterange->setDefaults(DR_PREVIOUS, DR_QUARTER);
        echo $daterange->display($this->PD);
      } elseif (! isset($this->PD['instrumentselected'])) {
        $this->instrumentSelect();
      } else {
        $this->returnReport();
      }
    } else {
      $this->selectReport();
    }
  }
  
  function mungePathData() {
    $this->PD = array();
    foreach ($_POST as $k => $v) {
      $this->PD[$k] = $v;
    }
    if (isset($this->PDATA[1])) {
      $this->PD['what'] = $this->PDATA[1];
    }
    echoData($this->PD, 1);
  }

  function selectReport() {
    global $BASEURL;
    $reportlist = array('groups'=>'Groups using instruments', 
                        'projects'=>'Projects using instruments', 
                        'users'=>'Users using instruments');
    $select = new AnchorTableList('Reports', 'Select which report to generate');
    $select->setValuesArray($reportlist, 'id', 'iv');
    $select->hrefbase = $BASEURL.'/report/';
    $select->setFormat('id', '%s', array('iv'));
    $select->numcols = 1;
    echo $select->display();
  }


  function instrumentSelect() {
    $select = new CheckBoxTableList('instrument', 'Select which instrument to view');
    $hidden = new TextField('instrument');
    $select->addFollowHidden($hidden);
    $chosen = new CheckBox('selected', 'Selected');
    $select->addCheckBox($chosen);
    //$select->numSpareCols = 1;
    $select->connectDB('instruments', array('id', 'name', 'longname'));
    $select->setFormat('id', '%s', array('name'), " %50.50s", array('longname'));
    $select->addFooter('(<a href="#" onclick="return deselectsome(%d,1);">deselect all</a>)<br />'.
                       '(<a href="#" onclick="return selectsome(%d,1);">select all</a>)');
    echo $select->display();
    echo '<input type="hidden" name="what" value="'.$this->PD['what'].'" />';
    echo '<input type="hidden" name="instrumentselected" value="1" />';
    echo '<input type="hidden" name="startdate" value="'.$this->PD['startdate'].'" />';
    echo '<input type="hidden" name="stopdate" value="'.$this->PD['stopdate'].'" />';
    echo '<input type="submit" name="submit" value="Select" />';
  }
  
  function returnReport() {
    $where = array('0');  //start the WHERE with 0 in case nothing was selected (always get valid SQL)
    $namebase = 'instrument-';
    for ($j=0; isset($this->PD[$namebase.$j.'-row']); $j++) {
      $instr = issetSet($this->PD,$namebase.$j.'-instrument');
      //echo "$j ($instr) => ($unbook, $announce)<br />";
      if (issetSet($this->PD,$namebase.$j.'-selected')) {
        $where[] ='(permissions.instrid='.qw($instr).' AND permissions.announce='.qw(1).')' ;
      }
    }
    #echo "Gathering email addresses: $q<br />";
    $list = new DBList('permissions', 'email', join($where, ' OR '), true);
    $list->join[] = (array('table' => 'users', 'condition' => 'users.id=permissions.userid'));
    $list->setFormat('%s', array('email'));
    $list->fill();
    if (count($list->data) == 0) {
      echo '<p>No email addresses found</p>';
    } else {
      $list->formatList();
      echo join($list->formatdata, xssqw($this->PD['separator']).'<br />');
    }
  }
}
?> 
