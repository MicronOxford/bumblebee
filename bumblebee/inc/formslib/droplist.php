<?php
# $Id$
# dropdown list (<select><option ...> $description</option>) for a ChoiceList

include_once 'choicelist.php';

class DropList extends ChoiceList {

  function DropList($name, $description="") {
    $this->ChoiceList($name, $description);
    $this->extendable = 0;
  }

  function display() {
    return $this->selectable();
  }

  function format($data) {
//     $data['_field'] = '0';
    //$aclass  = (isset($this->aclass) ? " class='$this->aclass'" : "");
//     preDump(debug_backtrace());
//     echo "<pre>".print_r($data,1)."</pre>";
    #echo $this->value;
    $selected = ($data[$this->formatid] == $this->getValue() ? " selected='1' " : '');
    $t  = '<option '
         ."value='".$data[$this->formatid]."' $selected /> ";
    foreach ($this->formatter as $k => $v) {
      $t .= $this->formatter[$k]->format($data);
    }
//     preDump($this->formatid);
//     preDump($data);
    if (isset($data['_field']) && $data['_field']) {
      echo 'foo'.$data['_field'].'bar';
      $t .= $data['_field']->selectable();
    }
    $t .= "</option>\n";
    return $t;
  }


  function selectable() {
    $t = "<select name='$this->namebase$this->name'>";
    foreach ($this->list->choicelist as $k => $v) {
//       echo "droplist: $k => $v<br />\n";
      $t .= $this->format($v);
    }
    $t .= "</select>";
    return $t;
  }

} // class DropList


?> 
