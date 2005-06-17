<?php
# $Id$
# JoinData: object to deal with JOINed data in the database, mimicks a Field
# for use in a DBRow although has autonomous data.

include_once 'field.php';
include_once 'idfield.php';
include_once 'dbrow.php';
include_once 'inc/db.php';

/**
  * The entries in one table can be a matrix with the coordinates from two others
  *
  * We respect the 'field' interface while overriding pretty much all of it.
  *
  * Typical usage:
  *   // jointable has structure: key1 key2 field1 field2 field3...
  *   // where key1 is the id key of table1 and key2 is the id key for table2.
  *   // we will display field* in rows for each key2 for a given key1
  *   $f = new JoinMatrix('jointable', 'jtid'
                       'key1', 'id1', 'table1',
                       'key2', 'id2', 'table2', $name, $longname, $description);
  *   $key1 = new TextField('key1', 'label1');
  *   $key1->setFormat('id', '%s (%s)', array('name', 'longname'));
  *   $key2 = new TextField('key2', 'label2');
  *   $f->addKeys($key1, $key2);
  *   $f1 = new TextField('field1', 'label');
  *   $f->addElement($f1);
  *   $f2 = new TextField('field2', 'label');
  *   $f->addElement($f2);
  *   $f->setKey($val);
  */
class JoinMatrix extends Field {
  var $joinTable;
  var $jtIdField;
  
  var $jtConstKeyId;
  var $jtConstKeyCol;
  var $table1;
  var $table1IdCol;
  var $key1;
  var $header1 = array();
  
  var $jtVarKeyCol;
  var $table2;
  var $table2IdCol;
  var $key2;
  
  var $protoRow;
  var $rows;
  var $number = 0;
  var $colspan = 1;
  var $table2All;
  var $numFields = 0;
  var $fatal_sql = 0;

  function JoinMatrix($joinTable, $jtId,
                      $jtConstKeyCol, $table1IdCol, $table1,
                      $jtVarKeyCol,   $table2IdCol, $table2,
                      $name, $longname, $description='') {
    parent::Field($name, $longname, $description);
    //$this->DEBUG=10;
    $this->joinTable = $joinTable;
    $this->jtIdField = $jtId;
    $this->jtConstKeyCol = $jtConstKeyCol;
    $this->table1IdCol = $table1IdCol;
    $this->table1 = $table1;
    $this->jtVarKeyCol = $jtVarKeyCol;
    $this->table2IdCol = $table2IdCol;
    $this->table2 = $table2;
    $this->protoRow = new DBRow($this->joinTable, -1);
    $id = new IdField($jtId);
    $this->protoRow->addElement($id);
    $k1 = new IdField($jtConstKeyCol);
    $this->protoRow->addElement($k1);
    $k2 = new IdField($jtVarKeyCol);
    $this->protoRow->addElement($k2);
    $this->protoRow->editable = 1;
    $this->protoRow->autonumbering = 1;
    $this->header2 = array();
    $this->rows = array();
    $this->notifyIdChange = 1;
  }

  function addKeys($key1, $key2) {
    $this->key1 = $key1;
    //$this->key1->namebase = $this->table1;
    $this->key2 = $key2;
    //$this->key2->namebase = $this->table2;
  }
  
  function setKey($id) {
    $this->jtConstKeyId = $id;
    $this->_fillFromProto();
    //preDump($this);
  }

  function _populateList() {
    global $TABLEPREFIX;
    $this->table2All = array();
    $q = 'SELECT '.$this->table2IdCol.', '.$this->key2->name
        .' FROM '.$TABLEPREFIX.$this->table2.' AS '.$this->table2
        .' ORDER BY '.$this->key2->name;
    $sql = db_get($q, $this->fatal_sql);
    // FIXME: mysql specific functions
    if (mysql_num_rows($sql)==0) {
      $this->number = 0;
      return;
    } else {
      while ($g = mysql_fetch_array($sql)) {
        $this->table2All[] = $g;
      }
    }
    $this->number = count($this->table2All);
  }

  function addElement($field) {
    $this->protoRow->addElement($field);
    $this->header1[] = $field;
    $this->numFields++;
  }

  function _createRow($rowNum) {
    $this->rows[$rowNum] = $this->protoRow;
    $this->rows[$rowNum]->setNamebase($this->name.'-'.$rowNum.'-');
  }

  function _fillFromProto() {
    $this->_populateList();
    $this->log('Creating rows: '.$this->number);
    for ($i=0; $i < $this->number; $i++) {
      $this->_createRow($i);
      $this->rows[$i]->fields[$this->jtConstKeyCol]->value = $this->jtConstKeyId;
      $this->rows[$i]->fields[$this->jtVarKeyCol]->value = $this->table2All[$i][$this->table2IdCol];
      $this->rows[$i]->ignoreId = true;
      $this->rows[$i]->recStart = $i;
      $this->rows[$i]->restriction = 
                      $this->jtVarKeyCol   .'='. qw($this->table2All[$i][$this->table2IdCol])
             .' AND '.$this->jtConstKeyCol .'='. qw($this->jtConstKeyId); 
      $this->rows[$i]->fill();
//       $this->rows[$i]->insertRow = ! ($this->rows[$i]->fields[$this->table2IdCol]->value > 0);
    }
  }
  
