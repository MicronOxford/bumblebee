<?php
/**
* Equivalent to DBRow where the database is not involved
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage FormsLibrary
*/

/** database uber-object that we will emulate */
include_once 'dbobject.php';
/** status codes for success/failure of database actions */
include_once 'inc/statuscodes.php';


/**
 * Object representing a NON-database row (and extensible to represent joined rows)
 * Usage:
 *   #set database connection parameters
 *   $obj = new nonDBRow();
 *   #set the fields required and their attributes
 *   $obj->addElement(....);
 *   #check to see if user data changes some values
 *   $obj->update($POST);
 *   $obj->checkValid();
 */
 
class nonDBRow {
  var $name;
  var $longnanme;
  var $description;
  var $newObject = 0;
  var $editable=1;
  var $namebase='';
  var $errorMessage = '';
  var $changed = 0;
  var $isValid = 0;
  var $suppressValidation = 0;
  var $dumpheader = 'nonDBRow object';
  var $fatal_sql = 1;
  var $extrarows;

  var $DEBUG = 0;
  
  function nonDBRow($name, $longname, $description) {
    $this->name = $name;
    $this->longname = $longname;
    $this->description = $description;
  }
  
  /** 
   *  update the value of each of the objects fields according to the user 
   *  input data, and validate the data if appropriate
   */
  function update($data) {
    // We're a new object, but has the user filled the form in, or is the
    // user about to fill the form in?
    // We're a new object, but has the user filled the form in, or is the
    // user about to fill the form in?
    $this->newObject = 1;
    foreach (array_keys($this->fields) as $k) {
      if (isset($data[$this->namebase.$k])) {
        $this->log('I AM NOT NEW '.$k.':changed');
        $this->newObject = 0;
        break;
      } else {
        $this->log('Still new '.$k.':unchanged');
      }
    }
  
    // check each field in turn to allow it to update its data
    foreach (array_keys($this->fields) as $k) {
      $this->log("Check $k ov:".$this->fields[$k]->value
                            .'('.$this->fields[$k]->useNullValues .'/'. $this->newObject.')');
      if (!($this->fields[$k]->useNullValues && $this->newObject)) {
        $this->changed += $this->fields[$k]->update($data);
      }
      $this->log('nv:'.$this->fields[$k]->value.' '.($this->changed ? 'changed' : 'not changed'));
    }
    #$this->checkValid();
    return $this->changed;
  }


  /**
   * check the validity of the data
  **/
  function checkValid() {
    $this->isValid = 1;
    // check each field in turn to allow it to update its data
    // if this object has not been filled in by the user, then 
    // suppress validation
    foreach (array_keys($this->fields) as $k) {
      if (! $this->newObject) {
        $this->log('Checking valid '.$this->fields[$k]->namebase . $k);
        if (! $this->fields[$k]->isValid()) {
          $this->errorMessage .= 'Invalid data: '.$this->fields[$k]->longname
                                    .'('.$this->fields[$k]->name.')'
                                  .' = "'. $this->fields[$k]->getValue() .'"<br />';
          $this->isValid = false;
        }
      }
    }
    if (! $this->isValid) {
      $this->errorMessage .= '<br />Some values entered into the form are not valid '
                  .'and should be highlighted in the form below. '
                  .'Please check your data entry and try again.';
    }
    return $this->isValid;
  }

  /** 
   * Add an element into the fields[] array. The element must conform
   * to the Fields class (or at least its interface!) as that will be
   * assumed elsewhere in this object.
   * Inheritable attributes are also set here.
  **/
  function addElement($el) {
    $this->fields[$el->name] = $el;
    if ($this->fields[$el->name]->editable == -1) {
      $this->fields[$el->name]->editable = $this->editable;
    }
    if (! isset($this->fields[$el->name]->namebase)) {
      $this->fields[$el->name]->namebase = $this->namebase;
      #echo "Altered field $el->name to $this->namebase\n";
    }
    if ($this->fields[$el->name]->suppressValidation == -1) {
      $this->fields[$el->name]->suppressValidation = $this->suppressValidation;
      #echo "Altered field $el->name to $this->namebase\n";
    }
    #echo $el->name;
    #echo "foo:".$this->fields[$el->name]->name.":bar";
  }

  /** 
   * Add multiple elements into the fields[] array.
  **/
  function addElements($els) {
    foreach ($els as $e) {
      #echo $e->text_dump();
      $this->addElement($e);
    }
  }

  /** 
   * Quick and dirty dump of fields (values only, not a full print_r
  **/
  function text_dump() {
    $t  = "<pre>$this->dumpheader $this->table (id=$this->id)\n{\n";
    foreach ($this->fields as $v) {
      $t .= "\t".$v->text_dump();
    }
    $t .= "}\n</pre>";
    return $t;
  }

  function display() {
    return $this->text_dump();
  }

  function displayInTable($numCols=2) {
    $t  = '<h3>'.$this->longname.'</h3>';
    $t .= '<table class="tabularobject" title="'.$this->description.'">';
    foreach ($this->fields as $v) {
      $t .= $v->displayInTable($numCols);
    }
    if (is_array($this->extrarows)) {
      foreach ($this->extrarows as $v) {
        $t .= '<tr>';
        foreach ($v as $c) {
          $t .= '<td>'.$c.'</td>';
        }
        $t .= '</tr>';
      }
    }
    $t .= '</table>';
    return $t;
  }

  function log($logstring, $prio=10) {
    if ($prio <= $this->DEBUG) {
      echo $logstring."<br />\n";
    }
  }

} // class dbrow

?> 
