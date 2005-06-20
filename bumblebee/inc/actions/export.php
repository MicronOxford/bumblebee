<?php
# $Id$
# return a billing summary 

include_once 'inc/formslib/checkbox.php';
include_once 'inc/formslib/checkboxtablelist.php';
include_once 'inc/formslib/datareflector.php';
include_once 'inc/actions/bufferedaction.php';
include_once 'inc/exportcodes.php';
include_once 'inc/export/exporttypes.php';
include_once 'inc/export/arrayexport.php';
include_once 'inc/export/htmlexport.php';
include_once 'inc/formslib/dblist.php';

/**
 *  Find out what sort of report is required and generate it
 *
 */

class ActionExport extends BufferedAction {
  var $fatal_sql = 1;
  var $format;
  var $typelist;
  var $_export;  // ExportType format description
  var $_daterange;
  var $_verb = 'export';

  function ActionExport($auth, $pdata) {
    parent::BufferedAction($auth, $pdata);
  }

  function go() {
    $reflector = new DataReflector();
    echo $reflector->display($this->PD);

    $this->typelist = new ExportTypeList();
//     preDump($this->typelist);
    if (! isset($this->PD['what'])) {
      $this->unbuffer();
      $this->selectExport();
    } else {
      $allDataOK = true;
      $this->_daterange = new DateRange('daterange', 'Select date range', 
                      'Enter the dates over which you want to export data');
      $this->_daterange->update($this->PD);
      $this->_daterange->checkValid();
      $this->_daterange->reflectData = 0;
      $this->_daterange->includeSubmitButton = 0;
      if ($this->_daterange->newObject || !$this->_daterange->isValid) {
        $allDataOK = false;
        $this->unbuffer();
        $this->_daterange->setDefaults(DR_PREVIOUS, DR_QUARTER);
        echo $this->_daterange->display($this->PD);
      } 
      if (! isset($this->format)) {
        $allDataOK = false;
        $this->unbuffer();
        $this->formatSelect();
      }
      if (! isset($this->PD['limitationselected'])) {
        $allDataOK = false;
        $this->unbuffer();
        $this->outputSelect();
      }
      if ($allDataOK) {
        echo $this->reportAction($this->returnExport(),
              array(STATUS_ERR =>  'Error exporting data: '.$this->errorMessage
                   )
             );
      } else {
        $this->_goButton();
      }
    }
  }
  
  function mungePathData() {
    $this->PD = array();
    foreach ($_POST as $k => $v) {
      $this->PD[$k] = $v;
    }
    if (isset($this->PDATA[1]) && ! empty($this->PDATA[1])) {
      $this->PD['what'] = $this->PDATA[1];
    }
    if (isset($this->PD['outputformat'])) {
      $this->format = $this->PD['outputformat'];
    }
    echoData($this->PD, 0);
  }

  function selectExport() {
    global $BASEURL;
    $reportlist = array();
    foreach ($this->typelist->types as $type) {
      $reportlist[$type->name] = $type->description;
    }
    $select = new AnchorTableList('datasource', 'Select which data to export', 1);
    $select->setValuesArray($reportlist, 'id', 'iv');
    $select->hrefbase = $BASEURL.'/'.$this->_verb.'/';
    $select->setFormat('id', '%s', array('iv'));
    echo $select->display();
  }

