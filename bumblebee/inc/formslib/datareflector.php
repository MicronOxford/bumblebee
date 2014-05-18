<?php
/**
* reflect all submitted data back to the user
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

/**
* Reflects all inputted data through hidden fields
*
* Can optionally exclude some fields
*
* Typical usage:<code>
*     $ref = new DataReflector();
*     $ref->exclude(array('id', 'name'));
*     $ref->excludeRegEx(array('/^setting-.+/'));
*     echo $ref->display($_POST);
* </code>
* @package    Bumblebee
* @subpackage FormsLibrary
*/
class DataReflector {
  /** @var string  basename that will be appended to all keys */
  var $basename = '';
  /** @var array   list of fields to exclude from the datareflector */
  var $excludes = array();
  /** @var array   list of regexp fields to exclude from the datareflector  */
  var $excludesRegEx = array();
  /** @var array   list of list of keys that have value restrictions */
  var $limitKeys = array();
  /** @var array   list of list of values that are acceptable for the limited keys */
  var $limitValues = array();
  /** @var integer   debug level    */
  var $DEBUG = 0;

  /**
  *  Create a datareflector object
  */
  function DataReflector() {
  }

  /**
  *  Creates hidden fields html representation
  *
  * @param array $PD  array of $field => $value
  * @return string  html hidden fields
  */
  function display($PD) {
    $t = '';
    foreach ($PD as $key => $val) {
      if ($this->_includeKey($key, $val)) {
      // then we should be included in the reflection
        $t .= sprintf('<input type="hidden" name="%s" value="%s" />',
                        $this->basename.xssqw($key),
                        xssqw($val)
                    );
      }
    }
    return $t;
  }

  function _includeKey($key, $val) {
    if (in_array($key, $this->excludes)) {
      return false;
    }
    foreach ($this->excludesRegEx as $re) {
      if (preg_match($re, $key)) {
        return false;
      }
    }
    foreach ($this->limitKeys as $seq => $lk) {
      if (in_array($key, $lk)) {
        // a restricted key; check that the key's value is OK
        if (! in_array($val, $this->limitValues[$seq])) {
          return false;
        }
      }
    }
    return true;
  }

  /**
  *  Exclude these fields from the reflection
  *
  * @param mixed  $arr single field or list of fields to exclude
  */
  function exclude($arr) {
    if (! is_array($arr)) {
      $this->excludes[] = $arr;
    } else {
      $this->excludes = array_merge($this->excludes, $arr);
    }
  }

  /**
  *  Exclude the fields that match these regexps from the reflection
  *
  * @param mixed   $arr single regexp or list of regexps to use for exclusion
  */
  function excludeRegEx($arr) {
    if (! is_array($arr)) {
      $this->excludesRegEx[] = $arr;
    } else {
      $this->excludesRegEx = array_merge($this->excludesRegEx, $arr);
    }
  }

  function excludeLogin() {
    $this->excludes[] = 'username';
    $this->excludes[] = 'pass';
    $this->excludes[] = 'magicTag';
  }

  function addLimit($keys, $values) {
    $this->limitKeys[] = $keys;
    $this->limitValues[] = $values;
  }

} // class DataReflector

?>
