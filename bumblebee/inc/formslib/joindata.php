<?php
# $Id$
# JoinData: object to deal with JOINed data in the database, mimicks a Field
# for use in a DBRow although has autonomous data.

include_once('field.php');
include_once('fieldarray.php');

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
  var $fields;
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
      for ($j=0; $j<count($this->elements) && $i<$this->values->length; $j++) {
        if (isset($this->values->list[$i][$this->elements[$j]->name])) {
          echo $i;
          echo ($this->elements[$j]->name);
          $this->fields[$i]->set($j, $this->values->list[$i][$this->elements[$j]->name]);
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
    $errorclass = ($this->isValid ? "" : "class='inputerror'");
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
        $this->changed += $this->fields[$i]->update($j, $data);
      }
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
    //FIXME 
    for ($i=0; $i < count($this->fields); $i++) {
      for ($j=0; $j < count($this->elements); $j++) {
        $this->changed += $this->fields[$i]->update($j, $data);
      }
    }
  }
  
} // class JoinData

?> 
