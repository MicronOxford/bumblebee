<?php
# $Id$
# JoinData: object to deal with JOINed data in the database, mimicks a Field
# for use in a DBRow although has autonomous data.

include_once('field.php');
include_once('dbrow.php');

/**
  * If the element in the table is a selection list then the setup will be
  * as a join table.
  *
  * We respect the 'field' interface while overriding pretty much all of it.
  *
  * Primitive class on which selection lists can be built from the
  * results of an SQL query. This may be used to determine the choices
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
      $jtOuterColumn,
      $jtoVal;
  var $protoRow,
      $rows;
  var $values;
  var $format,
      $number;
  var $radioclass = "item";
  var $groupValidTest;

  function JoinData($joinTable, $jtOuterColumn, $jtoVal,
                     $name, $description="") {
    $this->Field($name, "", $description);
    $this->joinTable = $joinTable;
    $this->jtOuterColumn = $jtOuterColumn;
    $this->jtoVal = $jtoVal;
    $this->protoRow = new DBRow($joinTable, $jtoVal, $jtOuterColumn);
    $field = new Field($jtOuterColumn);
    $this->protoRow->addElement($field);
    $this->protoRow->editable = 1;
    $this->rows = array();
    $this->groupValidTest = array();
  }

  function joinSetup($matchfield, $format="") {
    $this->matchfield = $matchfield;
    $this->format = (is_array($format) ? $format : array($format));
    $this->_fill();
    preDump($this);
  }

  function _calcMaxNumber($numrows) {
    if (isset($this->format['total'])) {
      $this->number = $this->format['total'];
    } else {
      $this->number = $this->$numrows;
    }
  }

  function addElement($field, $groupValidTest=NULL) {
    $this->protoRow->addElement($field);
    $this->groupValidTest[] = $groupValidTest;
  }

  function _createRow($rowNum) {
    #$this->rows[] = $this->protoRow;
    $newrow = $this->protoRow;
    #foreach ($this->protoRow->fields as $k => $f) {
      #$newrow->fields[$k] = $f;
      #$newrow->fields[$k]->list = $f->list;
    #}
    #$newrow = new DBRow($this->joinTable, $this->jtoVal, $this->jtOuterColumn);
    #$field = new Field($this->jtOuterColumn);
    #$newrow->addElement($field);
    #$newrow->editable = 1;
    $this->rows[$rowNum] = $newrow;
    $this->rows[$rowNum]->changeNamebase($this->name.'-'.$rowNum.'-');
  }

  function _fill() {
    $sjtoVal = qw($this->jtoVal);
    $this->_calcMaxNumber($this->values->length);
    for ($i=0; $i < $this->number; $i++) {
      $this->_createRow($i);
      //$this->rows[$i]->recNum = 1;
      //$this->rows[$i]->recStart = $i;
      //$this->rows[$i]->fill();
      if ($i==0) {
        $this->rows[$i]->fields['groupid']->value = $i+1;
        $this->rows[$i]->fields['groupid']->list->id = $i+5;
        $this->rows[$i]->fields['groupid']->list->idfield = $i+9;
        $this->rows[$i]->fields['groupid']->formatter[]=$i;
      }
      ## FIXME: changing rows[$i]->list somehow changes rows[0]->list
      echo "$i:";
      preDump($this->rows[$i]);
      preDump($this->rows[0]);
    }
    echo "FOOOOOOO";
    preDump($this);
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
      $this->changed += $this->rows[$i]->update($data);
    }
    return $this->changed;
  }

  /**
    * trip the complex field within us to sync(), which allows us
    * to then know our actual value (at last).
   **/
  function sqlSetStr() {
    echo "JoinData::sqlSetStr";
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
      $this->changed += ! $this->rows[$i]->sync();
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
      echo "val". $this->rows[$i]->fields[$this->matchfield]->value.";";
      if ($this->rows[$i]->fields[$this->matchfield]->value > 0) {
        $this->isValid = $this->rows[$i]->checkValid() && $this->isValid;
      }
      echo "JoinData::isValid = '$this->isValid'";
    }
    return $this->isValid;
  }
  
} // class JoinData

?> 
