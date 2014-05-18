<?php
/**
* a table of checkboxes for different options that are condensed into a single value by bitmask
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage FormsLibrary
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

/** parent object */
require_once 'textfield.php';
/** uses checkbox table list */
require_once 'checkboxtablelist.php';

// ===================  NOTE  ======================
// $this->select suffers from a horrible hack around a PHP 4.x bug
// All we want to do is something like:
//    $this->select->foo($a);
// but that causes $this->select to suddenly become a reference: Object not &Object (see a var_dump)
// see http://bugs.php.net/bug.php?id=24485 and http://bugs.php.net/bug.php?id=30787
// There is no prospect of this bug ever being fixed in PHP4.
// If we can come up with a clone() function for PHP4 that deals with references then
// we can at least get rid of some of these horrible hacks:
//     $s = $this->select;
//     $s->foo($a);
//     $this->select = $s;
// ================================================


/**
* a table of checkboxes for different options that are condensed into a single value by bitmask
*
* @package    Bumblebee
* @subpackage FormsLibrary
*/
class Bitmask extends TextField {
  /** @var boolean  show "expand" and "collapse" links */
  var $showHideButtons = false;

  /** @var CheckBoxTableList   the actual check box list that will be shown */
  var $select;

  /** @var integer  number of columns in the table  */
  var $numcols    = '';
  /** @var integer  number of extra columns to be added to the table  */
  var $numExtraInfoCols = '';
  /** @var string   html/css class for each row in the table  */
  var $trclass    = 'itemrow';
  /** @var string   html/css class for left-side table cell */
  var $tdlclass   = 'itemL';
  /** @var string   html/css class for right-side table cell */
  var $tdrclass   = 'checkBoxEntry';
  /** @var string   html/css class for entire table */
  var $tableclass = 'selectlist';
  /** @var array    list of table headings to be put at the top of the table */
  var $tableHeading;
  /** @var array    list of strings to be included in the footer of the table */
  var $footer;
  /** @var boolean  generate select/deselect links at the bottom of each column */
  var $includeSelectAll = false;

  /**
  *  Create a new BitmaskPopup
  *
  * @param string $name   the name of the field (db name, and html field name
  * @param string $description  used in the html title of the list
  */
  function Bitmask($name, $title, $description='', $checkbox_description) {
    parent::TextField($name, $title, $description);
    // $this->DEBUG=10;
    $select = new CheckBoxTableList($name); // see note above before refactoring $select out
    $bit = new CheckBox('bit', $checkbox_description);
    $hidden = new TextField('value');
    $select->addFollowHidden($hidden);
    $select->addCheckBox($bit);
    $select->setFormat('id', '%s', array('iv'));
    $select->addSelectAllFooter(true);
    $this->select = $select;
    $this->numcols=1;
  }

  /**
  * Provides a set of values for the droplist rather than filling it from a db query
  *
  * cf. ChoiceList::connectDB
  *
  * @param array $list List of label=>value pairs
  */
  function setValuesArray($list, $idfield='id', $valfield='iv'){
    $s = $this->select;   // see note above for why we have this horrible hack
    $s->setValuesArray($list, $idfield, $valfield);
    $this->select = $s;
  }

  /**
  *  Accessor method to set the table column headings
  *
  * @param array   new headings to use for the table
  */
  function setTableHeadings($headings) {
    $s = $this->select;   // see note above for why we have this horrible hack
    $s->tableHeadings = $headings;
    $this->select = $s;
  }

  function update($data) {
    #echo "Name=({$this->namebase},{$this->name})<br />";
    $value = 0;
    $fieldSet = false;
    $s = $this->select;   // see note above for why we have this horrible hack
    $s->namebase = $this->namebase;
    $s->formname = $this->formname;
    $s->MakeBoxMatrix();
    for ($i=0; $i<count($s->list->choicelist); $i++) {
      $fieldname  = $s->boxMatrix[$i][0]->formname . $s->boxMatrix[$i][0]->namebase . $s->boxMatrix[$i][0]->name;
      $fieldvalue = $s->boxMatrix[$i][0]->formname . $s->boxMatrix[$i][0]->namebase . 'value';
      #echo "/$fieldname/$fieldvalue/";
      if (isset($data[$fieldname]) && isset($data[$fieldvalue])) {
        $value |= $data[$fieldvalue];
        $fieldSet = true;
        #echo "(set)";
        #echo "('$data[$fieldname]')";
      }
      #echo $data[$fieldvalue];
      #echo "#$value<br />";
    }
    if ($fieldSet) {
      $data[$this->formname.$this->namebase.$this->name] = $value;
    }
    $this->select = $s;

    //$this->select->update($data);
    if (parent::update($data)) {
      $this->setChecks($value);
    }
    return $this->changed;
  }

