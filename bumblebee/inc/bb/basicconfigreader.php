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

if (version_compare(PHP_VERSION, "5") >= 0) {
  // Conditionally include the singleton functions if PHP5 is being used
  require_once 'inc/bb/basicconfigreadersingleton_php5.php';

} else {
  // pull in the singleton file and translate the PHP5 to PHP4 (that is still known to work)
  $file = file_get_contents('inc/bb/basicconfigreadersingleton_php5.php', true);
  $file = preg_replace(
            array('@static function@', '@<\?php@', '@\?>@'),
            array('function',          '',         ''),
            $file);
  eval($file);
}

class BasicConfigReader extends BasicConfigReaderSingleton {

  var $data;

  var $configError = false;

  var $configFileLocation = null;

  function BasicConfig() {
    parent::BasicConfigReaderSingleton();
  }

  function SetFileLocation($directory) {
    $this->configFileLocation = $directory;
  }

  function LoadFile($filename, $fatalErrors=true) {
    $this->data = $this->_readConfigFile($filename, $fatalErrors);
  }

  function MergeFile($filename, $section=null, $fatalErrors=true) {
    $newdata = $this->_readConfigFile($filename, $fatalErrors);
    $this->mergeConfig($newdata, $section);
  }

  function _readConfigFile($filename, $fatalErrors=true) {
    $source = $this->configFileLocation . DIRECTORY_SEPARATOR . $filename;
    if (! file_exists($source) && file_exists($filename)) {
      $this->configError = true;
      trigger_error("System misconfiguration: I could fine the config file '$filename' but
      not in the designated location.", $fatalErrors ? E_USER_ERROR : E_USER_NOTICE);
    } elseif (! file_exists($source)) {
      $this->configError = true;
      trigger_error("System misconfiguration: I could not find the config file '$filename'. Please give me a config file so I can do something useful.", $fatalErrors ? E_USER_ERROR : E_USER_NOTICE);
    }
    $newdata = parse_ini_file($source, 1);
    if (! is_array($newdata)) {
      // if the config file doesn't exist, then we're pretty much stuffed
      $this->configError = true;
      trigger_error("System misconfiguration: I could not find the config file '$filename'. Please give me a config file so I can do something useful.", $fatalErrors ? E_USER_ERROR : E_USER_NOTICE);
    }
    return $newdata;
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

  function getSections() {
    return array_keys($this->data);
  }

  /** merge an array into the config data. If a section is provided the array is added as a new sub key */
  function MergeConfig($other_array, $section = null) {
    if(is_array($other_array)) {
      if($section == null) {
        $this->data = array_merge($this->data, $other_array);
      } else if(!isset($this->data[$section])) {
        $this->data = array_merge($this->data, array($section => $other_array));
      } else {
        $tmp = $this->data[$section];
        $tmp = array_merge($tmp, $other_array);
        $this->data[$section] = $tmp;
      }
    } else {
      trigger_error("Tried to merge a non array into the config values");
    }
  }

  function MergeDatabaseTable($table = 'settings', $sectionColumn = 'section',
                              $parameterColumn = 'parameter', $valueColumn = 'value') {
    global $TABLEPREFIX;
    $q = "SELECT $sectionColumn as section, $parameterColumn as parameter, $valueColumn as value from $TABLEPREFIX$table";
    $sql = db_get($q, false);
    while ($g = db_fetch_array($sql)) {
      if (! isset($this->data[$g['section']]) || ! is_array($this->data[$g['section']])) {
        $this->data[$g['section']] = array();
      }
      $this->data[$g['section']][$g['parameter']] = $g['value'];
      //printf('[%s]::$s = %s', $g['section'], $g['parameter'], $g['value']);
    }
  }

}


?>
