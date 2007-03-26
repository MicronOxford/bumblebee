<?php
/**
* A base type that for classes that build xml-based (xhtml, excel, etc.) exports from
* the array export. 
*
* @author     Seth Sims
* @copyright  Copyright Seth Sims
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Export
*
* path (bumblebee root)/inc/export/xmlexportbase.php
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();


/** constants for defining export formatting and codes */
require_once 'inc/exportcodes.php';

/** make the eol avalable to non-interpreted strings */
define('EOL', "\n");

/**
* abstract base type for xml-based exports. 
*
* @package    Bumblebee
* @subpackage Export
*/
class XMLExportBase {
  /** @var string       rendered data   */
  var $export;
  /** @var ArrayExport  data to export    */
  var $ea;
  /** @var Array        the array to be passed to preg_rep */
  var $replace_array; 

  /**
  *  Create the HTMLExport object
  *
  * @param ArrayExport  &$exportArray
  */
  function XMLExportBase(&$exportArray) {
    $this->ea =& $exportArray;
  }
 
  /**
  * parse the data in the array export.
  *
  * @return noting
  *
  * This function is the workhorse of this family of classes and should not be overridden.
  * It calls the event handling functions declared abstract in this class. They sould
  * return a string to be appended to the running contents variable. 
  *     This function unsets parts of the export array no longer needed. It counts on the
  * fact that when unset is called on an array element the elements are not renumbered.
  * This is a php 4/php 5 behavior and if it this behavior changes the function will
  * no longer work. 
  */
  function makeBuffer() {

      $ea =& $this->ea->export;
      
      $this->handle_metadata($ea['metadata']);
      unset($ea['metadata']);

      $numrows = count($ea);

      $contents = '';

      for ($i = 0; $i < $numrows; $i++) {
	  switch ($ea[$i]['type']) {
	      case EXPORT_REPORT_START:
		  $contents .= $this->do_start($ea[$i]['data']);
		  break;

	      case EXPORT_REPORT_END:
		  $contents .= $this->do_end($ea[$i]['data']);
		  break;

	      case EXPORT_REPORT_HEADER:
		  $contents .= $this->do_header($ea[$i]['data']);
		  break;

	      case EXPORT_REPORT_SECTION_HEADER:
		  $contents .= $this->do_section_header($ea[$i]['data']);
		  break;

	      case EXPORT_REPORT_TABLE_START:
		  $contents .= $this->do_table_start($ea[$i]['data']);
		  break;

	      case EXPORT_REPORT_TABLE_END:
		  $contents .= $this->do_table_end($ea[$i]['data']);
		  break;

	      case EXPORT_REPORT_TABLE_HEADER:
		  $contents .= $this->do_table_header($ea[$i]['data']);
		  break;

	      case EXPORT_REPORT_TABLE_ROW:
		  $contents .= $this->do_table_row($ea[$i]['data']);
		  break;

	      case EXPORT_REPORT_TABLE_TOTAL:
		  $contents .= $this->do_table_total($ea[$i]['data']);
		  break;

	      case EXPORT_REPORT_TABLE_FOOTER:
		  $contents .= $this->do_table_footer($ea[$i]['data']);
		  break; 
	  } // end switch

	  unset($ea[$i]);
	  $contents .= EOL; 
      } //end for

      $contents = preg_replace('/\$/', '&#'.ord('$').';', $contents); 
      $this->export =& $contents;
      $this->do_finalize();
  } //end makeBuffer

  /**
  *  handle the metadata in the export.
  *  @var metadata  the metadata to be parsed.
  *  @return nothing
  *
  *  This function places values in replace_array that will be substituted for their place holders
  *  in the template file. This is a catch all implementation of the function and readies all
  *  metadata given by arrayExport for substitution. If a sub-class wishes it can override this
  *  function and only ready the metadata actually used.
  */
  function handle_metadata(&$metadata) {

      $this->replace_array['/__AUTHOR__/']   = $metadata['author'];
      $this->replace_array['/__CREATOR__/']  = $metadata['creator'];
      $this->replace_array['/__TITLE__/']    = $metadata['title'];
      $this->replace_array['/__KEYWORDS__/'] = $metadata['keywords'];
      $this->replace_array['/__SUBJECT__/']  = $metadata['subject'];

  } // end handle_metadata
 
  //TODO comment what these things should do to create a proper export 
  function do_start(&$data, &$metadata = NULL){ _trigger_error(); }
  function do_end(&$data, &$metadata = NULL){ _trigger_error(); }
  function do_header(&$data, &$metadata = NULL) { _trigger_error(); }
  function do_section_header(&$data, &$metadata = NULL){ _trigger_error(); }
  function do_section_footer(&$data, &$metadata = NULL){ _trigger_error(); }
  function do_table_start(&$data, &$metadata = NULL) { _trigger_error(); }
  function do_table_end(&$data, &$metadata = NULL) { _trigger_error(); }
  function do_table_header(&$data, &$metadata = NULL){ _trigger_error(); }
  function do_table_row(&$data, &$metadata = NULL){ _trigger_error(); }
  function do_table_total(&$data, &$metadata = NULL){ _trigger_error(); }

  /** This function is called to allow the subclass to do any final cleanup before the data is echoed out
   *    
   *  @return nothing 
   *
   *  Allows the subclass to do final clean up such as embedding the export into a template with _wrapBuffer
   */
  function do_finalize() { _trigger_error(); }

  /** return the proper file extention of this export */
  function get_file_extention(){ _trigger_error(); } 

  /** return the mime type for this export */
  function get_mime_type(){ _trigger_error(); }

  /** returns the actual text to be echoed to the browser */
  function get_export() { return  $this->export; }

  /** set the export text to be sent */
  function set_export(&$contents) { $this->export =& $contents; }

  /** called when a function that should be overloaded by a subclass is not */
  function _trigger_error() {
     trigger_error('Function not overloaded. Called from '.debug_caller(), E_USER_WARNING);
  }

  /**
  * Embeds the data in replace_array into the template file pointed to by path.
  *
  * @var string path to the template file
  * @return nothing 
  * @todo           //TODO reduce this things memory footprint
  */
  function _wrapBuffer($path) {
    $fd = fopen($path, 'r');
    $contents = fread($fd, filesize ($path));
    fclose($fd);
   
    $this->replace_array['/__CONTENTS__/'] =& $this->export;

    foreach($this->replace_array as $pattern => $replacement) {

        $contents = preg_replace($pattern, $replacement, $contents);

    } //end foreach
   
    $this->export =& $contents;
  } //end _wrapBuffer


} // class XMLExportBase 

?>
