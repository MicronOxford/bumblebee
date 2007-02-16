<?php
/**
* the textfield widget primitive
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

require_once 'inc/bb/configreader.php';

/** parent object */
require_once 'field.php';

/**
* The textfield widget primitive
*
* Designed for strings to be edited in a text field widget in the HTML form,
* but is inherited for TimeField, IdField etc
*
* @package    Bumblebee
* @subpackage FormsLibrary
*/
class TextField extends Field {

  /**
  *  Create a new field object, designed to be superclasses
  *
  * @param string $name   the name of the field (db name, and html field name
  * @param string $longname  long name to be used in the label of the field in display
  * @param string $description  used in the html title or longdesc for the field
  */
  function TextField($name, $longname='', $description='') {
    parent::Field($name, $longname, $description);
    $this->valueCleaner = array($this, '_textCleaner');
  }

  function _textCleaner($value) {
    $value = trim($value);

    if (issetSet($this->attr, 'float', false)) {
      $conf = ConfigReader::getInstance();
      if ($conf->value('language', 'use_comma_floats', false)) {
        $value = commaFloat($value);
        // also convert the original db value to a float so that the comparison will be numeric
        $this->value = floatval($this->value);
      }
    }
    return $value;
  }

  function displayInTable($cols=3) {
    $t = '';
    if (! $this->hidden) {
      $errorclass = ($this->isValid ? '' : 'class="inputerror"');
      $t .= "<tr $errorclass><td>$this->longname</td>\n"
          ."<td title='$this->description'>";
      if ($this->editable) {
        $t .= $this->selectable();
        } else {
        $t .= $this->selectedValue();
      }
      if ($this->duplicateName) {
        $t .= '<input type="hidden" name="'.$this->duplicateName.'" '
              .'value="'.xssqw($this->getValue()).'" />';
      }
      $t .= "</td>\n";
      for ($i=0; $i<$cols-2; $i++) {
        $t .= "<td></td>";
      }
      $t .= "</tr>";
    } else {
      $t .= $this->hidden();
    }
    return $t;
  }

  function selectedValue() {
    return xssqw($this->getValue()).$this->hidden();
  }

  function selectable() {
    $value = $this->getValue();
    if ($value !== null && $value !== '' &&
        issetSet($this->attr, 'float', false) && $precision = issetSet($this->attr, 'precision', false)) {
      $value = numberFormatter($value, $precision);
    }
    $t  = '<input type="text" name="'.$this->namebase.$this->name.'" ';
    $t .= 'title="'.$this->description.'" ';
    $t .= 'value="'.xssqw($value).'" ';
    $t .= (isset($this->attr['size']) ? 'size="'.$this->attr['size'].'" ' : '');
    $t .= (isset($this->attr['maxlength']) ? 'maxlength="'.$this->attr['maxlength'].'" ' : '');
    $t .= '/>';
    return $t;
  }

  function hidden() {
    return "<input type='hidden' name='$this->namebase$this->name' "
           ."value='".xssqw($this->getValue())."' />";
  }

} // class TextField


?>
