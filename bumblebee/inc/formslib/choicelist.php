<?php
# $Id$
# choice list (to be encapsulated in a select, list of a hrefs etc

class ChoiceList {
  var $name,
      $description;
  var $choices;

  function ChoiceList($name, $description="") {
    $this->name = $name;
    $this->description = $description;
    $this->choices = array();
  }

  function setChoices($newlist) {
    #newlist should be a "SimpleList" object, or compatible
    $this->choices = $newlist;
  }

  function text_dump() {
    $t  = "<pre>";
    foreach ($this->choices as $k => $v) {
      $t .= "$k =&gt; $v ";
      $t .= "\n";
    }
    $t .= "</pre>";
    return $t;
  }

  function display() {
    return $this->text_dump();
  }

} // class ChoiceList


?> 
