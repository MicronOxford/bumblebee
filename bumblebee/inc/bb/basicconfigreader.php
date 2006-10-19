<?php
/**
* Generic configuration management object
*
* Parses the {@link bumblebee.ini } file
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Misc
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

class BasicConfigReader {

  var $data;

  var $configError = false;

  function BasicConfigReader() {
    static $constructed = false;
    #echo "Constructor called";
    if ($constructed) {
      trigger_error('ConfigReader is a singleton. Instantiate it only once if you must (and you must instantiate it once if you want to inherit it) and then use getInstance()', E_USER_ERROR);
    }
    BasicConfigReader::_instanceManager($this);
    $constructed = true;
  }

  function & getInstance() {
    return BasicConfigReader::_instanceManager();
  }

  function & _instanceManager($newInstance = null) {
    static $instance = array();

    if ($newInstance == null) {
      if (count($instance) < 1 || $instance[0] == null) {
        #echo "Making instance";
        $instance[0] = new BasicConfigReader();
      }
      #echo "Returning instance";
      return $instance[0];
    } else {
      #echo "registering instance";
      $instance[0] = & $newInstance;
    }
  }

  function loadFile($filename, $fatalErrors=true) {
    $this->data = parse_ini_file($filename, 1);
    if (! is_array($this->data)) {
      // if the config file doesn't exist, then we're pretty much stuffed
      $this->configError = true;
      trigger_error("System misconfiguration: I could not find the config file '$filename'. Please give me a config file so I can do something useful.", $fatalErrors ? E_USER_ERROR : E_USER_NOTICE);
    }
  }

  function value($section, $parameter, $default=null) {
    if (isset($this->data[$section]) && is_array($this->data[$section])) {
      return issetSet($this->data[$section], $parameter, $default);
    } else {
      return $default;
    }
  }

  function getSection($section) {
    if(isset($this->data[$section]) && is_array($this->data[$section])) {
      return $this->data[$section];
    } else {
      trigger_error("Tried to access non existent section");
    }
  }

  /** merge an array into the config data. If a section is provided the array is added as a new sub key */
  function mergeConfig($other_array, $section = null) {
    if(is_array($other_array)) {
      if($section == null) {
        $this->data = array_merge($this->data, $other_array);
      } else {
        $this->data = array_merge($this->data, array($section => $other_array));
      }
    } else {
      trigger_error("Tried to merge a non array into the config values");
    }
  }

}


?>
