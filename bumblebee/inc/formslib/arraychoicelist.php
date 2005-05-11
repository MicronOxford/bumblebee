<?php
# $Id$
# a choice list based on an SQL statement, live db object to add extra entries

/**
 * Provide a non-database filled (i.e. array-filled) version of DBChoiceList
 *
 * See DBChoiceList for more details of interface.
 * 
 */
class ArrayChoiceList {
  var $id;
  var $editable = 0;
  var $extendable = 0;
  var $changed = 0;
  var $choicelist;
  var $length;
  var $DEBUG = 0;
  var $listKey;
  var $displayKey;

  function ArrayChoiceList($array, $iv, $dv) {
    $this->choicelist = array();
    $this->listKey = $iv;
    $this->displayKey = $dv;
    $item=0;
    foreach ($array as $internalVal => $displayVal) {
      $this->choicelist[$item] = array();
      $this->choicelist[$item][$this->listKey] = $internalVal;
      $this->choicelist[$item][$this->displayKey] = $displayVal;
      $this->choicelist[$item]['_field'] = 0;
      $item++;
    }
  }

  
  function _mkaddedarray($values, $field='') {
    $a = array();
    $a[$this->listKey] = $values[0];
    $a[$this->displayKey] = $values[1];
    $a['_field'] = $field;
    return $a;
  }

  /**
   * append or prepend a field (such as "Create new:") 
   *
   * This method is much simpler than the DB equivalent as we won't need to remember them.
   *
   */
  function append($values, $field='') {
    $fa = $this->_mkaddedarray($values, $field);
    array_push($this->choicelist, $fa);
  }

  function prepend($values, $field='') {
    $fa = $this->_mkaddedarray($values, $field);
    array_unshift($this->choicelist, $fa);
  }

  function display() {
    return $this->text_dump();
  }

  function text_dump() {
    return "<pre>SimpleList:\n".print_r($this->choicelist, true).'</pre>';
  }

  /** 
   * update the value of the list based on user data:
   *   - if it is within the range of current values, then take the value
   *   - if the field contains a new value (and is allowed to) then keep
   *     an illegal value, mark as being changed, and wait until later for
   *     the field to be updated
   *   - if the field contains a new value (and is not allowed to) or an 
   *     out-of-range value, then flag as being invalid
   * 
   * The (possibly) new value is in $newval, while ancillary user data is in
   * $data, which is passed on to any appended or prepended fields.
   **/
  function update($newval, $data) {
    if ($this->DEBUG) {
      echo 'ArrayChoiceList update: ';
      echo "(changed=$this->changed)";
      echo "(id=$this->id)";
      echo "(newval=$newval)";
    }
    if (isset($newval)) {
      //check to see if the newval is legal (does it exist on our choice list?)
      $isExisting = 0;
      foreach ($this->choicelist as $v) {
        if ($this->DEBUG) echo "($isExisting:".$v['id'].":$newval)";
        if ($v['id'] == $newval && $v['id'] >= 0) {
          $isExisting = 1;
          break;
        }
      }
      if ($isExisting) {
        // it is a legal, existing value, so we adopt it 
        if ($this->DEBUG) echo 'isExisting';
        $this->changed += ($newval != $this->id);
        $this->id = $newval;
        $this->isValid = 1;
        //isValid handling done by the Field that inherits it
      } elseif ($this->extendable) {
        // then it is a new value and we should accept it
        if ($this->DEBUG) echo 'isExtending';
        $this->changed += 1;
        //$this->id = $newval;
        //If we are extending the list, then we should have a negative
        //number as the current value to trip the creation of the new
        //entry later on in sync()
        $this->id = -1;
        foreach ($this->choicelist as $k => $v) {
          //preDump($v);
          if (isset($v['_field']) && $v['_field'] != "") {
            $this->choicelist[$k]['_field']->update($data);
            $this->isValid += $this->choicelist[$k]['_field']->isValid();
          }
        }
      } else {
        if ($this->DEBUG) echo 'isInvalid';
        // else, it's a new value and we should not accept it
        $this->isValid = 0;
      }
    }
    #echo " DBchoiceList::changed=$this->changed<br />";
    return $this->isValid;
  }

  function set($value) {
    #echo "DBchoiceList::set = $value<br/>";
    $this->id = $value;
  }

  /**
   * synchronise: but we have nothing to do
   */
  function sync() {
    return false;
  }

  function _sqlvals() {
    $vals = array();
    if ($this->changed) {
      #echo "This has changed";
      foreach ($this->choicelist as $v) {
        if (isset($v['_field'])) {
          $vals[] = $v['_field']->name ."=". qw($v['_field']->value);
        }
      }
    }
    #echo "<pre>"; print_r($this->choicelist); echo "</pre>";
    #echo "<pre>"; print_r($this->fields); echo "</pre>";
    #echo "<pre>"; print_r($vals); echo "</pre>";
    return join(",",$vals);
  }

  function selectedValue() {
    $val = array();
    foreach ($this->choicelist as $v) {
      //echo "H:$this->idfield, $k, $v, $this->id";
      if ($v[$this->listKey] == $this->id) {
        foreach ($this->fields as $f) {
          //echo "G=$f";
          $val[] = $v[$f];
        }
      }
    }
    return implode(' ', $val);
  }

  function setDefault($val) {
    //echo "DBChoiceList::setDefault: $val";
    if (isset($this->id) || $this->id < 0) {
      $this->id = $val;
    }
    //echo $this->id;
  }

} // class ArrayChoiceList

?> 
