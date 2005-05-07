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
  var $followHidden,
      $followHiddenField,
      $hidden;
  var $footer;

  function CheckBoxTableList($name, $description='', $numSpareCols=0) {
    $this->ChoiceList($name, $description);
    $this->numSpaceCols = $numSpareCols;
    $this->checkboxes = array();
    $this->footer = array();
    $this->hidden = new TextField('');
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

  function format($data, $j) {
    $aclass  = (isset($this->aclass) ? " class='$this->aclass'" : '');
    $trclass  = (isset($this->trclass) ? " class='$this->trclass'" : '');
    $tdlclass  = (isset($this->tdlclass) ? " class='$this->tdlclass'" : '');
    $tdrclass  = (isset($this->tdrclass) ? " class='$this->tdrclass'" : '');
    $t  = "<tr $trclass>"
         ."<td $tdlclass>";
    $t .= "<span $aclass>"
         .$this->formatter[0]->format($data)
         .'</span>';
    $t .= "</td>\n";
    for ($i=1; $i<=$this->numSpareCols; $i++) {
      $t .= "<td $tdrclass>"
           .$this->formatter[$i]->format($data);
      $t .= "</td>";
    }
    
    $namebase = $this->name.'-'.$j.'-';
    $fh = $this->followHidden;
    $fh->value = $data[$this->followHiddenField];
    $fh->namebase = $namebase;
    $h = $this->hidden;
    $h->value = $j;
    $h->namebase = $namebase;
    $t .= $fh->hidden() . $h->hidden();
    for ($i=0; $i<$this->numcols; $i++) {
      $cb = $this->checkboxes[$i];
      $cb->namebase = $namebase;
      $t .= '<td>'.$cb->selectable().'</td>';
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
    $t  .= '<tr>';
    for ($i=0; $i<=$this->numSpareCols; $i++) {
      $t .= '<th></th>';
    }
    for ($i=0; $i<$this->numcols; $i++) {
      $t .= '<th>'.$this->checkboxes[$i]->longname.'</th>';
    }
    $t .= '</tr>'."\n";    
    if (is_array($this->list->choicelist)) {
      for ($j=0; $j<count($this->list->choicelist); $j++) {
        $t .= $this->format($this->list->choicelist[$j], $j);
      }
    }
    $t .= '<tr>';
    for ($i=0; $i<=$this->numSpareCols; $i++) {
      $t .= '<td></td>';
    }
    for ($i=0; $i<$this->numcols; $i++) {
        $t .= '<td>'.sprintf($this->footer, $i, $i).'</td>';
    }
    $t .= "</table>\n";
    return $t;
  }

} // class CheckBoxTableList


?> 
