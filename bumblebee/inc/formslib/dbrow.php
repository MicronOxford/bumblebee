<?php
# $Id$
# database objects (self-initialising and self-updating object)

include_once('dbobject.php');

/**
 * Object representing a database row (and extensible to represent joined rows)
 * Usage:
 *   #set database connection parameters
 *   $obj = new DBRow("users", 14, "userid");
 *   #set the fields required and their attributes
 *   $obj->addElement(....);
 *   #connect to the database
 *   $obj->fill();
 *   #check to see if user data changes some values
 *   $obj->update($POST);
 *   #synchronise with database
 *   $obj->sync();
**/
class DBRow extends DBO {
  var $fatal_sql = 1;
  var $namebase;
  var $newObject = 0;
  var $recStart = '',
      $recNum   = '';

  function DBRow($table, $id, $idfield='id') {
    $this->DBO($table, $id, $idfield);
    #$this->fields = array();
  }
  
  /** 
   *  update the value of each of the objects fields according to the user 
   *  input data, and validate the data if appropriate
  **/
  function update($data) {
    echo "<br/><br/>DBRow:$this->namebase.Looking for updates:<br />";
    // First, check to see if this record is new
    if ($this->id == -1) {
      // We're a new object, but has the user filled the form in, or is the
      // user about to fill the form in?
      $this->newObject = 1;
      foreach ($this->fields as $k => $v) {
        if ($k != $this->idfield && isset($data[$this->namebase.$k])) {
          echo "$k:changed<br />";
          $this->newObject = 0;
          break;
        }
      }
    }
    // check each field in turn to allow it to update its data
    foreach ($this->fields as $k => $v) {
      echo "Check $k ";
      echo "ov:".$this->fields[$k]->value;
      $this->changed += $this->fields[$k]->update($data);
      echo "nv:".$this->fields[$k]->value." ";
      echo "<br />";
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
    foreach ($this->fields as $k => $v) {
      if (! $this->newObject) {
        echo "Checking valid ".$this->fields[$k]->namebase."$k ";
        $this->isValid = $this->fields[$k]->isValid() && $this->isValid;
      }
      echo "<br />";
    }
    return $this->isValid;
  }

  /**
   * synchronise this object's fields with the database.
   * If the object is new, then INSERT the data, if the object is pre-existing
   * then UPDATE the data. Fancier fields that are only pretending to
   * do be simple fields (such as JOINed data) should perform their updates
   * during the _sqlvals() call 
   *
   * Note, this function returns false on success
  **/
  function sync() {
    // If the input isn't valid then bail out straight away
    if (! ($this->changed && $this->isValid) ) {
      echo "not syncing: changed=$this->changed valid=$this->isValid<br />";
      return -1;
    }
    echo "syncing: changed=$this->changed valid=$this->isValid<br />";
    $sql_result = -1;
    //obtain the *clean* parameter='value' data that has been SQL-cleansed
    //this will also trip any complex fields to sync
    $vals = $this->_sqlvals();
    if ($vals != "") {
      if ($this->id != -1) {
        //it's an existing record, so update
        $q = "UPDATE $this->table "
            ."SET $vals "
            ."WHERE $this->idfield=".qw($this->id);
        $sql_result = db_quiet($q, $this->fatal_sql);
      } else {
        //it's a new record, insert it
        $q = "INSERT $this->table SET $vals";
        $sql_result = db_quiet($q, $this->fatal_sql);
        # FIXME: do we need to check that this was successful in here?
        //the record number can now be copied into the object's data.
        $this->id = db_new_id();
        $this->fields[$this->idfield]->set($this->id);
      }
    }
    return $sql_result;
  }

  function _sqlvals() {
    $vals = array();
    foreach ($this->fields as $k => $v) {
      if ($v->changed) {
        //obtain a string of the form "name='Stuart'" from the field.
        //Complex fields can use this as a JIT syncing point, and may
        //choose to return nothing here, in which case their entry is
        //not added to the return list for the row
        $sqlval = $this->fields[$k]->sqlSetStr();
        if ($sqlval) {
          echo "SQLUpdate: '$sqlval' <br />";
          $vals[] = $sqlval;
        }
        #$vals[] = "$k=" . qw($v->value);
      }
    }
    #echo "<pre>"; print_r($vals); echo "</pre>";
    return join(', ',$vals);
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
   * Fill this object (i.e. its fields) from the SQL query
  **/
  function fill() {
    $q = "SELECT * FROM "
        ."$this->table "
        ."WHERE $this->idfield=".qw($this->id).' '
        .(($this->recStart !== '') && ($this->recNum !== '') ? "LIMIT $this->recStart,$this->recNum" : '');
    $g = db_get_single($q);
    #echo "<pre>";print_r($g);echo "</pre>";
    foreach ($this->fields as $k => $v) {
      echo "Filling $k ";
      $val = issetSet($g,$k);
      $this->fields[$k]->set($val);
      #echo $this->fields[$k]->text_dump();
    }
    //in case we get no rows back from the database, we have to have an id
    //present otherwise we're in trouble next time
    #echo "Completed fill, id=$this->id\n";
    echo "Filling $this->idfield ";
    $this->fields[$this->idfield]->set($this->id);
  }

  /** 
   * Quick and dirty dump of fields (values only, not a full print_r
  **/
  function text_dump() {
    $t  = "<pre>$this->dumpheader $this->table (id=$this->id)\n{\n";
    foreach ($this->fields as $k => $v) {
      $t .= "\t".$v->text_dump();
    }
    $t .= "}\n</pre>";
    return $t;
  }

  function display() {
    return $this->text_dump();
  }

  function displayInTable($j) {
    $t = "<table class='tabularobject'>";
    foreach ($this->fields as $k => $v) {
      $t .= $v->displayInTable($j);
    }
    $t .= "</table>";
    return $t;
  }

  function changeNamebase($newname) {
    $this->namebase = $newname;
    foreach ($this->fields as $k => $v) {
      $this->fields[$k]->namebase = $newname;
    }
  }

} // class dbrow

?> 
