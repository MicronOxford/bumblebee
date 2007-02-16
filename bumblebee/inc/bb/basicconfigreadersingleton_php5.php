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

class BasicConfigReaderSingleton {

  function BasicConfigReaderSingleton() {
    static $constructed = false;
    #echo "Constructor called";
    if ($constructed) {
      trigger_error('ConfigReader is a singleton. Instantiate it only once if you must (and you must instantiate it once if you want to inherit it) and then use getInstance()', E_USER_ERROR);
    }
    BasicConfigReader::_instanceManager($this);
    $constructed = true;
  }

  static function & getInstance() {
    return BasicConfigReader::_instanceManager();
  }

  static function & _instanceManager($newInstance = null) {
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
      return $instance[0];
    }
  }

}

?>
