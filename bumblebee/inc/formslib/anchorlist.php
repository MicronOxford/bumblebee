<?php
# $Id$
# anchor list (<li><a href="$href">$name</a></li>) for a ChoiceList

include_once("choicelist.php");

class AnchorList extends ChoiceList {
  var $hrefbase;
  var $ulclass,
      $liclass,
      $aclass;

  function AnchorList($name, $description="") {
    $this->ChoiceList($name, $description);
    #ChoiceList::ChoiceList($name, $description);
  }

  function display() {
    $ulclass = (isset($this->ulclass) ? " class='$this->ulclass'" : "");
    $liclass = (isset($this->liclass) ? " class='$this->liclass'" : "");
    $aclass  = (isset($this->aclass) ? " class='$this->aclass'" : "");
    $t  = "<ul title='$this->description'$ulclass>\n";
    foreach ($this->choices->list as $k => $v) {
      $t .= "<li$liclass>";
      $t .= "<a href='$this->hrefbase".$v['key']."'$aclass>".$v['value']."</a>";
      if (isset($v['longvalue'])) {
        $t .= " (".$v['longvalue'].")";
      }
      $t .= "</li>\n";
    }
    $t .= "</ul>\n";
    return $t;
  }

} // class ChoiceList


?> 
