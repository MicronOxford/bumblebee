<?php
# $Id$
# anchor list (<li><a href="$href">$name</a></li>) for a ChoiceList

include_once("choicelist.php");

class AnchorList extends ChoiceList {
  var $hrefbase;
  var $ulclass = "selectlist",
      $liclass = "item",
      $aclass  = "itemanchor";

  function AnchorList($name, $description="") {
    $this->ChoiceList($name, $description);
    #ChoiceList::ChoiceList($name, $description);
  }

  function format($data) {
    $aclass  = (isset($this->aclass) ? " class='$this->aclass'" : "");
    $t .= "<a href='$this->hrefbase".$data[$this->formatid]."'$aclass>"
         .$this->formatter[0]->format($data)
         ."</a>"
         .$this->formatter[1]->format($data);
    return $t;
  }

  function display() {
    $ulclass = (isset($this->ulclass) ? " class='$this->ulclass'" : "");
    $liclass = (isset($this->liclass) ? " class='$this->liclass'" : "");
    $t  = "<ul title='$this->description'$ulclass>\n";
    if (is_array($this->list->choicelist)) {
      foreach ($this->list->choicelist as $k => $v) {
        $t .= "<li$liclass>";
        #$t .= print_r($v, true);
        $t .= $this->format($v);
        $t .= "</li>\n";
      }
    }
    $t .= "</ul>\n";
    return $t;
  }

} // class AnchorList


?> 
