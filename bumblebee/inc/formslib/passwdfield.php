<?php
/**
* a text field that is designed to hold passwords
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage FormsLibrary
*/

/** parent object */
include_once 'textfield.php';
/** type checking and data manipulation */
include_once 'inc/typeinfo.php';

class PasswdField extends TextField {
  var $crypt_method = 'md5';

  function PasswdField($name, $longname='', $description='') {
    //$this->DEBUG = 10;
    parent::TextField($name, $longname, $description);
  }

  function selectable() {
    $t  = '<input type="password" name="'.$this->namebase.$this->name.'" ';
    $t .= (isset($this->attr['size']) ? 'size="'.$this->attr['size'].'" ' : '');
    $t .= (isset($this->attr['maxlength']) ? 'maxlength="'.$this->attr['maxlength'].'" ' : '');
    $t .= '/>';
    return $t;
  }

  /**
   * We shouldn't give up our data too easily...
   */ 
  function getValue() {
    return '';
  }
  
  function update($data) {
    if (parent::update($data)) {
      return ($this->changed = ($this->value != ''));
    } 
    return false;
  }
  
  /**
   * return a SQL-injection-cleansed string that can be used in an SQL
   * UPDATE or INSERT statement. i.e. "name='Stuart'".
   *
   * @return string  in SQL assignable form
   */
  function sqlSetStr($name='') {
    if (empty($name)) {
      $name = $this->name;
    }
    if (! $this->sqlHidden && $this->value != '') {
      if ($this->crypt_method != '' && is_callable($this->crypt_method)) {
        $crypt_method = $this->crypt_method;
        $pass = $crypt_method($this->value);
      } else {
        $pass = $this->value;
      }
      return $name ."='$pass'";
    } else {
      return '';
    }
  }

  
  
} // class PasswdField


?> 
