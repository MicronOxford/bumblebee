<?php
# $Id$
# commentfield object: no SQL representation, just a comment in the table.

include_once 'field.php';

class CommentField extends Field {

  function CommentField($name, $longname='', $description='') {
    parent::Field($name, $longname, $description);
    $this->sqlHidden = 1;
    $this->suppressValidation = 1;
    $this->editable = 0;
  }

  function displayInTable($cols) {
    $t = '';
    if (! $this->hidden) {
      $t .= '<tr><td>'.$this->longname.'</td>'."\n"
          .'<td title="'.$this->description.'">';
      $t .= $this->selectable();
      $t .= '</td>'."\n";
      for ($i=0; $i<$cols-2; $i++) {
        $t .= '<td></td>';
      }
      $t .= '</tr>';
    }
    return $t;
  }

  function selectable() {
    return xssqw($this->getValue());
  }
  
  function hidden() {
    return '';
  }

} // class CommentField


?> 
