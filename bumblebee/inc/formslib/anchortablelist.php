<?php
# $Id$
# anchor list (<li><a href="$href">$name</a></li>) for a ChoiceList

include_once("anchorlist.php");

class AnchorTableList extends AnchorList {
  var $hrefbase   = "";
  var $trclass    = "itemrow",
      $tdlclass   = "itemL",
      $tdrclass   = "itemR",
      $aclass     = "itemanchor",
      $tableclass = "selectlist";

  function AnchorTableList($name, $description="") {
    $this->AnchorList($name, $description);
  }

  function format($data) {
    $aclass  = (isset($this->aclass) ? " class='$this->aclass'" : "");
    $trclass  = (isset($this->trclass) ? " class='$this->trclass'" : "");
    $tdlclass  = (isset($this->tdlclass) ? " class='$this->tdlclass'" : "");
    $tdrclass  = (isset($this->tdrclass) ? " class='$this->tdrclass'" : "");
    $t .= "<tr $trclass>"
         ."<td $tdlclass>";
    $t .= "<a href='$this->hrefbase".$data[$this->formatid]."'$aclass>"
         .$this->formatter[0]->format($data)
         ."</a>";
    $t .= "</td><td $tdrclass>"
         .$this->formatter[1]->format($data);
    $t .= "</td></tr>";
    return $t;
  }

  function display() {
    $tableclass = (isset($this->tableclass) ? " class='$this->tableclass'" : "");
    $t  = "<table title='$this->description' $tableclass>\n";
    if (is_array($this->list->list)) {
      foreach ($this->list->list as $k => $v) {
        $t .= $this->format($v);
      }
    }
    $t .= "</table>\n";
    return $t;
  }

} // class AnchorList


?> 
