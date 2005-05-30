<?php
# $Id$
# construct a PDF from the array representation

include_once 'inc/exportcodes.php';
require_once 'fpdf/fpdf.php';


/**
 *  A PDF exporter
 *
 */

class PDFExport {
  var $ea;
  var $pdf;
  var $export;
  
  var $orientation = 'L';
  var $size = 'A4';
  var $pageWidth   = 297;   // mm
  var $pageHeight  = 210;   // mm
  var $leftMargin  = 15;    // mm
  var $rightMargin = 15;    // mm
  var $topMargin   = 15;    // mm
  var $bottomMargin= 15;    // mm
  
  var $minAutoMargin = 4;   // mm added to auto calc'd column widths
  var $tableHeaderAlignment = 'L';
  var $rowLines = '';  // use 'T' for lines between rows
  var $headerLines = 'TB';    // Top and Bottom lines on the header rows
  
  var $cols = array(); //=array(50,20,20,20,20,20,20,20);   //column widths
  var $colStart = array();
  var $tableRow = 0;
  
  function PDFExport(&$exportArray) {
    $this->ea = $exportArray;
  }
  
  function makePDFBuffer() {
    $this->render();
    $this->export =& $this->Output();
  }
  
  function render() {
    $this->pdf = new TabularPDF($this->orientation, 'mm', $this->size);
    $this->_setMetaInfo();
    $this->_setPageMargins();
    $this->pdf->AliasNbPages();

    $this->_parseArray();
#    $this->pdf->AddPage();
 #   $this->pdf->Cell(40,10,'Hello World!');
  }

  function Output() {
    //return $this->pdf->Output('/tmp/bbtest.pdf', 'F');
    return $this->pdf->Output('', 'S');
  } 

  function _setMetaInfo() {
    $metaData = $this->ea->export['metadata'];
    $this->pdf->SetCreator($metaData['creator']);
    $this->pdf->SetAuthor($metaData['author']);
    $this->pdf->SetKeywords($metaData['keywords']);
    $this->pdf->SetSubject($metaData['subject']);
    $this->pdf->SetTitle($metaData['title']);
    $this->pdf->title = $metaData['title'];
    
    $this->_calcColWidths($metaData['colwidths']);
    $this->pdf->cols = $this->cols;
    $this->pdf->colStart = $this->colStart;
  }
  
  function _setPageMargins() {
    $this->pdf->SetMargins($this->leftMargin, $this->topMargin, $this->rightMargin);
    $this->pdf->SetAutoPageBreak(true, $this->bottomMargin);
  }
  
  function _calcColWidths($widths) {
    //preDump($widths);
    $sum = 0;
    $this->cols = array();
    for ($col = 0; $col<count($widths); $col++) {
      if ($widths[$col] == '*') {
        $this->cols[$col] = $this->_getColWidth($col);
      } else {
        $sum += $widths[$col];
      }
    }
    $taken = array_sum($this->cols);
    //if ($taken > ($this->pageWidth-$this->leftMargin-$this->rightMargin)) {
      //FIXME: must not go over page right
    for ($col = 0; $col<count($widths); $col++) {
      if (! isset($this->cols[$col])) {
        $this->cols[$col] = $widths[$col] 
                  / $sum*($this->pageWidth-$this->leftMargin-$this->rightMargin-$taken);
      }
    }
  }
  
  function _getColWidth($col) {
    //why are we doing lots of calls into the pdf here? is it bad encapsulation?
    //We have to have a font chosen within FPDF to perform these length calculations
    $this->pdf->_setTableFont();
    $ea =& $this->ea->export;
    $i=0;
    $width = 0;
    for ($key=1; $key<count($ea)-1; $key++) {
      $newWidth = $this->pdf->GetStringWidth($ea[$key]['data'][$col]['value']);
      if ($ea[$key]['type'] == EXPORT_REPORT_TABLE_HEADER)
        $newWidth *= 1.1;     //FIXME: we should do this calculation properly!
      //echo "VAL=".$ea[$key]['data'][$col]['value'].", WIDTH=$newWidth/$width.<br/>";
      $width = max($width, $newWidth);
    }
    //echo "WIDTH=$width.<br/>";
    return $width + $this->minAutoMargin;
  }

