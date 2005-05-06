<?php
# $Id$
# textfield object

include_once 'field.php';
include_once 'typeinfo.php';

class TextArea extends TextField {

  function TextArea($name, $longname='', $description='') {
    parent::TextField($name, $longname, $description);
  }

  function selectable() {
    $t  = '<textarea name="'.$this->namebase.$this->name.'" ';
    $t .= 'title="'.$this->description.'" ';
    $t .= (isset($this->attr['rows']) ? 'rows="'.$this->attr['rows'].'" ' : '');
    $t .= (isset($this->attr['cols']) ? 'cols="'.$this->attr['cols'].'" ' : '');
    $t .= '>';
    $t .= xssqw($this->getValue());
    $t .= '</textarea>';
    return $t;
  }
  
} // class TextArea


?> 
