<?php
/**
* Provide example entries for existing values next to the choices in a list, e.g. radio list
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage FormsLibrary
*/

/** uses choice list object for examples */
include_once 'dbchoicelist.php';
/** sql manipulation routines */
include_once 'sql.php';

class ExampleEntries {
  var $source;
  var $table,
      $columnmatch,
      $columnreturn;
  var $limit,
      $order;
  var $separator = ', ';
  var $list;

  function ExampleEntries($source, $table, $columnmatch, $columnreturn,
                          $maxentries=3, $order='') {
    $this->source = $source;
    $this->table = $table;
    $this->columnmatch = $columnmatch;
    $this->columnreturn = $columnreturn;
    $this->limit = $maxentries;
    $this->order = ($order != '' ? $order : $columnreturn);
  }

  function fill($id) {
    #echo "Filling for $id";
    $safeid = qw($id);
    $this->list = new DBChoiceList($this->table, $this->columnreturn,
                             "$this->columnmatch=$safeid",
                             $this->order,
                             $this->columnmatch, $this->limit);
  }
    
  function format(&$data) {
    #var_dump($data);
    $this->fill($data[$this->source]);
    $entries = array();
    foreach ($this->list->choicelist as $v) {
      $entries[] = $v[$this->columnreturn];
    }
    $t = implode($this->separator, $entries);
    return $t;
  }

} // class ExampleEntries


?> 