  function _getColWidthRand($col) {
    //FIXME: is this random thing good enough? what about fitting in the header?
    //why are we doing lots of calls into the pdf here? is it bad encapsulation?
    //We have to have a font chosen within FPDF to perform these length calculations
    $this->pdf->_setTableFont();
    $ea =& $this->ea->export;
    $i=0;
    $width = 0; $header = 0;
    while ($rows<10 || $header<1) {
      $key = array_rand($ea, 1);
      //echo $key;
      if (is_numeric($key) && $ea[$key]['type'] == EXPORT_REPORT_TABLE_ROW) {
        $rows++;
        $newWidth = $this->pdf->GetStringWidth($ea[$key]['data'][$col]['value']);
        //echo "VAL=".$ea[$key]['data'][$col]['value'].", WIDTH=$newWidth/$width.<br/>";
        $width = max($width, $newWidth);
     } elseif (is_numeric($key) && $ea[$key]['type'] == EXPORT_REPORT_TABLE_HEADER) {
        $header++;
        $newWidth = 1.1*$this->pdf->GetStringWidth($ea[$key]['data'][$col]['value']);
        //echo "VAL=".$ea[$key]['data'][$col]['value'].", WIDTH=$newWidth/$width.<br/>";
        $width = max($width, $newWidth);
     }
    }
    //echo "WIDTH=$width.<br/>";
    return $width + $this->minAutoMargin;
  }
  
  function _parseArray() {
    //$this->log('Making HTML representation of data');
    $ea =& $this->ea->export;
    $eol = "\n";
    $metaData = $ea['metadata'];
    $numcols = $metaData['numcols'];
    unset($ea['metadata']);
    $buf = '';
    for ($i=0; $i<count($ea); $i++) {
      switch ($ea[$i]['type']) {
        case EXPORT_REPORT_START:
          $this->pdf->reportStart();
          break;
        case EXPORT_REPORT_END:
          $this->pdf->reportEnd();
          break;
        case EXPORT_REPORT_HEADER:
          $this->pdf->reportHeader($ea[$i]['data']);
          break;
        case EXPORT_REPORT_SECTION_HEADER:
          $this->pdf->sectionHeader($ea[$i]['data']);
          break;
        case EXPORT_REPORT_TABLE_START:
          $this->tableRow=0;
          $this->pdf->tableStart();
          break;
        case EXPORT_REPORT_TABLE_END:
          $this->pdf->tableEnd();
          break;
        case EXPORT_REPORT_TABLE_HEADER:
          $this->pdf->tableHeader($this->_formatRow($ea[$i]['data'], true));
          break;
        case EXPORT_REPORT_TABLE_TOTAL:
          $this->pdf->tableTotal($this->_formatRow($ea[$i]['data'], false, 'TT'));
          break;
        case EXPORT_REPORT_TABLE_FOOTER:
          $this->pdf->tableFooter($this->_formatRow($ea[$i]['data']));
          break;
        case EXPORT_REPORT_TABLE_ROW:
          $this->pdf->tableRow($this->_formatRow($ea[$i]['data']));
          $this->tableRow++;
          break;
      }
    }      
  }
   
  function _formatRow($row, $isHeader=false, $border=NULL) {
    $rowpdf = array();
    for ($j=0; $j<count($row); $j++) {
      $rowpdf[] = $this->_formatCell($row[$j], $j, $isHeader, $border);
    }
    return $rowpdf;
  }

  function _formatCell($d, $col, $isHeader, $setborder) {
    $val = $d['value'];
    if (! $isHeader) {
      switch($d['format'] & EXPORT_HTML_ALIGN_MASK) {
        case EXPORT_HTML_LEFT:
          $align='L';
          break;
        case EXPORT_HTML_RIGHT:
          $align='R';
          break;
        case EXPORT_HTML_CENTRE:
        default:
          $align='C';
      }
      $fill = $this->tableRow % 2;
      $border = $this->rowLines;
    } else {
      $align = $this->tableHeaderAlignment;
      $fill = 1;
      $border = $this->headerLines;
    }
    if (isset($setborder)) {
      $border = $setborder;
    }
    return array('align'=>$align, 'value'=>$val, 'fill'=>$fill, 'border'=>$border);
  }

}  //  class PDFExport


class BrandedPDF extends FPDF {
  var $title = 'BumbleBee Report';
  
  function PDF($orientation, $measure, $format) {
    parent::FPDF($orientation, $measure, $format);
  }
    
  function Header() {
    //Logo
    $this->Image('theme/export/logo.png',10,8,33);
    //Arial bold 15
    $this->SetFont('Arial','B',15);
    //Move to the right
    $this->Cell(40);
    //Title
    $this->Cell(200,30,$this->title,0,0,'C');
    //Line break
    $this->Ln(30);
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
  
} // class BrandedPDF


class TabularPDF extends BrandedPDF {

  // Table-managing code adapted from Olivier's FPDF examples:
  // http://www.fpdf.org/en/script/script3.php
  
