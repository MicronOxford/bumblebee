<?php
# $Id$
# JoinData: object to deal with JOINed data in the database, mimicks a Field
# for use in a DBRow although has autonomous data.

include_once 'field.php';
include_once 'dbrow.php';
include_once 'inc/db.php';

/**
  * If the element in the table is a selection list then the setup will be
  * as a join table.
  *
  * We respect the 'field' interface while overriding pretty much all of it.
  *
  * Primitive class for managing join data. Can be used on its own to just
  * join data or with a selection lists class to make a join table.
  * This may be used to determine the choices
  * that a user is permitted to select (e.g. dropdown list or radio buttons)
  *
  * Used in a many:many or many:1 relationships (i.e. a field in a 
  * table that is the listed in a join table 
  *
  * Typical usage:
  *   $f = new JoinData('jointable',
                       'id1', $table1_key,
                       'fieldname', 'label1');
  *   $f2 = new DropList('id2', 'label2');
  *   $f2->connectDB('table2', array('id', 'name'));
  *   $f2->list->prepend(array('-1','(none)'));
  *   $f2->setFormat('id', '%s', array('name'), ' (%s)', array('longname'));
  *   $f->addElement($f2);
  *   $f3 = new TextField('field3', '');
  *   $f->addElement($f3, 'sum_is_100');
  *   $f->joinSetup('id2', array('total' => 3));
  */
class JoinData extends Field {
  var $joinTable;
  var $jtLeftIDCol;
  var $jtLeftID;
  var $jtRightIDCol;
  var $protoRow;
  var $rows;
  var $colspan;
  var $format;
  var $number = 0;
  var $radioclass = 'item';
  var $groupValidTest;
  var $fatalsql = 0;

  function JoinData($joinTable, $jtLeftIDCol, $jtLeftID,
                     $name, $description='') {
//     $this->DEBUG=10;
    $this->Field($name, '', $description);
    $this->joinTable = $joinTable;
    $this->jtLeftIDCol = $jtLeftIDCol;
    $this->jtLeftID = $jtLeftID;
    $this->protoRow = new DBRow($joinTable, $jtLeftID, $jtLeftIDCol);
    $field = new Field($jtLeftIDCol);
    $this->protoRow->addElement($field);
    $this->protoRow->editable = 1;
    $this->protoRow->autonumbering = 0;
    $this->rows = array();
    $this->groupValidTest = array();
    $this->notifyIdChange = 1;
  }

  function joinSetup($jtRightIDCol, $format='') {
    $this->jtRightIDCol = $jtRightIDCol;
    $this->format = (is_array($format) ? $format : array($format));
    $this->_fill();
    //preDump($this);
  }

  function _calcMaxNumber() {
    if (isset($this->format['total'])) {
      $this->number = $this->format['total'];
      return;
    }
    $this->number = $this->_countRowsInJoin();
    if (isset($this->format['minspare'])) {
      $this->number += $this->format['minspare'];
      return;
    }
  }

  function addElement($field, $groupValidTest=NULL) {
    $this->protoRow->addElement($field);
    $this->groupValidTest[$field->name] = $groupValidTest;
  }

  function _createRow($rowNum) {
    $this->rows[$rowNum] = $this->protoRow;
    $this->rows[$rowNum]->setNamebase($this->name.'-'.$rowNum.'-');
  }

  function _fill() {
    $sjtLeftID = qw($this->jtLeftID);
    $this->_fillFromProto();
    return;
  }

  function _fillFromProto() {
    $oldnumber = $this->number;
    $this->_calcMaxNumber();
    $this->log('Extending rows from '.$oldnumber.' to '.$this->number);
    for ($i=$oldnumber; $i < $this->number; $i++) {
      $this->_createRow($i);
      $this->rows[$i]->recNum = 1;
      $this->rows[$i]->recStart = $i;
      $this->rows[$i]->fill();
      $this->rows[$i]->restriction = $this->jtRightIDCol .'='. qw($this->rows[$i]->fields[$this->jtRightIDCol]->value); 
      $this->rows[$i]->insertRow = ! ($this->rows[$i]->fields[$this->jtRightIDCol]->value > 0);
    }
  }
  
  function display() {
    //check how many fields we need to have (again) as we might have to show more this time around.
    $this->_fillFromProto();
    return $this->selectable();
  }

  function selectable() {
    $t = '';
    #$errorclass = ($this->isValid ? '' : "class='inputerror'");
    $errorclass = '';
    for ($i=0; $i<$this->number; $i++) { 
      $t .= "<tr $errorclass><td colspan='$this->colspan'>\n";
      #$t .= "FOO$i";
      $t .= $this->rows[$i]->displayInTable(2);
      $t .= "</td></tr>\n";
    }
    return $t;
  }
  
