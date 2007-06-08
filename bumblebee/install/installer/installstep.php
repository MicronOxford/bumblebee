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
  var $hidden = false;

  function InstallStep($name, $action, $hidden=false) {
    $this->name = $name;
    $this->action = $action;
    $this->hidden = $hidden;
  }

  function setNext($step) {
    $this->next = $step;
  }

  function setPrev($step) {
    $this->prev = $step;
  }

  function getNextButton($name=NULL, $waitNext=false) {
    if ($this->next == NULL) return '';
    return $this->next->makeNextButton($name, $waitNext);
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
    return $this->makeButton('&laquo; '.$name);
  }

  function makeNextButton($name, $waitNext=false) {
    if ($name == NULL) $name = $this->name;
    return $this->makeButton($name.' &raquo;', $waitNext);
  }

  function makeButton($name, $disabled=false) {
    $dis = ($disabled ? "disabled='1'" : '');
    return "<input type='submit' name='{$this->action}' id='{$this->action}' value='{$name}' $dis />";
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
    $stepnum=1;
    foreach ($this->steps as $num => $s) {
      if ($s->hidden) continue;

      $t .= "<input type='submit' name='{$s->action}' value='$stepnum. {$s->name}'"
        .($this->index<$num ? " disabled='1'" : "")." />";
      $stepnum++;
    }
    return $t;
  }

  function getPrevNextButtons($prev=NULL, $next=NULL, $waitNext=false) {
    if ($prev   === NULL) $prev = 'Previous';
    if ($next   === NULL) $next = 'Continue';
    return $this->steps[$this->index]->getPrevButton($prev).' '
          .$this->steps[$this->index]->getNextButton($next, $waitNext);
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

  function getNextActionName() {
    return $this->steps[$this->index+1]->action;
  }

  function getThisActionName() {
    return $this->steps[$this->index]->action;
  }

}

?>
