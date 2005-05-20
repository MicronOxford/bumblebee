<?php
# $Id$
# return a billing summary 

include_once 'inc/formslib/checkbox.php';
include_once 'inc/formslib/checkboxtablelist.php';
include_once 'inc/formslib/datareflector.php';
include_once 'inc/actions/bufferedaction.php';
include_once 'inc/bb/exporttypes.php';
include_once 'inc/exportcodes.php';

/**
 *  Find out what sort of report is required and generate it
 *
 */

class ActionExport extends BufferedAction {
  var $fatal_sql = 1;
  var $format;
  var $typelist;

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
      $daterange = new DateRange('daterange', 'Select date range', 
                      'Enter the dates over which you want to export data');
      $daterange->update($this->PD);
      $daterange->checkValid();
      $daterange->reflectData = 0;
      $daterange->includeSubmitButton = 0;
      if ($daterange->newObject || !$daterange->isValid) {
        $allDataOK = false;
        $this->unbuffer();
        $daterange->setDefaults(DR_PREVIOUS, DR_QUARTER);
        echo $daterange->display($this->PD);
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
        echo $this->reportAction($this->returnExport($daterange),
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
    if (isset($this->PDATA[1])) {
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
    $formatlist = array(EXPORT_FORMAT_HTML  => 'View in web browser', 
                        EXPORT_FORMAT_CSV   => 'Save as comma separated variable (csv)', 
                        EXPORT_FORMAT_TAB   => 'Save as tab separated variable (txt)', 
                        EXPORT_FORMAT_PDF   => 'Save as pdf report'
                       );
    $select = new RadioList('outputformat', 'Select which data to export', 1);
    $select->setValuesArray($formatlist, 'id', 'iv');
    $select->setFormat('id', '%s', array('iv'));
    $select->setDefault(EXPORT_FORMAT_CSV);
    echo '<div>'.$select->display().'</div>';
  }  
  
  
  function outputSelect() {
    $export = $this->typelist->types[$this->PD['what']];
    $select = new CheckBoxTableList('limitation', 'Select which detail to view');
    $hidden = new TextField($export->limitation);
    $select->addFollowHidden($hidden);
    $chosen = new CheckBox('selected', 'Selected');
    $select->addCheckBox($chosen);
    //$select->numSpareCols = 1;
    $select->connectDB($export->limitation, array('id', 'name', 'longname'));
    $select->setFormat('id', '%s', array('name'), " %50.50s", array('longname'));
    $select->addFooter('(<a href="#" onclick="return deselectsome(%d,1);">deselect all</a>)<br />'.
                       '(<a href="#" onclick="return selectsome(%d,1);">select all</a>)');
    echo $select->display();
    echo '<input type="hidden" name="limitationselected" value="1" />';
    echo '<input type="submit" name="submit" value="Select" />';
  }

  
    
  function returnExport($daterange) {
    $list = $this->_getDataList($daterange);
    $list->fill();
    if (count($list->data) == 0) {
      return $this->unbufferForError('<p>No data found for those criteria</p>');
    }      
    // start rendering the data
    if ($this->format == EXPORT_FORMAT_HTML || $this->format == EXPORT_FORMAT_PDF) {
      $list->outputFormat = EXPORT_FORMAT_HTML;
      $list->formatList();   
      $htmlBuffer = '<table class="exportdata">'
                    .'<tr class="header">'.$list->outputHeader().'</tr>'."\n"
                    .'<tr>'.join($list->formatdata, "</tr>\n<tr>").'</tr>'
                    .'</table>';
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
      $this->bufferedStream = $list->outputHeader()."\n".join($list->formatdata, "\n");
      // the data itself will be dumped later by the action driver (index.php)
    } else {
      $this->unbuffer();
      echo $htmlBuffer;
    }
  }
  
  function _getDataList($daterange) {
    $export = $this->typelist->types[$this->PD['what']];
    $start = $daterange->getStart();
    $stop  = $daterange->getStop();
    $stop->addDays(1);
    
    $limitation = array();
    $namebase = 'limitation-';
    for ($j=0; isset($this->PD[$namebase.$j.'-row']); $j++) {
      $item = issetSet($this->PD,$namebase.$j.'-'.$export->limitation);
      //echo "$j ($instr) => ($unbook, $announce)<br />";
      if (issetSet($this->PD,$namebase.$j.'-selected')) {
        $limitation[] = $export->limitation.'.id='.qw($item);
      }
    }
    $where = $export->where;
    $where[] = $export->timewhere[0].qw($start->datetimestring);
    $where[] = $export->timewhere[1].qw($stop->datetimestring);
    $where[] = '('.join($limitation, ' OR ').')';
    $list = new DBList($export->basetable, $export->fields, join($where, ' AND '));
    $list->join = array_merge($list->join, $export->join);
    $list->group = $export->group;
    $list->order = $export->order;
    $list->distinct = $export->distinct;
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
