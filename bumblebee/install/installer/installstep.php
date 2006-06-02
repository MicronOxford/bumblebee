<?php
/**
* An Installation Step class
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Installer
*/

class InstallStep {

  var $prev = NULL;
  var $next = NULL;
  var $name;
  var $action;
  
  function InstallStep($name, $action) {
    $this->name = $name;
    $this->action = $action;
  }
  
  function setNext($step) {
    $this->next = $step;
  }

  function setPrev($step) {
    $this->prev = $step;
  }
  
  function getNextButton($name=NULL) {
    if ($this->next == NULL) return '';
    return $this->next->makeNextButton($name);
  }
  
  function getPrevButton($name=NULL) {
    if ($this->prev == NULL) return '';
    return $this->prev->makePrevButton($name);
  }
  
  function getThisButton($name=NULL) {
    if ($name == NULL) $name = $this->name;
    return $this->makeButton($name);
  }
  
  function makePrevButton($name) {
    if ($name == NULL) $name = $this->name;
    return $this->makeButton('&laquo '.$name);
  }

  function makeNextButton($name) {
    if ($name == NULL) $name = $this->name;
    return $this->makeButton($name.' &raquo');
  }
  
  function makeButton($name) {
    return "<input type='submit' name='{$this->action}' value='{$name}' />";
  }
}


class InstallStepCollection {

  var $steps = array();
  var $index = 1;

  function addStep($step) {
    $num = count($this->steps);
    if ($num != 0) {
      $step->setPrev($this->steps[$num]);
      $this->steps[$num]->setNext($step);
    }
    $this->steps[$num+1] = $step;
  }
  
  function numSteps() {
    return count($this->steps);
  }
  
  function getIndex() {
    return $this->index;
  }
  
  function getStepButtons() {
    $t = '';
    foreach ($this->steps as $num => $s) {
      $t .= "<input type='submit' name='{$s->action}' value='$num. {$s->name}'"
        .($this->index<$num ? " disabled='1'" : "")." />";
    }
    return $t;
  }
  
  function getPrevNextButtons($prev=NULL, $next=NULL) {
    if ($prev   === NULL) $prev = 'Previous';
    if ($next   === NULL) $next = 'Continue';
    return $this->steps[$this->index]->getPrevButton($prev).' '
          .$this->steps[$this->index]->getNextButton($next);
  }
  
  function getPrevReloadNextButtons($prev=NULL, $reload=NULL, $next=NULL) {
    if ($prev   === NULL) $prev = 'Previous';
    if ($reload === NULL) $reload = 'Reload';
    if ($next   === NULL) $next = 'Continue';
    return $this->steps[$this->index]->getPrevButton($prev).' '
          .$this->steps[$this->index]->getThisButton($reload).' '
          .$this->steps[$this->index]->getNextButton($next);
  }
  
  function getPrevSkipToButtons($skip=1, $prev=NULL, $next=NULL) {
    if ($prev   === NULL) $prev = 'Previous';
    if ($next   === NULL) $next = 'Continue';
    return $this->steps[$this->index]->getPrevButton($prev).' '
          .$this->steps[$this->index+$skip]->getNextButton($next);
  }
  
  function setCurrent($num) {
    $this->index = $num;
  }
  
  function increment() {
    $this->index++;
  }
  
}

?>
