<?php
# $Id$
# dropdown list (<select><option ...> $description</option>) for a ChoiceList

include_once 'choicelist.php';

class DropList extends ChoiceList {

  function DropList($name, $description='') {
    $this->ChoiceList($name, $description);
    $this->extendable = 0;
  }

  function display() {
    return $this->selectable();
  }

  function format($data) {
    //preDump($this->formatid);
    //preDump($data);
    $data['_field'] = '0';
    $selected = ($data[$this->formatid] == $this->getValue() ? " selected='1' " : '');
    $t  = '<option '
         ."value='".$data[$this->formatid]."' $selected> ";
    foreach (array_keys($this->formatter) as $k) {
      $t .= $this->formatter[$k]->format($data);
    }
    //if (isset($data['_field']) && $data['_field']) {
    //  echo 'foo'.$data['_field'].'bar';
    //  $t .= $data['_field']->selectable();
    //}
    $t .= "</option>\n";
    return $t;
  }


  function selectable() {
    $t = "<select name='$this->namebase$this->name'>";
    foreach ($this->list->choicelist as $v) {
//       echo "droplist: $k => $v<br />\n";
      //preDump($v);
      $t .= $this->format($v);
    }
    $t .= "</select>";
    return $t;
  }

  function selectedValue() {
    $value = $this->getValue();
    foreach ($this->list->choicelist as $data) {
      if ($data[$this->formatid] == $value) {
        break;
      }
    }
    //preDump($data);
    $t  = '<input type="hidden" '
          .'value="'.$data[$this->formatid].'" /> ';
    foreach (array_keys($this->formatter) as $k) {
      $t .= $this->formatter[$k]->format($data);
    }
    $t .= "\n";
    return $t;
  }
  
} // class DropList


?> 
