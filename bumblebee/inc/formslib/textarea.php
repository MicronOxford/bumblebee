<?php
/**
* a textarea widget
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
require_once 'field.php';
/** type checking and data manipulation */
require_once 'inc/typeinfo.php';

/**
* a textarea widget
*
* @package    Bumblebee
* @subpackage FormsLibrary
*/
class TextArea extends TextField {

  function TextArea($name, $longname='', $description='') {
    parent::TextField($name, $longname, $description);
  }

  function selectable() {
    $t  = '<textarea name="'.$this->namebase.$this->name.'" ';
    $t .= 'title="'.$this->description.'" ';
    $t .= (isset($this->attr['rows']) ? 'rows="'.$this->attr['rows'].'" ' : '');
    $t .= (isset($this->attr['cols']) ? 'cols="'.$this->attr['cols'].'" ' : '');
    $t .= '>';
    $t .= xssqw($this->getValue());
    $t .= '</textarea>';
    return $t;
  }
  
} // class TextArea


?> 
