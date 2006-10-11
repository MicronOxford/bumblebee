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

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

/** parent object */
require_once 'textfield.php';
/** type checking and data manipulation */
require_once 'inc/typeinfo.php';
/** username and password checks */
require_once 'inc/passwords.php';

/**
* a text field that is designed to hold passwords
*
* @package    Bumblebee
* @subpackage FormsLibrary
* @todo //TODO:  js to check double entry passwd is the same
*/
class PasswdField extends TextField {
  /** @var string  algorithm used to encrypt the data  */
  var $crypt_method = 'md5_compat';

  /**
  *  Create a new password field object
  *
  * @param string $name   the name of the field (db name, and html field name
  * @param string $longname  long name to be used in the label of the field in display
  * @param string $description  used in the html title or longdesc for the field
  */
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
  * Don't return data...
  *
  * We shouldn't give up our data too easily.
  */
  function getValue() {
    return '';
  }

  /**
  * Update the value of the field from user-supplied data, but only if the field was filled in
  *
  * Empty values don't count -- that way an unfilled passwd field will never count as changed
  * @param array $data  list of field_name => value pairs
  */
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
  * @param string $name the field name to be used
  * @return string  in SQL assignable form
  */
  function sqlSetStr($name='', $force=false) {
    if (empty($name)) {
      $name = $this->name;
    }
    if (! $this->sqlHidden && $this->value != '') {
      $pass = makePasswordHash($this->value);
      return $name ."='$pass'";
    } else {
      return '';
    }
  }



} // class PasswdField


?>
