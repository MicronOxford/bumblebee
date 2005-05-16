<?php
# $Id$
# anchor list (<li><a href="$href">$name</a></li>) for a ChoiceList

include_once 'anchorlist.php';

class CheckBoxTableList extends ChoiceList {
  var $numcols    = '';
  var $numExtraInfoCols = '';
  var $trclass    = 'itemrow';
  var $tdlclass   = 'itemL';
  var $tdrclass   = 'itemR';
  var $aclass     = 'itemanchor';
  var $tableclass = 'selectlist';
  var $tableHeading;
  var $checkboxes;
  var $followHidden;
  var $followHiddenField;
  var $hidden;
  var $footer;

  function CheckBoxTableList($name, $description='', $numExtraInfoCols=-1) {
    $this->ChoiceList($name, $description);
    $this->numExtraInfoCols = $numExtraInfoCols;
    $this->checkboxes = array();
    $this->footer = array();
    $this->hidden = new TextField('row');
    $this->hidden->hidden = 1;
  }

  function setTableHeadings($headings) {
    $this->tableHeadings = $headings;
  }
  
  function addCheckBox($cb) {
    $this->checkboxes[] = $cb;
    $this->numcols = count($this->checkboxes);
  }
  
  function addFollowHidden($h, $follow='id') {
    $h->hidden = 1;
    $this->followHidden = $h;
    $this->followHiddenField = $follow;
  }
  
  function addFooter($f) {
    $this->footer = $f;
  }

  function format($data, $j, $numcols) {
    $aclass  = (isset($this->aclass) ? " class='$this->aclass'" : '');
    $trclass  = (isset($this->trclass) ? " class='$this->trclass'" : '');
    $tdlclass  = (isset($this->tdlclass) ? " class='$this->tdlclass'" : '');
    $tdrclass  = (isset($this->tdrclass) ? " class='$this->tdrclass'" : '');

    $namebase = $this->name.'-'.$j.'-';
    $fh = $this->followHidden;
    $fh->value = $data[$this->followHiddenField];
    $fh->namebase = $namebase;
    $h = $this->hidden;
    $h->value = $j;
    $h->namebase = $namebase;
    
    $t  = "<tr $trclass>"
         ."<td $tdlclass>";
    $t .= "<span $aclass>"
         .$this->formatter[0]->format($data)
         .'</span>';
    $t .= $fh->hidden() . $h->hidden();
    $t .= "</td>\n";
    for ($i=1; $i<=$this->numExtraInfoCols; $i++) {
      $t .= "<td $tdrclass>"
           .$this->formatter[$i]->format($data);
      $t .= "</td>";
    }
    
    for ($i=0; $i<$this->numcols; $i++) {
      $cb = $this->checkboxes[$i];
      $cb->namebase = $namebase;
      $t .= '<td>'.$cb->selectable().'</td>';
    }
    for ($i=0; $i<=$numcols; $i++) {
      $t .= '<th></th>';
    }
    $t .= "</tr>\n";
    return $t;
  }

  function display() {
    $tableclass = (isset($this->tableclass) ? " class='$this->tableclass'" : "");
    $t  = "<table title='$this->description' $tableclass>\n";
    $t .= $this->displayInTable($this->numcols);
    $t .= "</table>\n";
    return $t;
  }

  
  function displayInTable($numCols) {
    $totalCols = 1 + $this->numExtraInfoCols + $this->numcols;
    $t='';
    if ($this->numExtraInfoCols = -1) {
      $this->numExtraInfoCols = count($this->formatter)-1;
    }
    if (isset($this->tableHeadings) && is_array($this->tableHeadings)) {
      $t .= '<tr>';
      foreach ($this->tableHeadings as $heading) {
        $t .= "<th>$heading</th>";
      }
      $t .= "</tr>\n";
    }
    $t  .= '<tr>';
    for ($i=0; $i<=$this->numExtraInfoCols; $i++) {
      $t .= '<th></th>';
    }
    for ($i=0; $i<$this->numcols; $i++) {
      $t .= '<th>'.$this->checkboxes[$i]->longname.'</th>';
    }
    for ($i=$totalCols; $i<=$numCols; $i++) {
      $t .= '<th></th>';
    }
    $t .= '</tr>'."\n";    
    if (is_array($this->list->choicelist)) {
      for ($j=0; $j<count($this->list->choicelist); $j++) {
        $t .= $this->format($this->list->choicelist[$j], $j, $numCols - $totalCols);
      }
    }
    $t .= '<tr>';
    for ($i=0; $i<=$this->numExtraInfoCols; $i++) {
      $t .= '<td></td>';
    }
    for ($i=0; $i<$this->numcols; $i++) {
        $t .= '<td>'.sprintf($this->footer, $i, $i).'</td>';
    }
    for ($i=$totalCols; $i<=$numCols; $i++) {
      $t .= '<td></td>';
    }
    return $t;
  }  
  
} // class CheckBoxTableList


?> 
