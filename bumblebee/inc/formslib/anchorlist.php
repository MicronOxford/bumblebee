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

  function format($data) {
    $t .= "<a href='$this->hrefbase".$data[$this->formatid]."'$aclass>"
         .$this->formatter[0]->format($data)
         ."</a>"
         .$this->formatter[1]->format($data);
    return $t;
  }

  function display() {
    $ulclass = (isset($this->ulclass) ? " class='$this->ulclass'" : "");
    $liclass = (isset($this->liclass) ? " class='$this->liclass'" : "");
    $aclass  = (isset($this->aclass) ? " class='$this->aclass'" : "");
    $t  = "<ul title='$this->description'$ulclass>\n";
    if (is_array($this->list->list)) {
      foreach ($this->list->list as $k => $v) {
        $t .= "<li$liclass>";
        #$t .= print_r($v, true);
        $t .= $this->format($v);
        #$t .= "<a href='$this->hrefbase".$v['key']."'$aclass>".$v['value']."</a>";
        #if (isset($v['longvalue']) && $v['longvalue'] != "") {
          #$t .= " (".$v['longvalue'].")";
        #}
        $t .= "</li>\n";
      }
    }
    $t .= "</ul>\n";
    return $t;
  }

} // class AnchorList


?> 
