<?php
# $Id$
# construct a PDF from the array representation

include_once 'inc/exportcodes.php';


/**
 *  A PDF exporter
 *
 */

class PDFExport {
  var $ea;
  var $pdf;
  var $export;
  
  var $orientation = 'P';
  var $size = 'A4';
  
  function PDFExport(&$buf) {
    $this->ea = $buf;
  }
  
  function makePDFExport() {
    $this->render;
    $this->export =& $this->Output();
  }
  
  function render() {
    $this->pdf = new PDF($this->orientation, 'mm', $this->size);
    $this->pdf->AliasNbPages();

    $this->pdf->AddPage();
    $this->pdf->SetFont('Arial','B',16);
    $this->pdf->Cell(40,10,'Hello World!');
  }

  function Output() {
    return $this->pdf->Output('/tmp/bbtest.pdf', 'F');
    //return $this->pdf->Output('', 'S');
  } 

}  //  class PDFExport


class PDF extends FPDF {
  function PDF($orientation, $measure, $format) {
    parent::FPDF($orientation, $measure, $format);
  }
    
  function Header() {
      //Logo
      $this->Image('theme/images/pfpc.png',10,8,33);
      //Arial bold 15
      $this->SetFont('Arial','B',15);
      //Move to the right
      $this->Cell(80);
      //Title
      $this->Cell(30,10,'Title',1,0,'C');
      //Line break
      $this->Ln(20);
  }
  
  //Page footer
  function Footer() {
      //Position at 1.5 cm from bottom
      $this->SetY(-15);
      //Arial italic 8
      $this->SetFont('Arial','I',8);
      //Page number
      $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
  }
} // class PDF


  function makeHTMLBuffer() {
    //$this->log('Making HTML representation of data');
    $ea =& $this->ea->export;
    $eol = "\n";
    $metaData = $ea['metadata'];
    $numcols = $metaData['numcols'];
    unset($ea['metadata']);
    $buf = '';
    for ($i=0; $i<count($ea); $i++) {
      if (! isset($ea[$i]['type'])) {
        echo $i.'<br/>';
      }
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
    global $CONFIG;
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
          $align='center';
          break;
        default:
          $align='';
      }
      switch ($d['format'] & EXPORT_HTML_NUMBER_MASK) {
        case EXPORT_HTML_MONEY:
          $val = sprintf($CONFIG['export']['moneyFormat'], $val);
          break;
        case EXPORT_HTML_DECIMAL_1:
          $val = sprintf('%.1f', $val);
          break;
        case EXPORT_HTML_DECIMAL_2:
          $val = sprintf('%.2f', $val);
          break;
      }
      $align = ($align!='' ? 'align='.$align : '');
      $t .= '<td '.$align.'>'.$val.'</td>';
    } else {
      $t .= '<th>'.$val.'</th>';
    }
    return $t;
  }



?> 
