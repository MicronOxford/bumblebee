<?php
# $Id$
# JoinData: object to deal with JOINed data in the database, mimicks a Field
# for use in a DBRow although has autonomous data.

include_once('field.php');
include_once('dbrow.php');
include_once('db.php');

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
  *   $f = new JoinData("jointable",
                       "id1", $table1_key,
                       "fieldname", "label1");
  *   $f2 = new DropList("id2", "label2");
  *   $f2->connectDB("table2", array("id", "name"));
  *   $f2->list->prepend(array("-1","(none)"));
  *   $f2->setFormat("id", "%s", array("name"), " (%s)", array("longname"));
  *   $f->addElement($f2);
  *   $f3 = new TextField("field3", "");
  *   $f->addElement($f3, "sum_is_100");
  *   $f->joinSetup("id2", array('total' => 3));
 **/
class JoinData extends Field {
  var $joinTable,
      $jtLeftIDCol,
      $jtLeftID,
      $jtRightIDCol;
  var $protoRow,
      $rows;
  var $format,
      $number;
  var $radioclass = "item";
  var $groupValidTest;

  function JoinData($joinTable, $jtLeftIDCol, $jtLeftID,
                     $name, $description="") {
    $this->Field($name, "", $description);
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
  }

  function joinSetup($jtRightIDCol, $format="") {
    $this->jtRightIDCol = $jtRightIDCol;
    $this->format = (is_array($format) ? $format : array($format));
    $this->_fill();
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
    $this->rows[$rowNum]->changeNamebase($this->name.'-'.$rowNum.'-');
  }

  function _fill() {
    $sjtLeftID = qw($this->jtLeftID);
    $this->_calcMaxNumber();
    for ($i=0; $i < $this->number; $i++) {
      $this->_createRow($i);
      $this->rows[$i]->recNum = 1;
      $this->rows[$i]->recStart = $i;
      $this->rows[$i]->fill();
      $this->rows[$i]->restriction = $this->jtRightIDCol .'='. qw($this->rows[$i]->fields[$this->jtRightIDCol]->value); 
      $this->rows[$i]->insertRow = ! ($this->rows[$i]->fields[$this->jtRightIDCol]->value > 0);
    }
    return;
  }

  function display() {
    return $this->selectable();
  }

  function selectable() {
    $t = "";
    #$errorclass = ($this->isValid ? "" : "class='inputerror'");
    $errorclass = '';
    for ($i=0; $i<$this->number; $i++) { 
      $t .= "<tr $errorclass><td>\n";
      #$t .= "FOO$i";
      $t .= $this->rows[$i]->displayInTable(2);
      $t .= "</td></tr>\n";
    }
    return $t;
  }

  function displayInTable($cols) {
    $t = "<tr><td colspan='$cols'>$this->description</td></tr>\n";
    if ($this->editable) {
      $t .= $this->selectable();
    } else {
      $t .= $this->selectedvalue();
      $t .= "<input type='hidden' name='$this->name' value='$this->value' />";
    }
    $t .= "</td>\n";
    for ($i=0; $i<$cols-2; $i++) {
      $t .= "<td></td>";
    }
    $t .= "</tr>";
    return $t;
  }

  function update($data) {
    for ($i=0; $i < $this->number; $i++) {
      $rowchanged = $this->rows[$i]->update($data);
      if ($rowchanged) {
        foreach ($this->rows[$i]->fields as $k => $v) {
          #$this->rows[$i]->fields[$this->jtRightIDCol]->changed = $rowchanged;
          #if ($v->name != $this->jtRightIDCol && $v->name != $this->jtLeftIDCol) {
            $this->rows[$i]->fields[$k]->changed = $rowchanged;
          #}
        }
      }
      $this->changed += $rowchanged;
    }
    return $this->changed;
  }

  /**
   *  Count the number of rows in the join table so we know how many
   *  to retrieve
  **/ 
  function _countRowsInJoin() {
    #FIXME: stub function
    $q = "SELECT COUNT(*) "
        ."FROM $this->joinTable "
        ."WHERE $this->jtLeftIDCol=".qw($this->jtLeftID);
    #trigger_error("Stub function", E_USER_WARNING);
    $g = db_get_single($q);
    preDump($g);
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
    return "";
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
  
} // class JoinData

?> 
