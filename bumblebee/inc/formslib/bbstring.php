<?php

/**
* String type that can be automatically translated
*
* @author     Seth Sims
* @copyright  Copyright Seth Sims
* @licence    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $id: bbstring.php,
* @package    Bumblebee
* @subpackage FormsLibrary
*
* path (bumblebee root)/inc/formslib/bbstring.php
*/

require_once 'inc/typeinfo.php';

class bbString {

    /** holds the internal bb representation of this string */
    var $internal = '';

    /** holds the translation of the internal representation */
    var $external = '';

    function bbString($string, $external = '') {

        $this->internal = $string;
        $this->external = $external;

    } //end function constructor

    /** returns the untranslated version of this string */
    function getInternalRep() {

        return $this->internal;

    } //end function getInternalRep

    /** returns the translated version of this string */
    function getExternalRep() {

        if($this->external === '') {

            return $this->internal;

        } else {

            return $this->external;

        } //end if-else

    } //end function getExternalRep

    function isUTF8() {

        return isUTF8($this->internal);

    }//end function isUTF8

    function isBlank() {

        return strlen(trim($this->internal)) == 0;

    } //end function isBlank

} //class bbstring
?>
