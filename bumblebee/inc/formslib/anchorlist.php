<?php
/**
* anchor list (&lt;li&gt;&lt;a href="$href"&gt;$name&lt;/a&gt;&lt;/li&gt;) for a ChoiceList
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage FormsLibrary
*/

/** choicelist parent object */
include_once 'choicelist.php';

class AnchorList extends ChoiceList {
  var $hrefbase;
  var $ulclass = 'selectlist';
  var $liclass = 'item';
  var $aclass  = 'itemanchor';

  function AnchorList($name, $description='') {
    $this->ChoiceList($name, $description);
    #ChoiceList::ChoiceList($name, $description);
  }

  function format($data) {
    $aclass  = (isset($this->aclass) ? " class='$this->aclass'" : '');
    $t .= "<a href='$this->hrefbase".$data[$this->formatid]."'$aclass>"
         .$this->formatter[0]->format($data)
         .'</a>'
         .$this->formatter[1]->format($data);
    return $t;
  }

  function display() {
    $ulclass = (isset($this->ulclass) ? " class='$this->ulclass'" : '');
    $liclass = (isset($this->liclass) ? " class='$this->liclass'" : '');
    $t  = "<ul title='$this->description'$ulclass>\n";
    if (is_array($this->list->choicelist)) {
      foreach ($this->list->choicelist as $v) {
        $t .= "<li$liclass>";
        #$t .= print_r($v, true);
        $t .= $this->format($v);
        $t .= "</li>\n";
      }
    }
    $t .= "</ul>\n";
    return $t;
  }

} // class AnchorList


?> 