  var $_last_sectionHeader;
  var $_last_tableHeader;
  var $_preventNewPage;
  
  var $continuedHeader = ' (continued)';
  
  var $lineHeight;
  var $normalLineHeight = 5;
  var $headerLineHeight = 6;
  var $footerLineHeight = 4;
  var $doubleLineWidth  = 0.2;
  var $singleLineWidth  = 0.3;
  var $sectionHeaderLineHeight = 8;
  var $cellTopMargin;
  var $singleCellTopMargin    = 1;
  
  function PDF($orientation, $measure, $format) {
    parent::FPDF($orientation, $measure, $format);
  }
    

  function _setTableFont() {
    $this->SetFont('Arial','',12);
    $this->SetDrawColor(0,0,0);
    $this->SetLineWidth($this->singleLineWidth);
    $this->SetFillColor(224,235,255);
    $this->SetTextColor(0);
    $this->lineHeight = $this->normalLineHeight;
    $this->cellTopMargin = $this->singleCellTopMargin;
  }
  
  function _setSectionHeaderFont() {
    $this->SetFillColor(255,255,255);
    $this->SetTextColor(0);
    $this->SetFont('','B', 14);
  }
  
  function _setTableHeaderFont() {
    $this->SetFillColor(0,0,128);
    $this->SetTextColor(255, 255, 255);
    $this->SetFont('','B');
    $this->lineHeight = $this->headerLineHeight;
  }
  
  function _setTableFooterFont() {
    $this->SetFillColor(255,255,255);
    $this->SetTextColor(0, 0, 0);
    $this->SetFont('','B', '9');
    $this->lineHeight = $this->footerLineHeight;
  }
  
  function _setTableTotalFont() {
    $this->SetTextColor(0, 0, 0);
    $this->SetLineWidth($this->doubleLineWidth);
    $this->lineHeight = $this->normalLineHeight; //+ 4*$this->doubleLineWidth;
    $this->cellTopMargin = $this->singleCellTopMargin + 4*$this->doubleLineWidth;
  }
  
  function sectionHeader($header, $skipAddPage=false) {
    if (!$skipAddPage) {
      $this->AddPage();
      $this->_last_sectionHeader = $header;
    }
    //Colors, line width and bold font
    $this->_setSectionHeaderFont();
    $this->_row(array(array('value'=>$header,'border'=>'','align'=>'L', 'fill'=>true)));
    $this->_setTableFont();
    $this->lineHeight = $this->sectionHeaderLineHeight;
}  
  
  function repeatSectionHeader() {
    $this->sectionHeader($this->_last_sectionHeader.$this->continuedHeader, true);
  }
  
  function repeatTableHeader() {
    $this->tableHeader($this->_last_tableHeader);
  }
  
  function reportStart() {
  }
  
  function reportHeader() {
  }
  
  function reportEnd() {
  }
  
  function tableStart() {
  }
  
  function tableHeader($data) {
    $this->_setTableHeaderFont();
    $this->_row($data);
    $this->_last_tableHeader = $data;
    $this->_setTableFont();
  }
  
  function tableFooter($data) {
    $this->_setTableFooterFont();
    $this->_row($data);
    $this->_setTableFont();
  }
  
  function tableTotal($data) {
    $this->_setTableTotalFont();
    $this->_row($data);
    $this->_setTableFont();
  }
  
  function tableEnd() {
    $this->_preventNewPage = true;
    $currHeight = $this->lineHeight;
    $this->lineHeight = 0.1;
    $this->_row(array(array('value'=>'','border'=>'T','fill'=>false)));
    $this->lineHeight = $currHeight;
    $this->_preventNewPage = false;
  } 
  
  function tableRow($data) {
    $this->_row($data);
  } 