  function formatSelect() {
    global $CONFIG;
    $formatlist = array(EXPORT_FORMAT_VIEW     => 'View in web browser', 
                        EXPORT_FORMAT_VIEWOPEN => 'View in web browser (new window)', 
                        EXPORT_FORMAT_CSV      => 'Save as comma separated variable (csv)', 
                        EXPORT_FORMAT_TAB      => 'Save as tab separated variable (txt)');
    if ($CONFIG['export']['enablePDF']) {
      $formatlist[EXPORT_FORMAT_PDF] = 'Save as pdf report';
    }
    $select = new RadioList('outputformat', 'Select which data to export', 1);
    $select->setValuesArray($formatlist, 'id', 'iv');
    $select->setFormat('id', '%s', array('iv'));
    if (is_numeric($CONFIG['export']['defaultFormat'])) {
      $select->setDefault($CONFIG['export']['defaultFormat']);
    } else {
      $select->setDefault(exportStringToCode($CONFIG['export']['defaultFormat']));
    }
    echo '<div style="margin: 2em 0 2em 0;">'.$select->display().'</div>';
  }  
  
  
  function outputSelect() {
    $export = $this->typelist->types[$this->PD['what']];
    for ($lim = 0; $lim < count($export->limitation); $lim++) {
      $select = new CheckBoxTableList('limitation-'.$lim, 'Select which detail to view');
      $hidden = new TextField($export->limitation[$lim]);
      $select->addFollowHidden($hidden);
      $chosen = new CheckBox('selected', 'Selected');
      $select->addCheckBox($chosen);
      //$select->numSpareCols = 1;
      if ($export->limitation[$lim] == 'users') {
        $select->connectDB($export->limitation[$lim], array('id', 'name', 'username'));
        $select->setFormat('id', '%s', array('name'), " (%s)", array('username'));
      } else {
        $select->connectDB($export->limitation[$lim], array('id', 'name', 'longname'));
        $select->setFormat('id', '%s', array('name'), " %50.50s", array('longname'));
      }
      $select->addSelectAllFooter(true);
      echo $select->display().'<br/>';
    }
    if (is_array($export->pivot) && count($export->pivot) > 1) {
      $views = array();
      foreach ($export->pivot as $k => $v) {
        $views[$k] = $v['description'];
      }
      $viewselect = new RadioList('pivot', 'Select which data view to export', 1);
      $viewselect->setValuesArray($views, 'id', 'iv');
      $viewselect->setFormat('id', '%s', array('iv'));
      reset($views);
      $viewselect->setDefault(key($views));
      echo '<div style="margin: 0em 0 2em 0;">'.$viewselect->display().'</div>';
    }
    echo '<input type="hidden" name="limitationselected" value="1" />';
  }

  function _goButton() {
    echo '<input type="submit" name="submit" value="Select" />';
  }
      
    
    
  function returnExport() {
    $list = $this->_getDataList($this->PD['what']);
    $list->fill();
    if (count($list->data) == 0) {
      return $this->unbufferForError('<p>No data found for those criteria</p>');
    }      
    // start rendering the data
    $list->outputFormat = $this->format;
    $list->formatList();   
    if ($this->format & EXPORT_FORMAT_USEARRAY) {
      $exportArray = new ArrayExport($list, $list->breakfield);
      $exportArray->header = $this->_reportHeader();
      $exportArray->author = $this->auth->name;
      $exportArray->makeExportArray();
      //preDump($exportArray->export);
    }
    if ($this->format & EXPORT_FORMAT_USEHTML) {
      $htmlExport = new HTMLExport($exportArray);
      $htmlExport->makeHTMLBuffer();
    }
    
    //finally, direct the data towards its output
    if ($this->format == EXPORT_FORMAT_PDF){ 
      // construct the PDF from $htmlbuffer
      $pdfExport = $this->_preparePDFExport($exportArray);
      $pdfExport->makePDFBuffer();
      
      if ($pdfExport->writeToFile) {
        $this->unbuffer();
      } else {
        $this->_getFilename();
        $this->bufferedStream =& $pdfExport->export;
        // the data itself will be dumped later by the action driver (index.php)
      }
    } elseif ($this->format & EXPORT_FORMAT_DELIMITED) {
      $this->_getFilename();
      $this->bufferedStream = '"'.$this->_reportHeader().'"'."\n"
                               .$list->outputHeader()."\n"
                               .join($list->formatdata, "\n");
      // the data itself will be dumped later by the action driver (index.php)
    } elseif ($this->format == EXPORT_FORMAT_VIEWOPEN) {
      $this->unbuffer();
      echo $htmlExport->wrapHTMLBuffer();
    } else {
      $this->unbuffer();
      echo $htmlExport->export;
    }
  }
  
  function _getDataList($report) {
    $this->_export = $this->typelist->types[$report];
    $start = $this->_daterange->getStart();
    $stop  = $this->_daterange->getStop();
    $stop->addDays(1);

    if (is_array($this->_export->union) && count($this->_export->union)) {
      $union = array();
      $limitsOffset = 0;
      foreach ($this->_export->union as $export) {
        $union[] = $this->_getDBListFromExport($export, $start, $stop, $limitsOffset);
        $limitsOffset += count($export->limitation);
      }
      $list = $this->_getDBListFromExport($this->_export, $start, $stop);
      $list->union = $union;
    } else {
      $list = $this->_getDBListFromExport($this->_export, $start, $stop);
    }
    return $list;
  }
  
