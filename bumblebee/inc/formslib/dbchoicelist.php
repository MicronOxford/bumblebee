<?php
# $Id$
# a choice list based on an SQL statement, live db object to add extra entries

include_once("dbobject.php");

/**
  * Primitive class on which selection lists can be built from the
  * results of an SQL query. This may be used to determine the choices
  * that a user is permitted to select (e.g. dropdown list or radio buttons)
  * or also to permit additional entries to be created.
  *
  * Used in a 1:many relationship (i.e. a field in a table that is the
  * primary key in another table)
  *
  * Note that this class has no real way of displaying itself properly,
  * so it would usually be inherited and the descendent class used.
  *
  * Typical usage:
  *   $f = new RadioList("myfield", "Field name");
  *   $f->connectDB("mytable", array("id", "name"));
  *   $f->setFormat("id", "%s", array("name"));
  *   $newentryfield = new TextField("name","");
  *   $newentryfield->namebase = "newentry-";
  *   $newentryfield->suppressValidation = 0;
  *   $f->list->append(array("-1","Create new: "), $newentryfield);
 **/
class DBChoiceList extends DBO {
  var $restriction,
      $order,
      $limit;
  var $editable = 0,
      $extendable = 0,
      $changed = 0;
  var $choicelist;
  var $length;
  var $appendedfields,
      $prependedfields;

  /** 
    * Construct a new DBList object based on:
    *     * database table ($table)
    *     * calling for the fields in the array (or scalar) $fields
    *     * with an SQL restriction (WHERE clause) $restriction
    *     * ordering the listing by $order
    *     * using the field $idfield as the control variable in the list
    *       (i.e. the value='' in a radio list etc)
    *     * with an SQL LIMIT statement of $limit
   **/
  function DBChoiceList($table, $fields='', $restriction='',
                  $order='', $idfield='id', $limit='') {
    $this->DBO($table, "", $idfield);
    $this->fields = (is_array($fields) ? $fields : array($fields));
    $this->restriction = $restriction;
    $this->order = $order;
    $this->limit = $limit;
    $this->choicelist = array();
    $this->appendedfields = array();
    $this->prependedfields = array();
    $this->fill();
  }

  /**
    * Fill the object from the database using the already initialised
    * members (->table etc).
   **/
  function fill() {
    $f = implode(", ", $this->fields);
    $q = "SELECT $this->idfield, $f "
        ."FROM $this->table "
        #."WHERE $this->restriction "
        #."ORDER BY $this->order "
        .($this->restriction != '' ? "WHERE $this->restriction " : '')
        .($this->order != '' ? "ORDER BY $this->order " : '')
        .($this->limit != '' ? "LIMIT $this->limit " : '');
    $sql = db_get($q, $this->fatal_sql);
    if (! $sql) {
      //then the SQL query was unsuccessful and we should bail out
      return 0;
    } else {
      //FIXME mysql specific array
      while ($g = mysql_fetch_array($sql)) {
        $this->choicelist[] = $g; #['key']] = $g['value'];
      }
      $this->length = count($this->choicelist);
      //if this fill() has been called after extra fields have been prepended
      //or appended to the field list, then we need to re-add them as they
      //will be lost by this process
      $this->_reAddExtraFields();
    }
    return 1;
  }
  
  function _mkaddedarray($values, $field='') {
    $a = array();
    for ($i=0; $i < count($values); $i++) {
      $a[$this->fields[$i]] = $values[$i];
    }
    $a['_field'] = $field;
    return $a;
  }

  /**
    * append or prepend a special field (such as "Create new:") to the 
    * choicelist. Keep a copy of the field so it can be added again later if
    * necessary, and then use a private function to actually do the adding
   **/
  function append($values, $field='') {
    $fa = $this->_mkaddedarray($values, $field);
    //keep a copy of the field so it can be added again after a fill()
    $this->appendedfields[] = $fa;
    $this->_append($fa);
  }

