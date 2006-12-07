<?php
/**
* Anchor list similar to AnchorList, but this time in a table not dot points
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage FormsLibrary
*
* path (bumblebee root)/inc/formslib/anchortablelist.php
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

/** anchorlist parent object */
require_once 'anchorlist.php';
require_once 'bbstring.php';

/**
* Anchor list similar to AnchorList, but this time in a table not dot points
*
* @package    Bumblebee
* @subpackage FormsLibrary
*
*/
class AnchorTableList extends AnchorList {
    /** @var integer  number of columns in the table*/
    var $numcols    = '';
    /** @var string   html/css class for the header row of the table */
    var $headerRowClass = 'tableHeader';
    /** @var string   html/css class for each row in the table  */
    var $trclass    = 'itemrow';
    /** @var string   html/css class for left-side table cell */
    var $tdlclass   = 'itemL';
    /** @var string   html/css class for right-side table cell */
    var $tdrclass   = 'itemR';
    /** @var string   html/css class for anchors */
    var $aclass     = 'itemR';
    /** @var string   html/css class for entire table */
    var $tableclass = 'selectlist';
    /** @var array    list of table headings to be put at the top of the table */
    var $tableHeading;
    /** @var boolean  make all columns in the table link to the target URL not just the first */
    var $linkAll = true;
    /** @var boolean  controls the generation of header links to sort the table by */
    var $useSortByHeadings = false;
    /** @var string   tells which header to sort by. null for default. This value will be
     * superseeded by the sortby paramater in connectDB */
    var $defaultSortByHeading = 'name';
    /** @var array    data source from which the column to sort by will be read */
    var $data = null;
    /** @var string   key in $data in which the column name should be stored */
    var $sortbyKey;

    /**
     *  Create a new AnchorTableList
     *
     * @param string $name   the name of the field (db name, and html field name
     * @param string $description  used in the html title of the list
     * @param integer $numcols (optional) number of columns in the table
     */
    function AnchorTableList($name, $description='', $numcols=2) {

        $this->AnchorList($name, $description);
        $this->numcols = $numcols;
        $this->sortbyKey = $this->name.'_sortby';

    } //end function AnchorTableList

    /**
     *  Accessor method to set the table column headings
     *
     * @param array   new headings to use for the table
     */
    function setTableHeadings($headings) {

        $this->tableHeadings = $headings;

    } //end function setTableHeadings

    /**
     * Sets which heading to sort by
     *
     * @param boolean turn the sort by links on and off
     * @param array   data source to find which heading to sort by
     * @param string  (optional) the default heading to sort by
     *
     */
    function sortByHeadings($sort, $data, $heading = 'name') {

        $this->useSortByHeadings = $sort;
        $this->data = $data;
        $this->defaultSortByHeading = $heading;

    } //end function sortByHeadings

    function format($data) {
        //preDump($this);
        $aclass  = (isset($this->aclass) ? " class='$this->aclass'" : '');
        $trclass  = (isset($this->trclass) ? " class='$this->trclass'" : '');
        $tdlclass  = (isset($this->tdlclass) ? " class='$this->tdlclass'" : '');
        $tdrclass  = (isset($this->tdrclass) ? " class='$this->tdrclass'" : '');
        $linkformat = "<a href='".str_replace('__id__', $data[$this->formatid], $this->hrefbase)."'$aclass>"
            ."%s</a>";
        $t  = "<tr $trclass>"
            ."<td $tdlclass>";
        $t .= sprintf($linkformat, $this->formatter[0]->format($data));
        $t .= "</td>\n";

        for ($i=2; $i<=$this->numcols; $i++) {

            $t .= "<td $tdrclass>";

            if ($this->linkAll) {
                $t .= sprintf($linkformat, $this->formatter[$i-1]->format($data));
            } else {
                $t .= $this->formatter[$i-1]->format($data);
            } //end if-else

            $t .= "</td>";
        } //end for

        $t .= "</tr>\n";
        return $t;
    } //end function format

    function display() {
        $tableclass = (isset($this->tableclass) ? " class='$this->tableclass'" : '');
        $t  = "<table title='$this->description' $tableclass>\n";

        if (isset($this->tableHeadings)) {

            $t .= "<tr class='{$this->headerRowClass}'>";

            if(! $this->useSortByHeadings) {
                foreach ($this->tableHeadings as $heading) {

                    if(type_is_a($heading, 'bbString')) {
                        $t .='<th>'.$heading->getExternalRep().'</th>';

                    } else {
                        $t .= "<th>$heading</th>";
                    } //end if-else
                } //end foreach

            } else {

                $aclass = (isset($this->aclass) ? " class='{$this->aclass}'" : '');

                foreach($this->tableHeadings as $heading) {
                  $text   = type_is_a($heading, 'bbString')
                                              ? $heading->getExternalRep() : $heading;
                  $sortby = type_is_a($heading, 'bbString')
                                              ? $heading->getInternalRep() : $heading;
                  $href = str_replace('__sortby__', $sortby, $this->sortByHref);

                  $t .=  "<th><a href='".$href."' $aclass title='".T_('Sort by this column')."'>".$text.'</a></th>';
                } //end foreach

            } //end if-else

            $t .= "</tr>\n";
        } //end if

        if (is_array($this->list->choicelist)) {
            foreach ($this->list->choicelist as $v) {
                $t .= $this->format($v);
            } //end foreach
        } //end if

        $t .= "</table>\n";
        return $t;
    } //end function display()

    /**
     *  overloading of ChoiceList's connectDB to allow for remembering the
     *     sortby column
     */
    function connectDB($table, $fields = '', $restriction = '', $order = 'name',
            $idfield = 'id', $limit = '', $join = '', $distinct = false) {

        if ($this->useSortByHeadings) {
            $order = qw(issetSet($this->data, $this->sortbyKey, $this->defaultSortByHeading));
        }
        parent::connectDB($table, $fields, $restriction, $order, $idfield, $limit,
                    $join, $distinct);

    } //end function connectDB

} // class AnchorList

?>
