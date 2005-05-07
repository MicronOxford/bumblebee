<?php
# $Id$
# Multi-type list: has an initial selector with a JOINed object attached to it
# (using a join table)
# This behaves as a single field in the sense that it is all JOINed info,
# so we respect the 'field' interface while overriding pretty muchh all of it.

// NOTE: This code not currently used in BUMBLEBEE so it might not be very well debugged, it at all!

include_once 'field.php';

class MultiList extends Field {
  var $joinTable;
  var $jtOuterColumn;
  var $jtoVal;
  var $controlfield;
  var $fields;
  var $matchfield;
  var $values;
  var $format;
  var $number;
  var $radioclass = 'item';
  var $elements;

  function MultiList($joinTable, $jtOuterColumn, $jtoVal,
                     $name, $description='') {
    $this->Field($name, '', $description);
    $this->joinTable = $joinTable;
    $this->jtOuterColumn = $jtOuterColumn;
    $this->jtoVal = $jtoVal;
    $this->elements = array();
  }

  function controlField(&$f, $matchfield, $format='') {
    $this->controlfield = $f;
    $this->matchfield = $matchfield;
    $this->format = (is_array($format) ? $format : array($format));
    if (isset($this->format['total'])) {
      $this->number = $this->format['total'];
    } else {
      $this->number = count($f->list);
    }
    $this->_createFields();
    $this->_fill();
  }

  function addElement($field) {
    $this->elements[] = $field;
  }

  function _createFields() {
    $this->fields = array();
    for ($i = 0; $i < $this->number; $i++) {
      $j=0;
      $this->fields[$i] = array();
      $this->fields[$i][$j] = $this->controlfield; //**COPY**
      $this->fields[$i][$j]->namebase = $this->name.'-$i-';
      foreach ($this->elements as $k => $v) {
        $j++;
        $this->fields[$i][$j] = $v;
        $this->fields[$i][$j]->namebase = $this->name.'-$i-';
      }
    }
  }

  function _fill() {
    $sjtoVal = qw($this->jtoVal);
    $getfields = array($this->matchfield);
    foreach ($this->elements as $k=>$v) {
      $getfields[] = $v->name;
    }
    $this->values = new DBList($this->joinTable, $getfields,
                              "$this->jtOuterColumn = $sjtoVal",
                              $this->jtOuterColumn,
                              $this->jtOuterColumn);
    #echo "<pre>".print_r($this->values,1)."</pre>";
    for ($i=0; $i<$this->number; $i++) {
      if (isset($this->values->list[$i][$this->matchfield])) {
        #echo $this->values->list[$i][$this->matchfield];
        $this->fields[$i][0]->value = $this->values->list[$i][$this->matchfield];
      }
      for ($j=0; $j<count($this->elements); $j++) {
        if (isset($this->values->list[$i][$this->elements[$j]->name])) {
          #echo $this->values->list[$i][$this->matchfield];
          $this->fields[$i][$j+1]->value = $this->values->list[$i][$this->elements[$j]->name];
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
    $errorclass = ($this->invalid ? "class='inputerror'" : "");
    foreach ($this->fields as $k => $v) {
      $t .= "<tr $errorclass>\n";
      #$t .= $this->fields[$k][0]->selectable();
      #$t .= "</td>\n";
      for ($j=0; $j <= count($this->elements); $j++) {
        $t .= '<td>';
        $t .= $this->fields[$k][$j]->selectable();
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
      $t .= $this->selectedValue();
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
        $this->changed += $this->fields[$i][$j]->update($data);
      }
    }
    return $this->changed;
  }
  
} // class MultiList

?> 

