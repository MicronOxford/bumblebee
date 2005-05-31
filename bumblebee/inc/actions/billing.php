<?php
# $Id$
#

include_once 'inc/actions/export.php';
include_once 'inc/export/exporttypes.php';
include_once 'inc/exportcodes.php';
include_once 'inc/formslib/dblist.php';

/**
 *  Find out what sort of report is required and generate it
 *
 */

class ActionBilling extends ActionExport {


  function ActionBilling($auth, $pdata) {
    parent::ActionExport($auth, $pdata);
    //$this->format = EXPORT_FORMAT_VIEW;
    $this->format = EXPORT_FORMAT_PDF;
    $this->_verb = 'billing';
    $this->PD['what'] = 'billing';
    $this->reportSet = array('group' => 'users', 
                             'consumablegroup' => 'users',
                             'billing' => '');
  }

//   function selectExport() {
//     $reportlist = array();
//     foreach ($this->typelist->types as $type) {
//       $reportlist[$type->name] = $type->description;
//     }
//     
//     $selectRow = new nonDBRow('listselect', 'Select reports', 
//               'Select which reports you want to return');
//     $select = new CheckBoxTableList('reports', 'Select which reports to generate');
//     $hidden = new TextField('what');
//     $select->addFollowHidden($hidden);
//     $report = new CheckBox('return', 'Return');
//     $select->addCheckBox($report);
//     $select->setValuesArray($reportlist, 'id', 'iv');
//     $select->setFormat('id', '%s', array('iv'));
//     $select->addSelectAllFooter(true);
//     $selectRow->addElement($select);
//     
//     echo $selectRow->displayInTable(4);
//     echo '<input type="hidden" name="what" value="1" />';
//     echo '<input type="submit" name="submit" value="Select" />';
//   }
// 
//   
  function returnExport() {
    $this->DEBUG=10;
    $lists = array();
    $pdfs = array();
    foreach ($this->reportSet as $report => $pivot) {
      $this->PD['pivot'] = $pivot;
      $lists[] = $this->_getDataList($report);
    }
    $groups = array(1,2,3,4,5,6);
    foreach ($groups as $g) { 
      $exportArray = new ArrayExport($lists[0], '');
      $exportArray->header = $this->_reportHeader();
      $exportArray->author = $this->auth->name;
      $noData = true;
      for ($r = 0; $r < count($lists); $r++) {
        // put a restriction on what to return for this incarnation
        $lists[$r]->fill();
        $noData = $noData && ! count($lists[$r]->data);
        $this->log('Found '. count($lists[$r]->data) .' rows');
        
        if (! count($lists[$r]->data)) {
          // start rendering the data
          $lists[$r]->outputFormat = $this->format;
          $lists[$r]->omitFields = $this->_export->omitFields;          //FIXME
          $lists[$r]->formatList();   
          $this->log('Creating new AE');
          preDump($lists[$r]);
          $ea = new ArrayExport($lists[$r], $this->_export->breakField);   //FIXME
          $ea->header = $this->_reportHeader();
          $ea->makeExportArray();
          $this->log('Appending EA');
          $exportArray->appendEA($ea);
        }
      }
      //preDump($exportArray);
      $pdfExport = $this->_preparePDFExport($exportArray);
      $pdfExport->makePDFBuffer();
      $this->_getFilename();
      $pdfs[] = array('filename'  => $this->filename, 
                      'mimetype'  => $this->mimetype, 
                      'data'      => $pdfExport->export);
    }
    $this->unbuffer();
    preDump($pdfs);  
//       $this->_getFilename();
//       $this->bufferedStream =& $pdfExport->export;
      // the data itself will be dumped later by the action driver (index.php)
    if ($noData) {
      return $this->unbufferForError('<p>No data found for those criteria</p>');
    } else {
      // dump out the files via email or a zip file....
    }
  }
  
  
}

?> 
