<?php
# $Id$
# database objects (self-initialising and self-updating object)

include_once("typeinfo.php");
include_once("validtester.php");

/**
  * Field object that corresponds to one field in a SQL table row.
  * A number of fields would normally be held together in a DBRow,
  * with the DBRow object controlling the updating to the SQL database.
  *
  * Typical usage is through inheritance, see example for DBRow
  *     $f = new TextField("name", "Name");
 **/
class Field {
  var $name,
      $longname,
      $description,
      $required = 0,
      $value,
      $ovalue,
      $defaultValue = '';
  var $duplicateName;
  var $editable = -1, 
      $changed = 0,
      $hidden,
      $isValid = 1,
      $suppressValidation = -1,
      $useNullValues = 0;
  var $attr,
      $errorclass = "error";
  var $namebase;
  var $isValidTest = "isset";

  function Field($name, $longname="", $description="") {
    $this->name = $name;
    $this->longname = $longname;
    $this->description = $description;
  }

  /**
    * update the value of the object with the user-supplied data in $data
    * most probably from POST data etc.
    * The validity of the data is *not* checked at this stage, the object
    * only takes the user-supplied value.
    *
    * $data is an array with the data relevant to this field being
    * in the key $this->namebase.$this->name. 
    * For example:
    *     $this->namebase = 'person-';
    *     $this->name     = 'phonenumber';
    * $data['person-phonenumber'] is used for the value.
   **/
  function update($data) {
    if (isset($data["$this->namebase$this->name"]) || $this->useNullValues) {
      $newval = issetSet($data, "$this->namebase$this->name");
      echo "$this->name, $this->value, $newval<br />\n";
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
    return $this->changed;
  }

  /**
   * Check the validity of the current data value. This also checks the 
   * validity of the data even if the data is not newly-entered.
   * Returns true if the specified validity tests are passed:
   *     is the field required to be filled in && is it filled in?
   *     is there a validity test && is the data valid?
  **/
  function isValid() {
    /*
    echo "<br />";
    echo $this->name .":". $this->isValidTest.":";
    echo is_callable($this->isValidTest);
    echo "$this->suppressValidation";
    echo "req=$this->required";
    */
    $this->isValid = 1;
    if ($this->required) {
      $this->isValid = (isset($this->value) && $this->value != "");
    }
    if ($this->isValid && $this->suppressValidation == 0) {
      $this->isValid = ValidTester($this->isValidTest, $this->value);
    }
    echo ($this->isValid ? "VALID" : "INVALID");
    return $this->isValid;
  }

  /**
   * set the value of this field *without* validation or checking
   * to see whether the field has changed.
  **/
  function set($value) {
    //echo "Updating field $this->name. New value=$value\n";
    $this->value = $value;
  }

  /**
   * return a SQL-injection-cleansed string that can be used in an SQL
   * UPDATE or INSERT statement. i.e. "name='Stuart'".
  **/
  function sqlSetStr() {
    return $this->name .'='. qw($this->getValue());
  }

  /**
   * set display attributes for the field.
  **/
  function setattr($attrs) {
    foreach ($attrs as $k => $v) {
      $this->attr[$k] = $v;
    }
  }

  /** 
   * quick and dirty display of the field status
  **/
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

  function displayInTable() {
  }

  function getValue() {
    #echo "FIELD: ".$this->value.":".$this->defaultValue."<br />";
    return (isset($this->value) ? $this->value : $this->defaultValue);
  }

} // class Field

?> 
