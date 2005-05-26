<?php
# $Id$
# construct a PDF from a simple HTML stream

require('fpdf/fpdf.php');

/**
 *  A PDF exporter
 *
 */

class PDFExport {
  var $html;
  var $pdf;
  
  var $orientation = 'P';
  var $size = 'A4';
  
  function PDFExport(&$buf) {
    $this->html = $buf;
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

?> 
