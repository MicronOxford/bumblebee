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

  function setFormat() {
    #called as: setFormat($id, $f1, $v1, $f2, $v2, ...) {
    #f1, v1 etc must be in pairs.
    #f1 is an sprintf format and v1 is an array of names that
    #will be used to fill the sprintf format
    $argc = func_num_args();
    $argv = func_get_args();
    #echo "<pre>Var args: $argc\n".print_r($argv,1)."</pre>";
    $this->formatid = $argv[0];
    $this->formatter = array();
    for ($i = 1; $i < $argc; $i+=2) {
      #echo "<pre>Adding: $i\n".print_r($argv[$i],1).print_r($argv[$i+1],1)."</pre>";
      $this->formatter[] = new OutputFormatter($argv[$i],
                                               $argv[$i+1]);
    }
  }

  function format($data) {
    $s = $data[$this->formatid] .":". $this->formatter[0]->format($data)
        ."(". $this->formatter[1]->format($data).")";
    return $s;
  }

  function displayInTable($cols) {
    $errorclass = ($this->isvalid ? "" : "class='inputerror'");
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
    #echo $this->list->id;
    Field::update($data);
    $this->list->update($this->value, $data);
    if ($this->list->id != -1) {
      #if the data were valid, then list->update() would change list->id from
      # -1 to the real value of the newly created entry
      $this->value = $this->list->id;
      $this->changed += $this->list->changed;
    }
    #echo $this->list->id;
    #echo " (nv: $this->value)";
    return $this->changed;
  }

  function set($value) {
    $this->list->set($value);
    Field::set($value);
  }

} // class ChoiceList

?> 
