<?php
# $Id$
# radio list (<input type='radio' ...> $description</ br>) for a ChoiceList

include_once("choicelist.php");

class RadioList extends ChoiceList {
  var $radioclass = "item";

  function RadioList($name, $description="") {
    echo "Constructor: $this->value";
    $this->ChoiceList($name, $description);
    echo "Constructor: $this->value";
  }

  function display() {
    return $this->selectable();
  }

  function format($data) {
    //$aclass  = (isset($this->aclass) ? " class='$this->aclass'" : "");

    #echo "<pre>".print_r($data,1)."</pre>";
    echo $this->value;
    $selected = ($data[$this->formatid] == $this->value ? " checked='1' " : "");
    $t  = "<input type='radio' name='$this->name' "
         ."value='".$data[$this->formatid]."' $selected /> "
         .$this->formatter[0]->format($data)
         .$this->formatter[1]->format($data);
    if (isset($data['_field']) && $data['_field']) {
      $t .= $data['_field']->selectable();
    }
    return $t;
  }


  function selectable() {
    $t = "";
    foreach ($this->list->list as $k => $v) {
      $t .= $this->format($v);
      $t .= "<br />\n";
    }
    return $t;
  }

} // class RadioList


?> 