  function _row($data) {
    $widths = array();
    if (count($data) == 1) {
      $span = 1;
      $widths[0] = array_sum($this->cols);
    } else {
      $span = 0;
      $widths = $this->cols;
    }
    //Calculate the height of the row
    $nb=0;
    for($i=0; $i<count($data); $i++)
        $nb=max($nb, $this->NbLines($widths[$i], $data[$i]['value']));
    $rowHeight = $this->lineHeight*$nb + $this->cellTopMargin; 
    //Issue a page break first if needed
    $this->CheckPageBreak($rowHeight);
    $y0bg  = $this->GetY();
    $y0txt = $y0bg + $this->cellTopMargin;
    //Draw the cells of the row
    $this->SetY($y0txt);
    for($i=0; $i<count($data); $i++) {
      $align=isset($data[$i]['align']) ? $data[$i]['align'] : 'L';
      //Save the current position
      $x = $this->GetX();
      //$y = $this->GetY();
      //Draw the background of the cell if appropriate
      if ($data[$i]['fill'])
        $this->Rect($x, $y0bg, $widths[$i], $rowHeight+$this->cellTopMargin, 'F');
      //Draw the borders requested
      if ($data[$i]['border']) {
        if (strpos($data[$i]['border'], 'B') !== false) {
          //echo 'B';
          $this->line($x,             $y0txt+$rowHeight, $x+$widths[$i], $y0txt+$rowHeight);
        }
        if (strpos($data[$i]['border'], 'T') !== false) {
          //echo 'T';
          $this->line($x,             $y0bg,            $x+$widths[$i], $y0bg);
        }
        if (strpos($data[$i]['border'], 'TT') !== false) {
          //double line on the top of the cell
          $dy=$this->doubleLineWidth*3; //mm
          $this->line($x,             $y0bg+$dy,        $x+$widths[$i], $y0bg+$dy);
        }
        if (strpos($data[$i]['border'], 'L') !== false) {
          //echo 'L';
          $this->line($x,             $y0bg,            $x,             $y0txt+$rowHeight);
        }
        if (strpos($data[$i]['border'], 'R') !== false) {
          //echo 'R';
          $this->line($x+$widths[$i], $y0bg,            $x+$widths[$i], $y0txt+$rowHeight);
        }
      }
      //Print the text
      $this->MultiCell($widths[$i], $this->lineHeight, $data[$i]['value'], '', $align, 0);
      //Put the position to the right of the cell
      $this->SetXY($x+$widths[$i],$y0txt);
    }
    //Go to the next line
    $this->Ln($rowHeight);
  }

  function CheckPageBreak($h) {
    //If the height h would cause an overflow, add a new page immediately
    if(! $this->_preventNewPage && $this->GetY()+$h>$this->PageBreakTrigger) {
      $this->tableEnd();
      $this->AddPage($this->CurOrientation);
      $this->repeatSectionHeader();
      $this->repeatTableHeader();
    }
  }

  function NbLines($w,$txt) {
    //Computes the number of lines a MultiCell of width w will take
    $cw=&$this->CurrentFont['cw'];
    if($w==0)
        $w=$this->w-$this->rMargin-$this->x;
    $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
    $s=str_replace("\r",'',$txt);
    $nb=strlen($s);
    if($nb>0 and $s[$nb-1]=="\n")
        $nb--;
    $sep=-1;
    $i=0;
    $j=0;
    $l=0;
    $nl=1;
    while($i<$nb)  {
        $c=$s[$i];
        if($c=="\n") {
            $i++;
            $sep=-1;
            $j=$i;
            $l=0;
            $nl++;
            continue;
        }
        if($c==' ')
            $sep=$i;
        $l+=$cw[$c];
        if($l>$wmax) {
            if($sep==-1)
            {
                if($i==$j)
                    $i++;
            }
            else
                $i=$sep+1;
            $sep=-1;
            $j=$i;
            $l=0;
            $nl++;
        }
        else
            $i++;
    }
    return $nl;
  }
  
        
} // class TabularPDF

/*  function _row($d) {
    //error_log("ROW");
    //exit;
    //preDump($d);
    for ($c=0; $c<count($d); $c++) {
      //preDump($d[$c]);
      $this->TableCell($d[$c]['value'], $c, $d[$c]['border'], $d[$c]['align'], $d[$c]['fill']);
    }
    $this->NewRow();
  }*/
  
/* function TableCell($value, $column=0, $border='', $align='C', $fill=0) {
    if ($column != -1) {
      $width = $this->cols[$column];
    } else {
      $width = array_sum($this->cols);
    }
    $height = 6;
    $y0 = $this->GetY();
    $this->MultiCell($width, $height, $value, $border, $align, $fill);
    //$this->pdf->Cell($width,$height,$value,$border,0,$align,$fill);
    $this->rowHeight = max($this->rowHeight, $this->GetY()-$y0);
    $this->SetY($y0);
    $this->SetX($this->colStart[$column+1]);
  }
  
  function NewRow($h='') {
    $this->Ln($this->rowHeight);
    $this->rowHeight = 0;
  }
*//*
  function _rowOLD($data) {
    for ($i=0; $i<count($data); $i++) {
      $this->aligns[] = $data[$i]['align'];
      $d[] = $data[$i]['value'];
    }
    $this->widths = $this->cols;
    $this->Row($d);
  }
  */
?> 