  function prepend($values, $field='') {
    $fa = $this->_mkaddedarray($values, $field);
    //keep a copy of the field so it can be added again after a fill()
    $this->prependedfields[] = $fa;
    $this->_prepend($fa);
  }

  /**
    * private functions _append and _prepend that will actually add the field
    * to the field list after it has been properly constructed and saved for
    * future reference
   **/
  function _append($fa) {
    array_push($this->choicelist, $fa);
  }

  function _prepend($fa) {
    array_unshift($this->choicelist, $fa);
  }

  /**
    * add back in the extra fields that were appended/prepended to the
    * choicelist. Use this if they fields are lost due to a fill()
   **/
  function _reAddExtraFields() {
    foreach ($this->appendedfields as $k => $v) {
      $this->_append($v);
    }
    foreach ($this->prependedfields as $k => $v) {
      $this->_prepend($v);
    }
  }

  function display() {
    return $this->text_dump();
  }

  function text_dump() {
    return "<pre>SimpleList:\n".print_r($this->choicelist, true)."</pre>";
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
    echo "DBChoiceList update: ";
    echo "(changed=$this->changed)";
    echo "(id=$this->id)";
    echo "(newval=$newval)";
    if (isset($newval)) {
      //check to see if the newval is legal (does it exist on our choice list?)
      $isExisting = 0;
      foreach ($this->choicelist as $k => $v) {
        echo "($isExisting:".$v['id'].":$newval)";
        if ($v['id'] == $newval && $v['id'] >= 0) {
          $isExisting = 1;
          break;
        }
      }
      if ($isExisting) {
        // it is a legal, existing value, so we adopt it 
        echo "isExisting";
        $this->changed += ($newval != $this->id);
        $this->id = $newval;
        $this->isValid = 1;
        //isValid handling done by the Field that inherits it
      } elseif ($this->extendable) {
        // then it is a new value and we should accept it
        echo "isExtending";
        $this->changed += 1;
        //$this->id = $newval;
        //If we are extending the list, then we should have a negative
        //number as the current value to trip the creation of the new
        //entry later on in sync()
        //FIXME is this right? 
        $this->id = -1;
        foreach ($this->choicelist as $k => $v) {
          preDump($v);
          if (isset($v['_field']) && $v['_field'] != "") {
            $this->choicelist[$k]['_field']->update($data);
            $this->isValid += $this->choicelist[$k]['_field']->isValid();
          }
        }
      } else {
        echo "isInvalid";
        // else, it's a new value and we should not accept it
        $this->isValid = 0;
      }
    }
    echo " DBchoiceList::changed=$this->changed<br />";
    return $this->isValid;
  }

  function set($value) {
    echo "DBchoiceList::set = $value<br/>";
    $this->id = $value;
  }

  /**
    * synchronise with the database -- this also creates the true value for
    * this field if it is undefined
    * returns false on success
   **/
  function sync() {
    #preDump($this);
    if ($this->changed && $this->isValid) {
      echo "Syncing...<br />";
      if ($this->id == -1) {
        //it's a new record, insert it
        $vals = $this->_sqlvals();
        $q = "INSERT $this->table SET $vals";
        $sql_result = db_quiet($q, $this->fatal_sql);
        $this->id = db_new_id();
        $this->fill();
        return $sql_result;
      }
    }
  }

  function _sqlvals() {
    $vals = array();
    if ($this->changed) {
      #echo "This has changed";
      foreach ($this->choicelist as $k => $v) {
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

  function selectedvalue() {
    $val = array();
    foreach ($this->choicelist as $k => $v) {
      echo "H:$this->idfield, $k, $v, $this->id";
      if ($v[$this->idfield] == $this->id) {
        foreach ($this->fields as $f) {
          echo "G=$f";
          $val[] = $v[$f];
        }
      }
    }
    return implode(' ', $val);
  }

  function setDefault($val) {
    echo "DBChoiceList::setDefault: $val";
    if (isset($this->id) || $this->id < 0) {
      $this->id = $val;
    }
    echo $this->id;
  }

} // class DBChoiceList

?> 
