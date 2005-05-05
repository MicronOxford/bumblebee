<?php
# $Id$
# database objects (self-initialising and self-updating object)

include_once 'typeinfo.php';
include_once 'validtester.php';

/**
 * Field object that corresponds to one field in a SQL table row.
 * A number of fields would normally be held together in a DBRow,
 * with the DBRow object controlling the updating to the SQL database.
 *
 * Typical usage is through inheritance, see example for DBRow
 *     $f = new TextField("name", "Name");
 */
class Field {
  var $name;
  var $longname;
  var $description;
  var $required = 0;
  var $value;
  var $ovalue;
  var $defaultValue = '';
  var $duplicateName;
  var $editable = -1;
  var $changed = 0;
  var $hidden;
  var $isValid = 1;
  var $suppressValidation = -1;
  var $useNullValues = 0;
  var $attr;
  var $errorclass = 'error';
  var $namebase;
  var $isValidTest = 'isset';
  var $sqlHidden = 0;
  var $DEBUG = 0;

  /**
   *  Create a new generic field object, designed to be superclasses
   *
   * @param string $name   the name of the field (db name, and html field name
   * @param string $longname  long name to be used in the label of the field in display
   * @param string $description  used in the html title or longdesc for the field
   */
  function Field($name, $longname='', $description='') {
    $this->name = $name;
    $this->longname = $longname;
    $this->description = $description;
  }

  /**
   * update the value of the object with the user-supplied data in $data
   * 
   * @param array $data is name => value pairs as below
   * @return boolean  did the value of this object change?
   *
   * $data is most probably from POST data etc.
   *
   * The validity of the data is *not* checked at this stage, the object
   * only takes the user-supplied value.
   *
   * $data is an array with the data relevant to this field being
   * in the key $this->namebase.$this->name. 
   * For example:
   *     $this->namebase = 'person-';
   *     $this->name     = 'phonenumber';
   * $data['person-phonenumber'] is used for the value.
   */
  function update($data) {
    if (isset($data["$this->namebase$this->name"]) || $this->useNullValues) {
      $newval = issetSet($data, "$this->namebase$this->name");
      $this->log("$this->name, $this->value, $newval ($this->useNullValues)");
      if ($this->editable) {
        // we ignore new values if the field is not editable
        if ($this->changed = ($this->getValue() != $newval)) {
          $this->ovalue = $this->getValue();
          $this->value = $newval;
        }
      } else {
        //ensure that the value is copied in from the default value if
        //it is unset.
        $this->value = $this->getValue();
      }
    } else {
      if ($this->getValue() != $this->value) {
        $this->changed = 0;
        $this->value = $this->getValue();
      }
    }
    $this->log($this->name . ($this->changed ? ' CHANGED' : ' SAME'));
    return $this->changed;
  }

  /**
   * Check the validity of the current data value. 
   * 
   * @return boolean  is the data value valid
   *
   * This also checks the validity of the data even if the data is not newly-entered.
   * Returns true if the specified validity tests are passed:
   *     is the field required to be filled in && is it filled in?
   *     is there a validity test && is the data valid?
   */
  function isValid() {
    /*    
    echo "<br />";
    echo $this->name .":". $this->isValidTest.":";
    echo is_callable($this->isValidTest);
    echo "$this->suppressValidation";
    echo "req=$this->required";*/
    
    $this->isValid = 1;
    if ($this->required) {
      #$this->isValid = (isset($this->value) && $this->value != "");
      $this->isValid = ($this->getValue() != '');
      $this->log($this->name . ' Required: '.($this->isValid ? ' VALID' : ' INVALID'));
    }
    if ($this->isValid && $this->suppressValidation == 0) {
      $this->isValid = ValidTester($this->isValidTest, $this->getValue());
    }
    $this->log($this->name . ($this->isValid ? ' VALID' : ' INVALID'));
    return $this->isValid;
  }

  /**
   * set the value of this field 
   * 
   * @param string   the new value for this field
   *
   * *without* validation or checking to see whether the field has changed.
   */
  function set($value) {
    //echo "Updating field $this->name. New value=$value\n";
    $this->value = $value;
  }

  /**
   * return a SQL-injection-cleansed string that can be used in an SQL
   * UPDATE or INSERT statement. i.e. "name='Stuart'".
   *
   * @return string  in SQL assignable form
   */
  function sqlSetStr() {
    if (! $this->sqlHidden) {
      return $this->name .'='. qw($this->getValue());
    } else {
      return '';
    }
  }

  /**
   * set display attributes for the field.
   *
   * the attribute fielde are parsed differently for each different field subclass
   *
   * @param array $attrs attribute_name => value
   * @access public
   * 
   */
  function setattr($attrs) {
    foreach ($attrs as $k => $v) {
      $this->attr[$k] = $v;
    }
  }

  /** 
   * quick and dirty display of the field status
   *
   * @return string simple text representation of the class's value and attributes
   */
  function text_dump() {
    $t  = "$this->name =&gt; ".$this->getValue();
    $t .= ($this->editable ? "(editable)" : "(read-only)");
    $t .= ($this->isValid ? "" : "(invalid)");
    $t .= "\n";
    return $t;
  }

  function display() {
    return $this->text_dump();
  }

  function hidden() {
    return "<input type='hidden' name='$this->namebase$this->name' "
           ."value='".xssqw($this->getValue())."' />";
  }

  function displayInTable() {
  }

  function getValue() {
//     echo "FIELD $this->name: ".$this->value.":".$this->defaultValue."<br />";
    return (isset($this->value) ? $this->value : $this->defaultValue);
  }

  function log($logstring, $prio=10) {
    if ($prio <= $this->DEBUG) {
      echo $logstring."<br />\n";
    }
  }
  
  function setEditable($editable=1) {
    $this->editable = $editable;
  }
  
  function setNamebase($namebase='') {
    $this->namebase = $namebase;
  }
  
} // class Field

?> 
