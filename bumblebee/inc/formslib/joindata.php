<?php
# $Id$
# JoinData: object to deal with JOINed data in the database, mimicks a Field
# for use in a DBRow although has autonomous data.
# If the element in the table is a selection list then the setup will be
# as a join table.
#
# We respect the 'field' interface while overriding pretty much all of it.

include_once('field.php');
include_once('fieldarray.php');

class JoinData extends Field {
  var $joinTable,
      $jtOuterColumn,
      $jtoVal;
  #var $controlfield;
  var $fields;
  #var $matchfield;
  var $values;
  var $format,
      $number;
  var $radioclass = "item";
  var $elements,
      $groupValidTest;

  function JoinData($joinTable, $jtOuterColumn, $jtoVal,
                     $name, $description="") {
    $this->Field($name, "", $description);
    $this->joinTable = $joinTable;
    $this->jtOuterColumn = $jtOuterColumn;
    $this->jtoVal = $jtoVal;
    $this->fields = array();
    $this->elements = array();
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
      return;
    } else {
      $this->number = $this->$numrows;
    }
  }

  function addElement($field, $groupValidTest=NULL) {
    $this->elements[] = $field;
    $this->groupValidTest[] = $groupValidTest;
  }

  function _createRow() {
    $this->fields[] = new FieldArray($this->name.'-'.count($this->fields).'-',
                                     $this->elements);
  }
  /*
    $i = count($this->fields);
    $this->fields[$i] = array();
    for ($j=0; $j<count($this->elements); $j++) {
      $j++;
      $this->fields[$i][$j] = $this->elements[$j];
      $this->fields[$i][$j]->namebase = $this->name."-$i-";
    }
  }*/

  function _fill() {
    $sjtoVal = qw($this->jtoVal);
    #$getfields = array($this->matchfield);
    foreach ($this->elements as $k=>$v) {
      $getfields[] = $v->name;
    }
    $this->values = new DBList($this->joinTable, $getfields,
                              "$this->jtOuterColumn = $sjtoVal",
                              $this->jtOuterColumn,
                              $this->jtOuterColumn);
    #echo "<pre>".print_r($this->values,1)."</pre>";
    $this->_calcMaxNumber($this->values->length);
    for ($i=0; $i<$this->number; $i++) {
      $this->_createRow();
      for ($j=0; $j<count($this->elements); $j++) {
        if (isset($this->values->list[$i][$this->elements[$j]->name])) {
          #echo $this->values->list[$i][$this->matchfield];
          ##############FIXME
          #$this->fields[$i][$j]->value = $this->values->list[$i][$this->elements[$j]->value];
          $this->fields[$i]->set($j, $this->values->list[$i][$this->elements[$j]->value]);
        }
      }
    }
  }

  function display() {
    return $this->selectable();
  }

/*
  function format($data) {
    //$aclass  = (isset($this->aclass) ? " class='$this->aclass'" : "");

    #echo "<pre>".print_r($data,1)."</pre>";
    #echo $this->value;
    $selected = ($data[$this->formatid] == $this->value ? " checked='1' " : "");
    $t  = "<input type='radio' name='$this->name' "
         ."value='".$data[$this->formatid]."' $selected /> ";
    foreach ($this->formatter as $k => $v) {
      $t .= $this->formatter[$k]->format($data);
    }
    if (isset($data['_field']) && $data['_field']) {
      $t .= $data['_field']->selectable();
    }
    return $t;
  }
*/

  function selectable() {
    $t = "";
    $errorclass = ($this->isvalid ? "" : "class='inputerror'");
    for ($i=0; $i<$this->number; $i++) { 
      $t .= "<tr $errorclass>\n";
      for ($j=0; $j < count($this->elements); $j++) {
        $t .= '<td>';
        #$t .= $this->fields[$i][$j]->selectable();
        $t .= $this->fields[$i]->selectable($j);
        $t .= "</td>\n";
      }
      $t .= "</tr>\n";
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
    for ($i=0; $i < count($this->fields); $i++) {
      for ($j=0; $j < count($this->elements); $j++) {
        #$this->changed += $this->fields[$i][$j]->update($data);
        $this->changed += $this->fields[$i]->update($j, $data);
      }
    }
    //return $this->changed;
    $this->changed = 0;
    return 0;
  }
  
} // class JoinData

?> 

