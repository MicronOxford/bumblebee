<?php
# $Id$
# choice list (to be encapsulated in a select, list of a hrefs etc

include_once "field.php";

class ChoiceList extends Field {
  var $name,
      $description;
  var $choices;

  function ChoiceList($name, $description="") {
    $this->name = $name;
    $this->description = $description;
    $this->choices = array();
  }

  function setChoices($newlist) {
    #newlist should be a "SimpleList" object, or compatible
    $this->choices = $newlist;
  }

  function text_dump() {
    $t  = "<pre>";
    foreach ($this->choices as $k => $v) {
      $t .= "$k =&gt; $v ";
      $t .= "\n";
    }
    $t .= "</pre>";
    return $t;
  }

  function display() {
    return $this->text_dump();
  }

  function displayInTable($cols) {
    $t = "<tr><td>$this->description</td>\n"
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
    echo "updating $this->name (ov: $this->value)";
    Field::update($data);
    echo " (nv: $this->value)";
    if ($this->value == -1) {
      #FIXME... this needs to be cleaned up and put elsewhere... where?
      $newchoice = new DBO("userclass",-1);
      $newchoice->editable=1;
      $newchoice->namebase = "newcharge-";
      $newchoice->addElement(new Field("id"));
      $newchoice->addElement(new Field("name"));
      $newchoice->fill();
      echo $newchoice->text_dump();
      $newchoice->update($data, "newcharge-");
      echo $newchoice->text_dump();
      $newchoice->sync();
      $this->value = $newchoice->fields['id']->value;
    }
    return $this->changed;
  }

} // class ChoiceList

?> 
