<?php
# $Id$
# dropdown list (<select><option ...> $description</option>) for a ChoiceList

include_once("choicelist.php");

class DropList extends ChoiceList {

  function DropList($name, $description="") {
    $this->ChoiceList($name, $description);
    $this->extendable = 0;
  }

  function display() {
    return $this->selectable();
  }

  function format($data) {
    //$aclass  = (isset($this->aclass) ? " class='$this->aclass'" : "");

    #echo "<pre>".print_r($data,1)."</pre>";
    #echo $this->value;
    $selected = ($data[$this->formatid] == $this->value ? " selected='1' " : "");
    $t  = "<option "
         ."value='".$data[$this->formatid]."' $selected /> ";
    foreach ($this->formatter as $k => $v) {
      $t .= $this->formatter[$k]->format($data);
    }
    if (isset($data['_field']) && $data['_field']) {
      $t .= $data['_field']->selectable();
    }
    $t .= "</option>\n";
    return $t;
  }


  function selectable() {
    $t = "<select name='$this->namebase$this->name'>";
    foreach ($this->list->list as $k => $v) {
      $t .= $this->format($v);
    }
    $t .= "</select>";
    return $t;
  }

} // class ChoiceList


?> 
