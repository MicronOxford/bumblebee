<?php
# $Id$
# choice list (to be encapsulated in a select, list of a hrefs etc

include_once "field.php";
include_once "outputformatter.php";
include_once "dblist.php";

class ChoiceList extends Field {
  var $name,
      $description;
  var $list;
  var $formatter, $formatid;

  function ChoiceList($name, $description="") {
    $this->name = $name;
    $this->description = $description;
  }

  function connectDB($table, $fields="", $restriction="1", $order="name") {
    $this->list = new DBList($table, $fields, $restriction, $order);
  }

  function text_dump() {
    return $this->list->text_dump();
  }

  function display() {
    return $this->text_dump();
  }

  function setFormat($id, $f1, $f2, $v1, $v2) {
    $this->formatid = $id;
    $this->formatter = array(new OutputFormatter($f1, $v1),
                             new OutputFormatter($f2, $v2));
  }

  function format($data) {
    $s = $data[$this->formatid] .":". $this->formatter[0]->format($data)
        ."(". $this->formatter[1]->format($data).")";
    return $s;
  }

  function displayInTable($cols) {
    $errorclass = ($this->invalid ? "class='inputerror'" : "");
    $t = "<tr $errorclass><td>$this->description</td>\n"
        ."<td title='$this->description'>";
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
    #echo "updating $this->name (ov: $this->value)";
    Field::update($data);
    $this->list->update($this->value, $data);
    $this->value = $this->list->id;
    $this->changed += $this->list->changed;
    #echo " (nv: $this->value)";
    return $this->changed;
  }

} // class ChoiceList

?> 
