<?php
# $Id$
# return a billing summary 

include_once 'inc/formslib/checkbox.php';
include_once 'inc/formslib/checkboxtablelist.php';
include_once 'inc/formslib/datareflector.php';
include_once 'inc/actions/bufferedaction.php';
include_once 'inc/bb/exporttypes.php';
include_once 'inc/exportcodes.php';
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

  function ActionExport($auth, $pdata) {
    parent::BufferedAction($auth, $pdata);
    $this->mungePathData();
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
    $select->hrefbase = $BASEURL.'/export/';
    $select->setFormat('id', '%s', array('iv'));
    echo $select->display();
  }

  function formatSelect() {
    global $CONFIG;
    $formatlist = array(EXPORT_FORMAT_HTML  => 'View in web browser', 
                        EXPORT_FORMAT_CSV   => 'Save as comma separated variable (csv)', 
                        EXPORT_FORMAT_TAB   => 'Save as tab separated variable (txt)');
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
    echo '<input type="submit" name="submit" value="Select" />';
  }

  
    
  function returnExport() {
    $list = $this->_getDataList();
    $list->fill();
    if (count($list->data) == 0) {
      return $this->unbufferForError('<p>No data found for those criteria</p>');
    }      
    // start rendering the data
    if ($this->format == EXPORT_FORMAT_HTML || $this->format == EXPORT_FORMAT_PDF) {
      $list->outputFormat = EXPORT_FORMAT_HTML;
      $list->omitFields = $this->_export->omitFields;
      $list->formatList();   
      $htmlBuffer = $this->_formatDataHTML($list);
    } else {
      $list->outputFormat = $this->format;
      $list->formatList();   
    }
    
    //finally, direct the data towards its output
    if ($this->format == EXPORT_FORMAT_PDF){ 
      // construct the PDF from $htmlbuffer
      $pdfbuffer = '';
      $this->_getFilename();
      $this->bufferedStream = $pdfbuffer;
      // the data itself will be dumped later by the action driver (index.php)
    } elseif ($this->format == EXPORT_FORMAT_CSV || $this->format == EXPORT_FORMAT_TAB) {
      $this->_getFilename();
      $this->bufferedStream = '"'.$this->_reportHeader().'"'
                               .$list->outputHeader()."\n"
                               .join($list->formatdata, "\n");
      // the data itself will be dumped later by the action driver (index.php)
    } else {
      $this->unbuffer();
      echo $htmlBuffer;
    }
  }
  
  function _getDataList() {
    $this->_export = $this->typelist->types[$this->PD['what']];
    $start = $this->_daterange->getStart();
    $stop  = $this->_daterange->getStop();
    $stop->addDays(1);
    
    $where = $this->_export->where;
    $where[] = $this->_export->timewhere[0].qw($start->datetimestring);
    $where[] = $this->_export->timewhere[1].qw($stop->datetimestring);
    for ($lim = 0; $lim < count($this->_export->limitation); $lim++) {
      $limitation = array();
      $namebase = 'limitation-'.$lim.'-';
      for ($j=0; isset($this->PD[$namebase.$j.'-row']); $j++) {
        $item = issetSet($this->PD,$namebase.$j.'-'.$this->_export->limitation[$lim]);
        if (issetSet($this->PD,$namebase.$j.'-selected')) {
          $limitation[] = $this->_export->limitation[$lim].'.id='.qw($item);
        }
      }
      $where[] = '('.join($limitation, ' OR ').')';
    }
    // work out what view/pivot of the data we want to see
    if (count($this->_export->limitation) > 1 && is_array($this->_export->pivot)) {
      $pivot = $this->_export->pivot[$this->PD['pivot']];
      $this->_export->group      = $pivot['group'];
      $this->_export->omitFields = array_flip($pivot['omitFields']);
      $this->_export->breakField = $pivot['breakField'];
    }
    $list = new DBList($this->_export->basetable, $this->_export->fields, join($where, ' AND '));
    $list->join = array_merge($list->join, $this->_export->join);
    $list->group = $this->_export->group;
    $list->order = $this->_export->order;
    $list->distinct = $this->_export->distinct;
    return $list;
  }

  function _getFilename() {
    switch ($this->format) {
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

  function _formatDataHTML($list) {
    $buf = '<div id="bumblebeeExport">';
    $buf .= '<div class="exportHeader">'.$this->_reportHeader().'</div>';
    $entry = 0;
    while ($entry < count($list->formatdata)) {
      $buf .= $this->_sectionReportHTML($list, $entry);
    }
    $buf .= '</div>';
    return $buf;
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

  function _sectionReportHTML($list, &$entry) {
    if (empty($this->_export->breakField) || ! isset($list->data[$entry][$this->_export->breakField])) {
      // then there are no fancy options for this export so it can be done very easily and quickly
      $entry = count($list->formatdata);
      return '<table class="exportdata">'
              .'<tr class="header">'.$list->outputHeader().'</tr>'."\n"
              .'<tr>'.join($list->formatdata,'</tr><tr>').'</tr>'."\n"
              .'</table>'."\n";
    }
    $buf = '<div class="exportSectionHeader">'
              .$this->_sectionHeader($list->data[$entry])
            .'</div>';
    $buf .= '<table class="exportdata">'
           .'<tr class="header">'.$list->outputHeader().'</tr>'."\n";
    $initial = $list->data[$entry][$this->_export->breakField];
    while ($entry < count($list->formatdata) 
               && $initial == $list->data[$entry][$this->_export->breakField]) {
      $buf .= '<tr>'.$list->formatdata[$entry].'</tr>'."\n";
      $entry++;
    }
    $buf .= '</table>'."\n";
    return $buf;
  }
//     $q = "SELECT "
//         ."bookings.id AS bookingid,"
//         ."users.id AS userid,"
//         ."users.username AS username,"
//         ."instruments.name AS instrumentname,"
//         ."instrumentclass.name AS instrumentclassname,"
//         ."projects.id AS projectid,"
//         ."projects.name AS projectname,"
//         ."projectgroups.grouppc AS pc,"
//         #."groups.id AS groupid,"
//         #."groups.name AS groupname,"
//         #."stoptime,"
//         #."starttime,"
//         #."(stoptime-starttime) AS usetime, " #this is a dodge (only works for whole hours, minutes broken)
//         #."SUBTIME(stoptime,starttime), "
//         # SUBTIME(t1,t2) and TIMEDIFF(t1,t2) were only added in MySQL v4.1.1
//         ."bookwhen AS starttime, "
//         ."DATE_ADD(bookwhen, INTERVAL duration HOUR_SECOND) AS stoptime, "
//         ."duration,"
//         ."ishalfday,"
//         ."isfullday, "
//         #."costs.name AS costcategory,"
//         ."userclass.name AS userclassname,"
// 
//         ."groups.* "
//         ."FROM bookings "
//         ."LEFT JOIN users ON users.id=bookings.userid "
//         ."LEFT JOIN instruments ON instruments.id=bookings.instrument "
//         ."LEFT JOIN instrumentclass ON instrumentclass.id=instruments.class "
//         ."LEFT JOIN projects ON bookings.projectid=projects.id "
//         ."LEFT JOIN userclass ON userclass.id=projects.defaultclass "
//         ."LEFT JOIN projectgroups ON projectgroups.projectid=bookings.projectid "
//         ."LEFT JOIN groups ON groups.id=projectgroups.groupid "
//         ."LEFT JOIN projectrates ON (projects.id=projectrates.projectid AND bookings.instrument=projectrates.instrid) "
//         ."LEFT JOIN costs AS dc ON (instruments.class=dc.instrumentclass AND projects.defaultclass=dc.userclass) "
//         ."LEFT JOIN costs AS sc ON (projectrates.rate=sc.id) "
//         ."WHERE (bookings.bookwhen>='$startdate' "
//         ."AND bookings.bookwhen<='$stopdate') ";
// 
//   
  
  
  

}  //ActionExport
?> 