  function display() {
    return $this->selectable();
  }

  function selectable() {
    $eol = "\n";
    $t = '<table><tr><td></td>'.$eol;
    for ($field=0; $field < $this->numFields; $field++) {
      $t .= '<td title="'.$this->header1[$field]->description.'">'
              .$this->header1[$field]->longname.'</td>'.$eol;
    }
    $t .= '</tr>'.$eol;
    for ($row=0; $row<$this->number; $row++) {
      $t .= '<tr><td>'.$this->table2All[$row][$this->key2->name]
           .$this->rows[$row]->fields[$this->jtVarKeyCol]->hidden()
           .'</td>'.$eol;
      for ($field=0; $field < $this->numFields; $field++) {
        $f =& $this->rows[$row]->fields[$this->header1[$field]->name];
        if ($this->editable) {
          $ft = $f->selectable();
        } else { 
          $ft = $f->selectedValue();
        }
        $t .= '<td title="'.$f->description.'">'.$ft.'</td>'.$eol;
      }
      $t .= '</tr>'.$eol;
    }
    $t .= '</table>';
    return $t;
  }
  
  function selectedValue() {
    $editable = $this->editable;
    $this->editable = 0;
    $t = $this->selectable();
    $this->editable = $editable;
    return $t;
  }

  function displayInTable($cols) {
    //check how many fields we need to have (again) as we might have to show more this time around.
    //$cols += $this->colspan;
    $eol = "\n";
    $t = '<tr><td colspan="'.($cols+$this->colspan).'" title="'.$this->description.'">'
                      .$this->longname.'</td></tr>'
        .'<tr><td colspan="'.$this->colspan.'">'.$eol;
    $t .= $this->selectable();
    for ($i=0; $i<$cols-2; $i++) {
      $t .= '<td></td>';
    }
    $t .= '</tr>'.$eol;
    return $t;
  }

  function update($data) {
    for ($i=0; $i < $this->number; $i++) {
      $rowchanged = $this->rows[$i]->update($data);
      if ($rowchanged) {
        $this->log('JoinData-Row '.$i.' has changed.');
        foreach (array_keys($this->rows[$i]->fields) as $k) {
          #$this->rows[$i]->fields[$this->jtRightIDCol]->changed = $rowchanged;
          #if ($v->name != $this->jtRightIDCol && $v->name != $this->jtLeftIDCol) {
            $this->rows[$i]->fields[$k]->changed = $rowchanged;
          #}
        }
      }
      $this->changed += $rowchanged;
    }
    $this->log('Overall JoinData row changed='.$this->changed);
    return $this->changed;
  }

  /**
    * trip the complex field within us to sync(), which allows us
    * to then know our actual value (at last).
    */
  function sqlSetStr($name='') {
    //$this->DEBUG=10;
    #echo "JoinData::sqlSetStr";
    $this->_joinSync();
    //We return an empty string as this is only a join table entry,
    //so it has no representation within the row itself.
    return '';
  }

  /**
    * synchronise the join table
    */
  function _joinSync() {
    for ($i=0; $i < $this->number; $i++) {
      #echo "before sync row $i oob='".$this->oob_status."' ";
      $this->changed += $this->rows[$i]->changed;
      $this->log('JoinData::_joinSync(): Syncing row '.$i);
      $this->oob_status |= $this->rows[$i]->sync();
      $this->oob_errorMessage .= $this->rows[$i]->errorMessage;
      $this->changed += $this->rows[$i]->changed;
      #echo " after sync row $i oob='".$this->oob_status."'";
    }
  }
  
  
  /**
   * override the isValid method of the Field class, using the
   * checkValid method of each member row completed as well as 
   * cross checks on other fields.
  **/
  function isValid() {
    $this->isValid = 1;
    for ($i=0; $i < $this->number; $i++) {
      $this->isValid = $this->rows[$i]->checkValid() && $this->isValid;
    }
    return $this->isValid;
  }
  
  function idChange($newId) {
    for ($i=0; $i < $this->number; $i++) {
      $this->rows[$i]->setId($newId);
    }
  }
  
  function setNamebase($namebase='') {
    for ($i=0; $i < $this->number; $i++) {
      $this->rows[$i]->setNamebase($namebase);
    }
    $this->protoRow->setNamebase($namebase);
  }

  function setEditable($editable=false) {
    for ($i=0; $i < $this->number; $i++) {
      $this->rows[$i]->setEditable($editable);
    }
    $this->protoRow->setEditable($editable);
  }
  
} // class JoinMatrix

?> 
