<?php
# $Id$
# anchor list (<li><a href="$href">$name</a></li>) for a ChoiceList

include_once("anchorlist.php");

class CheckBoxTableList extends ChoiceList {
  var $numcols    = '',
      $numSpareCols = '';
  var $trclass    = 'itemrow',
      $tdlclass   = 'itemL',
      $tdrclass   = 'itemR',
      $aclass     = 'itemanchor',
      $tableclass = 'selectlist';
  var $tableHeading;
  var $checkboxes;

  function CheckBoxTableList($name, $description='', $numSpareCols=0) {
    $this->ChoiceList($name, $description);
    $this->numSpaceCols = $numSpareCols;
    $this->checkboxes = array();
  }

  function setTableHeadings($headings) {
    $this->tableHeadings = $headings;
  }
  
  function addCheckBox($cb) {
    $this->checkboxes[] = $cb;
    $this->numcols = count($this->checkboxes);
  }

  function format($data, $j) {
    $aclass  = (isset($this->aclass) ? " class='$this->aclass'" : '');
    $trclass  = (isset($this->trclass) ? " class='$this->trclass'" : '');
    $tdlclass  = (isset($this->tdlclass) ? " class='$this->tdlclass'" : '');
    $tdrclass  = (isset($this->tdrclass) ? " class='$this->tdrclass'" : '');
    $t  = '<tr><th>'.$this->longname.'</th>';
    for ($i=0; $i<$this->numcols; $i++) {
      $t .= '<th>'.$this->checkboxes[$i]->longname.'</th>';
    }
    $t .= '</tr>';    
    $t .= "<tr $trclass>"
         ."<td $tdlclass>";
    $t .= "<span $aclass>"
         .$this->formatter[0]->format($data)
         .'</span>';
    $t .= "</td>\n";
    
    for ($i=0; $i<$this->numcols; $i++) {
      $cb = $this->checkboxes[$i];
      $cb->namebase = $this->name.'-'.$i
      $t .= '<td>'.$cb->selectable().'<td>';
    }
    for ($i=2; $i<=$this->numSpareCols; $i++) {
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
        $t .= $this->format($v, $j);
      }
    }
    $t .= "</table>\n";
    return $t;
  }

} // class AnchorList


?> 
