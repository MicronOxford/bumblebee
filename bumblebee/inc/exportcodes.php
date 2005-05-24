<?php
# $Id$

// these aren't designed to be bitshifted, so they don't have to be on powers of 2

define('EXPORT_FORMAT_CUSTOM', 1);
define('EXPORT_FORMAT_HTML',   2);
define('EXPORT_FORMAT_CSV',    3);
define('EXPORT_FORMAT_TAB',    4);
define('EXPORT_FORMAT_PDF',    5);


// these are designed to be bitshifted around
define('EXPORT_HTML_CENTRE',     1);
define('EXPORT_HTML_RIGHT',      2);
define('EXPORT_HTML_LEFT',       4);
define('EXPORT_HTML_ALIGN',      EXPORT_HTML_CENTRE|EXPORT_HTML_RIGHT|EXPORT_HTML_LEFT);


define('EXPORT_HTML_MONEY',     32);
define('EXPORT_HTML_DECIMAL',   64);
define('EXPORT_HTML_DECIMAL_1', EXPORT_HTML_DECIMAL|128);  // round to 1 sig figs
define('EXPORT_HTML_DECIMAL_2', EXPORT_HTML_DECIMAL|256);  // round to 2 sig figs
define('EXPORT_HTML_NUMBER',    EXPORT_HTML_MONEY|EXPORT_HTML_DECIMAL_1|EXPORT_HTML_DECIMAL_2);




function exportStringToCode($s) {
  switch ($s) {
    case 'EXPORT_FORMAT_CUSTOM':
      return EXPORT_FORMAT_CUSTOM;
    case 'EXPORT_FORMAT_HTML':
      return EXPORT_FORMAT_HTML;
    case 'EXPORT_FORMAT_CSV':
      return EXPORT_FORMAT_CSV;
    case 'EXPORT_FORMAT_TAB':
      return EXPORT_FORMAT_TAB;
    case 'EXPORT_FORMAT_PDF':
      return EXPORT_FORMAT_PDF;
  }
}

?>