  function _getDBListFromExport(&$export, $start, $stop, $limitsOffset=0) {
    $where = $export->where;
    $where[] = $export->timewhere[0].qw($start->datetimestring);
    $where[] = $export->timewhere[1].qw($stop->datetimestring);
    $where = array_merge($where, $this->_limitationSet($export->limitation, $limitsOffset));
    // work out what view/pivot of the data we want to see
    if (count($export->limitation) > 1 && is_array($export->pivot)) {
      $pivot = $export->pivot[$this->PD['pivot']];
      $export->group      = $pivot['group'];
      $export->omitFields = array_flip($pivot['omitFields']);
      $export->breakField = $pivot['breakField'];
      if (isset($pivot['fieldOrder']) && is_array($pivot['fieldOrder'])) {
        $export->fieldOrder = $pivot['fieldOrder'];
      }
      if (isset($pivot['extraFields']) && is_array($pivot['extraFields'])) {
        $export->fields = array_merge($export->fields, $pivot['extraFields']);
      }
    }
    $list = new DBList($export->basetable, $export->fields, join($where, ' AND '));
    $list->join        = array_merge($list->join, $export->join);
    $list->group       = $export->group;
    $list->manualGroup = $export->manualGroup;
    $list->manualSum   = $export->manualSum;
    $list->order       = $export->order;
    $list->distinct    = $export->distinct;
    $list->fieldOrder  = $export->fieldOrder;
    $list->breakfield  = $export->breakField;
    if ($this->format & EXPORT_FORMAT_USEARRAY) {
      $list->omitFields = $export->omitFields;
    }
    return $list;
  }

  function _limitationSet($fields, $limitsOffset, $makeSQL=true) {
    $sets = array();
    for ($lim = 0; $lim < count($fields); $lim++) {
      $limitation = array();
      $fieldpattern = '/^limitation\-(\d+)\-(\d+)\-'.$fields[$lim].'$/';
      $selected = array_values(preg_grep($fieldpattern, array_keys($this->PD)));
      #preDump($selected);
      for ($j=0; $j < count($selected); $j++) {
        $ids = array();
        preg_match($fieldpattern, $selected[$j], $ids);
        $item = issetSet($this->PD,$selected[$j]);
        if (issetSet($this->PD,'limitation-'.$ids[1].'-'.$ids[2].'-selected') && $item !== NULL) {
          $limitation[] = /*$export->limitation[$lim].'.id='.*/qw($item);
        }
        //echo $namebase.':'.$j.':'.$lim.':'.$fields[$lim].':'.$item.'<br/>';
      }
      if (count($limitation)) {
        if ($makeSQL) {
          $sets[] = $fields[$lim].'.id IN ('.join($limitation, ', ').')';
        } else {
          $sets[$fields[$lim]] = $limitation;
        }
      }
      //preDump($limitation);
    }
    return $sets;
  }
  
  /**
   *    this function is no longer used
   */
  function _limitationSetRIGID($fields, $limitsOffset, $makeSQL=true) {
    $sets = array();
    for ($lim = 0; $lim < count($fields); $lim++) {
      $limitation = array();
      $namebase = 'limitation-'.($limitsOffset+$lim).'-';
      for ($j=0; isset($this->PD[$namebase.$j.'-row']); $j++) {
        $item = issetSet($this->PD,$namebase.$j.'-'.$fields[$lim]);
        if (issetSet($this->PD,$namebase.$j.'-selected') && $item !== NULL) {
          $limitation[] = /*$export->limitation[$lim].'.id='.*/qw($item);
        }
        echo $namebase.':'.$j.':'.$lim.':'.$fields[$lim].':'.$item.'<br/>';
      }
      if (count($limitation)) {
        if ($makeSQL) {
          $sets[] = $fields[$lim].'.id IN ('.join($limitation, ', ').')';
        } else {
          $sets[$fields[$lim]] = $limitation;
        }
      }
      //preDump($limitation);
    }
    return $sets;
  }
  
  function _getFilename() {
    switch ($this->format & EXPORT_FORMAT_MASK) {
      case EXPORT_FORMAT_CSV:
        $ext = 'csv';
        $type = 'text/csv';
        break;
      case EXPORT_FORMAT_TAB:
        $ext = 'txt';
        $type = 'text/tab-separated-values';
        //$type = 'application/vnd-excel';
        break;
      case EXPORT_FORMAT_PDF:
        $ext = 'pdf';
        $type = 'application/pdf';
        break;
      default:
        $ext = '';
        $type = 'application/octet-stream';
    }
    $this->filename = parent::getFilename('export', $this->PD['what'], $ext);
    $this->mimetype = $type;
  }
  
  function _preparePDFExport(&$exportArray) {
    require_once('inc/export/pdfexport.php');
    $pdf = new PDFExport($exportArray);
    return $pdf;
  }

  function _reportHeader() {
    $start = $this->_daterange->getStart();
    $stop  = $this->_daterange->getStop();
    $s = $this->_export->description .' for '. $start->datestring .' - '. $stop->datestring;
    return $s;
  }  

}  //ActionExport
?> 
