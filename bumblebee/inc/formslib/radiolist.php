<?php
# $Id$
# radio list (<input type='radio' ...> $description</ br>) for a ChoiceList

include_once("choicelist.php");

class RadioList extends ChoiceList {

  function AnchorList($name, $description="") {
    $this->ChoiceList($name, $description);
    #ChoiceList::ChoiceList($name, $description);
  }

  function display() {
    return $this->selectable();
  }

  function selectable() {
    foreach ($this->choices->list as $k => $v) {
      $selected = ($v['key'] == $this->value ? " checked='1' " : "");
      #echo "$k, ".$v['key'].", $this->value, $selected <br />";
      $t .= "<input type='radio' name='$this->name' ".
            "value='".$v['key']."'$selected> ".$v['value'];
      if (isset($v['longvalue']) && $v['longvalue'] != "") {
        $t .= " (".$v['longvalue'].")";
      }
      if (isset($v['field']) && $v['field']) {
        $t .= $v['field']->selectable();
      }
      $t .= "<br />\n";
    }
    return $t;
  }

} // class RadioList


?> 
