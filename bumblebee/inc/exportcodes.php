<?php
# $Id$

// these aren't designed to be bitshifted, so they don't have to be on powers of 2

define('EXPORT_FORMAT_CUSTOM', 1);
define('EXPORT_FORMAT_HTML',   2);
define('EXPORT_FORMAT_CSV',    3);
define('EXPORT_FORMAT_TAB',    4);
define('EXPORT_FORMAT_PDF',    5);


define('EXPORT_HTML_CENTRE',  64);
define('EXPORT_HTML_RIGHT',   65);
define('EXPORT_HTML_LEFT',    66);
define('EXPORT_HTML_DECIMAL', 67);

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