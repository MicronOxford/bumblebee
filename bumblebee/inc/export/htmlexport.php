<?php
# $Id$
# construct an HTML export from array representation

include_once 'inc/exportcodes.php';

/**
 *  An HTML exporter
 *
 */

class HTMLExport {
  var $export;
  var $bigtable = true;
  var $ea;
  var $header;
  
  function HTMLExport(&$exportArray) {
    $this->ea =& $exportArray;
  }

  function makeHTMLBuffer() {
    //$this->log('Making HTML representation of data');
    $ea =& $this->ea->export;
    $eol = "\n";
    $metaData = $ea['metadata'];
    $numcols = $metaData['numcols'];
    unset($ea['metadata']);
    $buf = '';
    for ($i=0; $i<count($ea); $i++) {
      if (! $this->bigtable) {
        switch ($ea[$i]['type']) {
          case EXPORT_REPORT_START:
            $buf .= '<div id="bumblebeeExport">';
            break;
          case EXPORT_REPORT_END:
            $buf .= '</div>';
            break;
          case EXPORT_REPORT_HEADER:
            $buf .= '<div class="exportHeader">'.$ea[$i]['data'].'</div>'.$eol;
            $this->header = $ea[$i]['data'];
            break;
          case EXPORT_REPORT_SECTION_HEADER:
            $buf .= '<div class="exportSectionHeader">'.$ea[$i]['data'].'</div>'.$eol;
            break;
          case EXPORT_REPORT_TABLE_START:
            $buf .= '<table class="exportdata">'.$eol;
            break;
          case EXPORT_REPORT_TABLE_END:
            $buf .= '</table>'.$eol;
            break;
          case EXPORT_REPORT_TABLE_HEADER:
            $buf .= '<tr class="header">'
                        .$this->_formatRowHTML($ea[$i]['data'], true)
                    .'</tr>'.$eol;
            break;
          case EXPORT_REPORT_TABLE_FOOTER:
            $buf .= '<tr class="footer">'
                        .$this->_formatRowHTML($ea[$i]['data'])
                    .'</tr>'.$eol;
            break;
          case EXPORT_REPORT_TABLE_ROW:
            $buf .= '<tr>'
                        .$this->_formatRowHTML($ea[$i]['data'])
                    .'</tr>'.$eol;
            break;
        }
      } else {
        switch ($ea[$i]['type']) {
          case EXPORT_REPORT_START:
            $buf .= '<div id="bumblebeeExport">';
            break;
          case EXPORT_REPORT_END:
            $buf .= '</table></div>';
            break;
          case EXPORT_REPORT_HEADER:
            $buf .= '<div class="exportHeader">'.$ea[$i]['data'].'</div>'.$eol;
            $buf .= '<table class="exportdata">'.$eol;
            break;
          case EXPORT_REPORT_SECTION_HEADER:
            $buf .= '<tr class="exportSectionHeader"><td colspan="'.$numcols.'" class="exportSectionHeader">'
                        .$ea[$i]['data'].'</td></tr>'.$eol;
            break;
          case EXPORT_REPORT_TABLE_HEADER:
            $buf .= '<tr class="header">'
                        .$this->_formatRowHTML($ea[$i]['data'], true)
                    .'</tr>'.$eol;
            break;
          case EXPORT_REPORT_TABLE_FOOTER:
            $buf .= '<tr class="footer">'
                        .$this->_formatRowHTML($ea[$i]['data'])
                    .'</tr>'.$eol;
            break;
          case EXPORT_REPORT_TABLE_ROW:
            $buf .= '<tr>'
                        .$this->_formatRowHTML($ea[$i]['data'])
                    .'</tr>'.$eol;
            break;
        }
      }
    }      
    $this->export =& $buf;
  }
  
  function _reportHeader() {
    $start = $this->_daterange->getStart();
    $stop  = $this->_daterange->getStop();
    $s = $this->_export->description .' for '. $start->datestring .' - '. $stop->datestring;
    return $s;
  }  

  function _sectionHeader($row) {
    $s = $row[$this->_export->breakField];
    return $s;
  }  
  
  function _formatRowHTML($row, $isHeader=false) {
    $b = '';
    for ($j=0; $j<count($row); $j++) {
      $b .= $this->_formatCellHTML($row[$j], $isHeader);
    }
    return $b;
  }

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
      $t .= '<td '.$align.'>'.htmlentities($val).'</td>';
    } else {
      $t .= '<th>'.htmlentities($val).'</th>';
    }
    return $t;
  }
  
  function wrapHTMLBuffer() {
    global $CONFIG;
    global $BASEPATH;
    $filename = $CONFIG['export']['htmlWrapperFile'];
    $fd = fopen($filename, 'r');
    $contents = fread($fd, filesize ($filename));
    fclose($fd); 
    $title = 'Data export';
    $table = preg_replace('/\$/', '&#'.ord('$').';', $this->export);
    $contents = preg_replace('/__TITLE__/', $title, $contents);
    $contents = preg_replace('/__BASEPATH__/', $BASEPATH, $contents);
    //return $contents;
    //preDump($contents);
    $contents = preg_replace('/__CONTENTS__/', $table, $contents);
    //encode the HTML so that it doesn't get interpreted by the browser and cause big problems
    //the PHP function rawurlencode() can be reversed by the JavaScript function unescape()
    //which is then a convenient pairing to use rather than replacing everything manually.
    $enchtml = rawurlencode($contents);
    $jsbuf = '<script type="text/javascript">
<!--
  function BBwriteAll(data) {
    bboutwin = window.open("", "bumblebeeOutput", "");
    bboutwin.document.write(unescape(data));
    bboutwin.document.close();
  }
  
  data = "'.$enchtml.'";
  
  BBwriteAll(data);
  
//-->
</script><a href="javascript:BBwriteAll(data)">Open Window</a>';
    return $jsbuf;
  }


} // class HTMLExport

?> 
