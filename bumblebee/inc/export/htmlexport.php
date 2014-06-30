<?php
/**
* Construct an HTML export from array representation
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Export
*
* path (bumblebee root)/inc/export/htmlexport.php
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

/** base type for html export */

/** constants for defining export formatting and codes */
require_once 'inc/exportcodes.php';
require_once 'inc/export/xmlexportbase.php';
/**
* Construct an HTML export from array representation
*
* @package    Bumblebee
* @subpackage Export
*/
class HTMLExport extends XMLExportBase {

  /** @var boolean      export the data as one big table with header rows between sections  */
  var $bigtable = true;

  /** @var string       header to the report  */
  var $header;

  /**
  *  Create the HTMLExport object
  *
  * @param ArrayExport  &$exportArray
  */
  function HTMLExport(&$exportArray) {
      parent::XMLExportBase($exportArray);
  }

  function do_start(&$data, $metadata=NULL) { return '<div id="bumblebeeExport">'; }
  function do_end(&$data, $metadata = NULL) { return (!$this->bigtable)?('</div>'):('</table></div>'); }

  function do_header(&$data, $metadata = NULL) {

      $this->header = $data;

      $temp  = '<div class="exportHeader">'.xssqw($data).'</div>'.EOL;

      if($this->bigtable) {
          $temp .= '<table class="exportdata">'.EOL;
      } //end if

      return $temp;
  } //end do_report_header

  function do_section_header(&$data, $metadata = NULL) {

        if(!$this->bigtable) {

            return '<div class="exportSectionHeader">'.xssqw($data).'</div>'.EOL;
        } //end if

        $numcols = $metadata['numcols'];
        return '<tr class="exportSectionHeader"><td colspan="'.$numcols.'" class="exportSectionHeader">'
                        .xssqw($data).'</td></tr>'.EOL;
  } //end do_report_section_header

  function do_table_start(&$data, $metadata = NULL) {

      if(!$this->bigtable) {
           return '<table class="exportdata">'.EOL;
      } //end if
  } //end do_report_table_start

  function do_table_end(&$data, $metadata = NULL) {

      if(!$this->bigtable) {
           return '</table>'.EOL;
      } //end if
  } //end do_report_table_end

  function do_table_header(&$data, $metadata = NULL) {

      return '<tr class="header">' .$this->_formatRowHTML($data, true) .'</tr>'.EOL;

  } //end do_report_table_header

  function do_table_total(&$data, $metadata = NULL) {

      return '<tr class="totals">'.$this->_formatRowHTML($data).'</tr>'.EOL;

  } //end do_report_table_total

  function do_table_footer(&$data, $metadata = NULL) {

      return '<tr class="footer">'.$this->_formatRowHTML($data).'</tr>'.EOL;

  } //end do_report_table_footer

  function do_table_row(&$data, $metadata = NULL) {

      return '<tr>'.$this->_formatRowHTML($data).'</tr>'.EOL;

  } //end do_export_report_table_row

  /**
  * generate the HTML for a row
  *
  * @return string row
  */
  function _formatRowHTML($row, $isHeader=false) {
    $b = '';
    $numfields = count($row);  //FIXME: can this be cached?
    for ($j=0; $j<$numfields; $j++) {
      $b .= $this->_formatCellHTML($row[$j], $isHeader);
    }
    return $b;
  }

  /**
  * generate the HTML for a single cell
  *
  * @return string cell
  */
  function _formatCellHTML($d, $isHeader) {
    $t = '';
    $val = $d['value'];
    if (! $isHeader) {
      switch($d['format'] & EXPORT_HTML_ALIGN_MASK) {
        case EXPORT_HTML_CENTRE:
          $align='center';
          break;
        case EXPORT_HTML_LEFT:
          $align='left';
          break;
        case EXPORT_HTML_RIGHT:
          $align='right';
          break;
        default:
          $align='';
      }
      $align = ($align!='' ? 'align='.$align : '');
      $t .= '<td '.$align.'>'.htmlentities($val, ENT_QUOTES, 'UTF-8').'</td>';
    } else {
      $t .= '<th>'.htmlentities($val).'</th>';
    }
    return $t;
  }

  /**
   * embed the html within a blank page to create the report in a separate window
   *
   * @return nothing
   * @todo //TODO: potential memory hog (stores HTML output in three places at once)
   */
    function get_export_new_window() {

	    $conf = ConfigReader::getInstance();
	    $filename = "templates/" . $conf->value('display', 'template') . "/export/exporttemplate.html";
	    $BaseTemplate = $conf->BasePath . "/templates/" . $conf->value('display', 'template');

	    $this->replace_array['/__TITLE__/'] = T_('Data export');
	    $this->replace_array['/__BASETEMPLATE__/'] = $BaseTemplate;

	    $this->_wrapBuffer($filename);
	    #$enchtml = rawurlencode($this->export);
	    $badchars = array(
	           '/"/'       =>    '\u0022',
	           "/'/"       =>    '\u0027',
	           '/</'       =>    '\u003c',
	           '/>/'       =>    '\u003e',
	           '/&/'       =>    '\u0026',
	           "/\n/"      =>    '\n',
	           "/\r/"      =>    '\r');
	    $enchtml = preg_replace(array_keys($badchars),
	                            array_values($badchars),
	                            $this->export);
	    $jsbuf = '<script type="text/javascript">
		    <!--
		    function BBwriteAll(data) {
			    bboutwin = window.open("", "bumblebeeOutput", "");
			    bboutwin.document.write(data);
			    bboutwin.document.close();
		    }

	    data = "'.$enchtml.'";

	    BBwriteAll(data);

	    //-->
	    </script><a href="javascript:BBwriteAll(data)">'.T_('Open Window').'</a>';

	    return $jsbuf;
    }


  function do_finalize() {


  } //end do_finalize


} // class HTMLExport

?>
