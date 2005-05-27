<?php
# $Id$

// these aren't designed to be bitshifted, so they don't have to be on powers of 2

define('EXPORT_FORMAT_CUSTOM',         1);
define('EXPORT_FORMAT_DELIMITED',      2);
define('EXPORT_FORMAT_CSV',            EXPORT_FORMAT_DELIMITED|4);
define('EXPORT_FORMAT_TAB',            EXPORT_FORMAT_DELIMITED|8);
define('EXPORT_FORMAT_DELIMITED_MASK', EXPORT_FORMAT_CSV|EXPORT_FORMAT_TAB);

define('EXPORT_FORMAT_USEARRAY',  64);
define('EXPORT_FORMAT_USEHTML',   128);
define('EXPORT_FORMAT_VIEWOPEN',  EXPORT_FORMAT_USEARRAY|EXPORT_FORMAT_USEHTML|256);
define('EXPORT_FORMAT_VIEW',      EXPORT_FORMAT_USEARRAY|EXPORT_FORMAT_USEHTML|512);
define('EXPORT_FORMAT_PDF',       EXPORT_FORMAT_USEARRAY|1024);
define('EXPORT_FORMAT_USEARRAY_MASK', EXPORT_FORMAT_VIEWOPEN|EXPORT_FORMAT_PDF|EXPORT_FORMAT_VIEW);
define('EXPORT_FORMAT_USEHTML_MASK',  EXPORT_FORMAT_VIEWOPEN|EXPORT_FORMAT_VIEW);

define('EXPORT_FORMAT_MASK',      EXPORT_FORMAT_USEARRAY_MASK|EXPORT_FORMAT_DELIMITED_MASK
                                            |EXPORT_FORMAT_CUSTOM);

// tree structure ofr HTML and PDF export
define('EXPORT_REPORT_START',              1);
define('EXPORT_REPORT_END',                2);
define('EXPORT_REPORT_HEADER',             3);
define('EXPORT_REPORT_SECTION_HEADER',     4);
define('EXPORT_REPORT_TABLE_START',        5);
define('EXPORT_REPORT_TABLE_HEADER',       6);
define('EXPORT_REPORT_TABLE_ROW',          7);
define('EXPORT_REPORT_TABLE_FOOTER',       8);
define('EXPORT_REPORT_TABLE_END',          9);
  
                                          
                                            
// these are designed to be bitshifted around
define('EXPORT_HTML_ALIGN',      1);
define('EXPORT_HTML_CENTRE',     EXPORT_HTML_ALIGN|2);
define('EXPORT_HTML_RIGHT',      EXPORT_HTML_ALIGN|4);
define('EXPORT_HTML_LEFT',       EXPORT_HTML_ALIGN|8);
define('EXPORT_HTML_ALIGN_MASK', EXPORT_HTML_CENTRE|EXPORT_HTML_RIGHT|EXPORT_HTML_LEFT);


define('EXPORT_HTML_NUMBER',       32);
define('EXPORT_HTML_MONEY',        EXPORT_HTML_NUMBER|64);
define('EXPORT_HTML_DECIMAL_1',    EXPORT_HTML_NUMBER|128);  // round to 1 sig figs
define('EXPORT_HTML_DECIMAL_2',    EXPORT_HTML_NUMBER|256);  // round to 2 sig figs
define('EXPORT_HTML_DECIMAL_MASK', EXPORT_HTML_DECIMAL_1|EXPORT_HTML_DECIMAL_2);
define('EXPORT_HTML_NUMBER_MASK',  EXPORT_HTML_MONEY|EXPORT_HTML_DECIMAL_MASK);




function exportStringToCode($s) {
  switch ($s) {
    case 'EXPORT_FORMAT_CUSTOM':
      return EXPORT_FORMAT_CUSTOM;
    case 'EXPORT_FORMAT_VIEW':
      return EXPORT_FORMAT_VIEW;
    case 'EXPORT_FORMAT_VIEWOPEN':
      return EXPORT_FORMAT_VIEWOPEN;
    case 'EXPORT_FORMAT_CSV':
      return EXPORT_FORMAT_CSV;
    case 'EXPORT_FORMAT_TAB':
      return EXPORT_FORMAT_TAB;
    case 'EXPORT_FORMAT_PDF':
      return EXPORT_FORMAT_PDF;
  }
}

?>