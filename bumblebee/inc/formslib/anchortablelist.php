<?php
# $Id$
# anchor list (<li><a href="$href">$name</a></li>) for a ChoiceList

include_once("anchorlist.php");

class AnchorTableList extends AnchorList {
  var $numcols    = '';
  var $trclass    = 'itemrow',
      $tdlclass   = 'itemL',
      $tdrclass   = 'itemR',
      $aclass     = 'itemanchor',
      $tableclass = 'selectlist';
  var $tableHeading;

  function AnchorTableList($name, $description='', $numcols=2) {
    $this->AnchorList($name, $description);
    $this->numcols = $numcols;
  }

  function setTableHeadings($headings) {
    $this->tableHeadings = $headings;
  }

  function format($data) {
    //preDump($this);
    $aclass  = (isset($this->aclass) ? " class='$this->aclass'" : '');
    $trclass  = (isset($this->trclass) ? " class='$this->trclass'" : '');
    $tdlclass  = (isset($this->tdlclass) ? " class='$this->tdlclass'" : '');
    $tdrclass  = (isset($this->tdrclass) ? " class='$this->tdrclass'" : '');
    $t  = "<tr $trclass>"
         ."<td $tdlclass>";
    $t .= "<a href='$this->hrefbase".$data[$this->formatid]."'$aclass>"
         .$this->formatter[0]->format($data)
         ."</a>";
    $t .= "</td>\n";
    for ($i=2; $i<=$this->numcols; $i++) {
      $t .= "<td $tdrclass>"
           .$this->formatter[$i-1]->format($data);
      $t .= "</td>";
    }
    $t .= "</tr>\n";
    return $t;
  }

  function display() {
    $tableclass = (isset($this->tableclass) ? " class='$this->tableclass'" : "");
    $t  = "<table title='$this->description' $tableclass>\n";
    if (isset($this->tableHeadings) && is_array($this->tableHeadings)) {
      $t .= '<tr>';
      foreach ($this->tableHeadings as $heading) {
        $t .= "<th>$heading</th>";
      }
      $t .= "</tr>\n";
    }
    if (is_array($this->list->choicelist)) {
      foreach ($this->list->choicelist as $k => $v) {
        $t .= $this->format($v);
      }
    }
    $t .= "</table>\n";
    return $t;
  }

} // class AnchorList


?> 