  function selectedValue() {
    return $this->selectable();
  }

  function displayInTable($cols) {
    //$cols += $this->colspan;
    $t = "<tr><td colspan='$cols'>$this->description</td></tr>\n";
    if ($this->editable) {
      $t .= $this->selectable();
    } else {
      //preDump($this);
      //preDump(debug_backtrace());
      $t .= $this->selectedValue();
      $t .= "<input type='hidden' name='$this->name' value='$this->value' />";
    }
    $t .= "</td>\n";
    for ($i=0; $i<$cols-2; $i++) {
      $t .= '<td></td>';
    }
    $t .= '</tr>';
    return $t;
  }

  function update($data) {
    for ($i=0; $i < $this->number; $i++) {
      $rowchanged = $this->rows[$i]->update($data);
      if ($rowchanged) {
        $this->log('JoinData-Row '.$i.' has changed.');
        foreach ($this->rows[$i]->fields as $k => $v) {
          #$this->rows[$i]->fields[$this->jtRightIDCol]->changed = $rowchanged;
          #if ($v->name != $this->jtRightIDCol && $v->name != $this->jtLeftIDCol) {
            $this->rows[$i]->fields[$k]->changed = $rowchanged;
          #}
        }
      }
      $this->changed += $rowchanged;
    }
    $this->log('Overall JoinData row changed='.$this->changed);
    return $this->changed;
  }

  /**
   *  Count the number of rows in the join table so we know how many
   *  to retrieve
  **/ 
  function _countRowsInJoin() {
    $g = quickSQLSelect($this->joinTable, $this->jtLeftIDCol, $this->jtLeftID, $this->fatalsql, 1);
    return $g[0];
  }

  /**
    * trip the complex field within us to sync(), which allows us
    * to then know our actual value (at last).
   **/
  function sqlSetStr() {
    #echo "JoinData::sqlSetStr";
    $this->_joinSync();
    //We return an empty string as this is only a join table entry,
    //so it has no representation within the row itself.
    return '';
  }

  /**
    * synchronise the join table
   **/
  function _joinSync() {
    for ($i=0; $i < $this->number; $i++) {
      if ($this->rows[$i]->fields[$this->jtRightIDCol]->value == 0
       && $this->rows[$i]->fields[$this->jtRightIDCol]->changed) {
        //then this row is to be deleted...
        $this->changed += ! $this->rows[$i]->delete();
      } else {
        $this->log('JoinData::_joinSync(): Syncing row '.$i);
        $this->changed += ! $this->rows[$i]->sync();
      }
    }
  }
  
  /**
   * override the isValid method of the Field class, using the
   * checkValid method of each member row completed as well as 
   * cross checks on other fields.
  **/
  function isValid() {
    $this->isValid = 1;
    for ($i=0; $i < $this->number; $i++) {
      #echo "val". $this->rows[$i]->fields[$this->jtRightIDCol]->value.";";
      if ($this->rows[$i]->fields[$this->jtRightIDCol]->value == 0
         && $this->rows[$i]->changed) {
        $this->rows[$i]->isValid = 1;
      }
      if ($this->rows[$i]->fields[$this->jtRightIDCol]->value > 0) {
      #if ($this->rows[$i]->changed) {
        $this->isValid = $this->rows[$i]->checkValid() && $this->isValid;
      }
      #echo "JoinData::isValid = '$this->isValid'";
    }
    //now we need to check the validity of sets of data (e.g. sum of the same
    //field across the different rows.
    foreach ($this->rows[0]->fields as $k => $f) {
      if (isset($this->groupValidTest[$f->name])) {
        $allvals = array();
        for ($i=0; $i < $this->number; $i++) {
          if ($this->rows[$i]->fields[$this->jtRightIDCol]->value > 0) {
            $allvals[] = $this->rows[$i]->fields[$k]->value;
          }
        }
        $fieldvalid = ValidTester($this->groupValidTest[$f->name], $allvals);
        if (! $fieldvalid) {
          for ($i=0; $i < $this->number; $i++) {
            $this->rows[$i]->fields[$k]->isValid = 0;
          }
        }
        $this->isValid = $fieldvalid && $this->isValid;
      }
    }
    return $this->isValid;
  }
  
  function idChange($newId) {
    for ($i=0; $i < $this->number; $i++) {
      $this->rows[$i]->setId($newId);
    }
  }
  
  function setNamebase($namebase='') {
    for ($i=0; $i < $this->number; $i++) {
      $this->rows[$i]->setNamebase($namebase);
    }
    $this->protoRow->setNamebase($namebase);
  }

  function setEditable($editable=false) {
    for ($i=0; $i < $this->number; $i++) {
      $this->rows[$i]->setEditable($editable);
    }
    $this->protoRow->setEditable($editable);
  }
  
} // class JoinData

?> 