  function set($value) {
    #echo "Set Name=({$this->namebase},{$this->name})<br />";
    parent::set($value);
    $s = $this->select;
    $s->formname = $this->formname;
    $s->namebase = $this->namebase;
    $s->MakeBoxMatrix();
    $this->select = $s;
    $this->setChecks($value);
  }

  function setChecks($value) {
    for ($i=0; $i<count($this->select->list->choicelist); $i++) {
      #echo "Setting $i, $value => ";
      #echo $this->select->boxMatrix[$i][0]->namebase;
      $this->select->boxMatrix[$i][0]->value =
          ($value & $this->select->list->choicelist[$i]['id'])
                 == $this->select->list->choicelist[$i]['id'];
      #echo $this->select->list->choicelist[$i]['id'] . " " . $this->select->boxMatrix[$i][0]->value;
      #echo "<br />";
    }
  }

  /**
  * embed the html within a div to enable some creative js folding
  *
  * @return string  html snippet that will open a new window with the html report
  */
  function wrapHTMLBuffer($contents) {
    if (! $this->showHideButtons) return $contents;

    $id = preg_replace('/[^\w]/', '_', $this->formname.$this->namebase.$this->name);
    $func = "toggle$id";

    $jsbuf = "
    <div class='collapsecontrol' id='show$id' style='display: none;'><a href='javascript:$func(true)'>"
                  .T_('expand')."</a></div>
    <div class='collapsecontrol' id='hide$id' style='display: none;'><a href='javascript:$func(false)'>"
                  .T_('collapse')."</a></div>
    <div id='table$id'>$contents</div>
    <script type='text/javascript'>
      function $func(show) {
        if (show) {
          var id1 = document.getElementById('show$id');
          id1.style.display = 'none';
          var id2 = document.getElementById('hide$id');
          id2.style.display = 'block';
          var id3 = document.getElementById('table$id');
          id3.style.display = 'inline';
        } else {
          var id1 = document.getElementById('show$id');
          id1.style.display = 'block';
          var id2 = document.getElementById('hide$id');
          id2.style.display = 'none';
          var id3 = document.getElementById('table$id');
          id3.style.display = 'none';
        }
      }
      $func(false);
    </script>
      ";
    return $jsbuf;
  }

  function displayInTable($numCols=3) {
    #preDump($this->select);
    $this->select->numcols    = $this->numcols;
    $this->select->numExtraInfoCols = $this->numExtraInfoCols;
    $this->select->trclass    = $this->trclass;
    $this->select->tdlclass   = $this->tdlclass;
    $this->select->tdrclass   = $this->tdrclass;
    $this->select->tableclass = $this->tableclass;
    $this->select->tableHeading = $this->tableHeading;
    $this->select->footer     = $this->footer;
//     $this->select->includeSelectAll = $this->includeSelectAll;

    $tableclass = (isset($this->tableclass) ? " class='$this->tableclass'" : "");
    $t  = #'<html><head></head><body>'
         #.
         "<table title='{$this->description}' $tableclass>\n"
         .$this->select->displayInTable($this->numcols)
         ."</table>";#</body></html>\n";
    $enc = $this->wrapHTMLBuffer($t);
    return "<tr><td>{$this->longname}</td><td>$enc</td></tr>\n";
  }

  /**
  * PHP5 clone method
  *
  * PHP5 clone statement will perform only a shallow copy of the object. Any subobjects must also be cloned
  */
  function __clone() {
    //echo "<hr /><br />Cloning bitmask<br />";
    // Force a copy of contents of $this->list
    if (is_object($this->select)) $this->select = clone($this->select);
  }

} // class Bitmask


?>
