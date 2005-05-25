<?php
# $Id$
# radio list (<input type='radio' ...> $description<br />) for a ChoiceList

include_once 'choicelist.php';

class RadioList extends ChoiceList {
  var $radioclass = 'item';

  function RadioList($name, $description='') {
    //$this->DEBUG = 10;
    $this->ChoiceList($name, $description);
  }

  function display() {
    return $this->selectable();
  }

  function format($data) {
    //$aclass  = (isset($this->aclass) ? " class='$this->aclass'" : "");
    #echo "<pre>".print_r($data,1)."</pre>";
    #echo $this->value;
    $selected = ($data[$this->formatid] == $this->getValue() ? ' checked="1" ' : '');
    $t  = '<label><input type="radio" name="'.$this->name.'" '  
         .'value="'.$data[$this->formatid].'" '.$selected.' /> ';
    foreach (array_keys($this->formatter) as $k) {
      $t .= $this->formatter[$k]->format($data);
    }
    $t .= '</label>';
    if (isset($data['_field']) && $data['_field']) {
      $t .= $data['_field']->selectable();
    }
    return $t;
  }


  function selectable() {
    $t = '';
    foreach ($this->list->choicelist as $v) {
      $t .= $this->format($v);
      $t .= "<br />\n";
    }
    return $t;
  }

} // class RadioList


?> 
