<?php
# $Id$
#

  function actionBilling()
  {
    if (! isset($_POST['selectinstruments'])) {
      selectBillingInstruments();
    } elseif (! isset($_POST['selectusers'])) {
      selectBillingUsers();
    } elseif (! isset($_POST['selectformat'])) {
      selectBillingFormat();
    } else {
      returnBillingData();
    }
  }

  function selectBillingInstruments() {
    echo "<h2>Please select the instruments to include in the analysis</h2>";
    $q = "SELECT DISTINCT * "
        ."FROM instruments "
        ."ORDER BY name";
    $sql = mysql_query($q);
    if (! $sql) die (mysql_error());
    echo "<table>";
    echo "<tr><th colspan='2'>Instrument</th>"
        ."</tr>";
    if (mysql_num_rows($sql)==0) {
      echo "<tr><td colspan='2'>Sorry: no instruments found</td></tr>";
    } else {
      $j = 0;
      while ($g = mysql_fetch_array($sql)) {
        $j++;
        echo "<tr>"
            ."<td>".$g['name']."</td>"
            ."<td><input type='checkbox' name='billinstr-$j' value='1' /></td>"
            ."</tr>"
            ."<input type='hidden' name='instr-$j' value='".$g['id']."' />";
      }
      echo "<tr><td>Consumables</td>"
          ."<td><input type='checkbox' name='billing-consumables' value='1' /></td>"
          ."</tr>";
      /*echo "<tr><td><a href='#' "
          ."onclick='"
          #."alert(\"start\");"
          ."for (var i=0; i<document.forms[0].length; i++) {"
            ."if(document.forms[0].elements[i].type == \"checkbox\"); "
              ."document.forms[0].elements[i].checked=true;"
            #."alert(document.forms[0].elements[i].value);"
          ."} "
          ."return false;"
          ."'>select all</a></td>"
          ."</tr>";*/
      echo "<tr><td colspan='2'>(<a href='#' onclick='return selectall();'>select all</a> | ";
      echo "<a href='#' onclick='return deselectall();'>deselect all</a>)</td></tr>";
      echo "
        <input type='hidden' name='selectinstruments' value='1' />
        <tr><td><button name='action' type='submit' value='billing'>
            Next &gt;
          </button>
        </td></tr>
      ";
    }
    echo "</table>";
  }

  function selectBillingUsers() {
    $q = "SELECT id,username,name "
        ."FROM users "
        ."ORDER BY name";
    if (!$sql = mysql_query($q)) die(mysql_error());
    echo "<table>";
    echo "<tr><th colspan='2'>Users</th>"
        ."</tr>";
    if (mysql_num_rows($sql)==0) {
      echo "<tr><td colspan='2'>Sorry: no users found</td></tr>";
    } else {
      $j = 0;
      while ($g = mysql_fetch_array($sql)) {
        $j++;
        echo "<tr>"
            ."<td>".$g['name']." (".$g['name'].")</td>"
            ."<td><input type='checkbox' name='billuser-$j' value='1' /></td>"
            ."</tr>"
            ."<input type='hidden' name='user-$j' value='".$g['id']."' />";
      }
      echo "<tr><td colspan='2'>(<a href='#' onclick='return selectall();'>select all</a> | ";
      echo "<a href='#' onclick='return deselectall();'>deselect all</a>)</td></tr>";
      reflectdata();
      echo "
        <input type='hidden' name='selectusers' value='1' />
        <tr><td><button name='action' type='submit' value='billing'>
            Next &gt;
          </button>
        </td></tr>
      </table>
      ";
    }
    echo "</table>";
  }

  function selectBillingFormat() {
    $q = "SELECT * "
        ."FROM  billing_formats ";
    if (!$sql = mysql_query($q)) die(mysql_error());
    echo "<table>";
    echo "<tr><th colspan='2'>Formats</th>"
        ."</tr>";
    if (mysql_num_rows($sql)==0) {
      echo "<tr><td colspan='2'>Sorry: no billing formats found</td></tr>";
    } else {
      $j = 0;
      while ($g = mysql_fetch_array($sql)) {
        $j++;
        echo "<tr>"
            ."<td>".$g['name']."</td>"
            ."<td><input type='radio' name='format' value='format-$j' /></td>"
            ."</tr>";
      }
    echo "<tr><th colspan='2'>Analysis Period</th>"
        ."</tr>";
      $start=mktime (0,0,0,date("m")-1,date("d"),date("Y"));
      $lastmonth=date("n", $start);
      $lastyear=date("Y", $start);
      echo "<tr>"
          ."<td>Start date (dd/mm/yyyy)</td>"
          ."<td>"
          ."<input type='text' name='startday' value='1' size='2' /> / "
          ."<input type='text' name='startmonth' value='$lastmonth' size='2' /> / "
          ."<input type='text' name='startyear' value='$lastyear' size='4' />"
          ."</td>"
          ."</tr>";
      $stop=mktime (0,0,0,date("m"),1-1,date("Y"));
      $day=date("j", $stop);
      $month=date("n", $stop);
      $year=date("Y", $stop);
      echo "<tr>"
          ."<td>Stop date (dd/mm/yyyy)</td>"
          ."<td>"
          ."<input type='text' name='stopday' value='$day' size='2' /> / "
          ."<input type='text' name='stopmonth' value='$month' size='2' /> / "
          ."<input type='text' name='stopyear' value='$year' size='4' />"
          ."</td>"
          ."</tr>";
      reflectdata();
      echo "
        <input type='hidden' name='selectformat' value='1' />
        <tr><td><button name='action' type='submit' value='billing'>
            Next &gt;
          </button>
        </td></tr>
      </table>
      ";
    }
    echo "</table>";
  }
    

  function displayPost() {
    echo "<table border='1'><tr><th>key</th><th>value</th></tr>\n";
    foreach ($_POST as $key => $supplied) {
      echo "<tr><td>'$key'</td><td>'$supplied'</td></tr>\n";
    }
    echo "\n</table>";
  }

  function returnBillingData() {
    displayPost();
    $startdate = sprintf("%04d-%02d-%02d", $_POST['startyear'], $_POST['startmonth'], $_POST['startday']);
    $stopdate = sprintf("%04d-%02d-%02d", $_POST['stopyear'], $_POST['stopmonth'], $_POST['stopday']);
    #$q = "SELECT bookings.id,users.id,users.username,projects.id,projects.name,groups.id,groups.name,stoptime,starttime,ishalfday,isfullday "
    $q = "SELECT "
        ."bookings.id AS bookingid,"
        ."users.id AS userid,"
        ."users.username AS username,"
        ."instruments.name AS instrumentname,"
        ."instrumentclass.name AS instrumentclassname,"
        ."projects.id AS projectid,"
        ."projects.name AS projectname,"
        ."projectgroups.grouppc AS pc,"
        #."groups.id AS groupid,"
        #."groups.name AS groupname,"
        #."stoptime,"
        #."starttime,"
        #."(stoptime-starttime) AS usetime, " #this is a dodge (only works for whole hours, minutes broken)
        #."SUBTIME(stoptime,starttime), "
        # SUBTIME(t1,t2) and TIMEDIFF(t1,t2) were only added in MySQL v4.1.1
        ."bookwhen AS starttime, "
        ."DATE_ADD(bookwhen, INTERVAL duration HOUR_SECOND) AS stoptime, "
        ."duration,"
        ."ishalfday,"
        ."isfullday, "
        #."costs.name AS costcategory,"
        ."userclass.name AS userclassname,"
        ."sc.cost_hour AS sch, sc.cost_halfday AS schd, sc.cost_fullday AS scfd,"
        ."dc.cost_hour AS dch, dc.cost_halfday AS dchd, dc.cost_fullday AS dcfd,"
        /*
        ."((1-ishalfday)*(1-isfullday)*(HOUR(duration)+MINUTE(duration)/60)*costs.cost_hour) AS costh1, "
        ."(ishalfday*costs.cost_halfday) AS costhd1, "
        ."(isfullday*costs.cost_fullday) AS costfd1, "
        */
        ## Calculate the cost for the booking, making use of the percentage
        ## contribution from different groups and any discount that may be
        ## applied to an individual booking
        /*
        ."("
        ."((1-ishalfday)*(1-isfullday)*(HOUR(duration)+MINUTE(duration)/60)*sc.cost_hour)+"
        ."(ishalfday*sc.cost_halfday)+"
        ."(isfullday*sc.cost_fullday) "
        .")*((100-bookings.discount)/100)"
        ."*projectgroups.grouppc/100 "
        ."AS totalcost,"
        ."("
        ."((1-ishalfday)*(1-isfullday)*(HOUR(duration)+MINUTE(duration)/60)*dc.cost_hour)+"
        ."(ishalfday*dc.cost_halfday)+"
        ."(isfullday*dc.cost_fullday) "
        .")*((100-bookings.discount)/100)"
        ."*projectgroups.grouppc/100 "
        ."AS dtotalcost,"
        */
        ."IFNULL("
        ."("
        ."((1-ishalfday)*(1-isfullday)*(HOUR(duration)+MINUTE(duration)/60)*sc.cost_hour)+"
        ."(ishalfday*sc.cost_halfday)+"
        ."(isfullday*sc.cost_fullday) "
        .")*((100-bookings.discount)/100)"
        ."*projectgroups.grouppc/100 "
        .","
        ."("
        ."((1-ishalfday)*(1-isfullday)*(HOUR(duration)+MINUTE(duration)/60)*dc.cost_hour)+"
        ."(ishalfday*dc.cost_halfday)+"
        ."(isfullday*dc.cost_fullday) "
        .")*((100-bookings.discount)/100)"
        ."*projectgroups.grouppc/100 "
        .") AS totalcost, "
        ."groups.* "
        ."FROM bookings "
        ."LEFT JOIN users ON users.id=bookings.userid "
        ."LEFT JOIN instruments ON instruments.id=bookings.instrument "
        ."LEFT JOIN instrumentclass ON instrumentclass.id=instruments.class "
        ."LEFT JOIN projects ON bookings.projectid=projects.id "
        ."LEFT JOIN userclass ON userclass.id=projects.defaultclass "
        ."LEFT JOIN projectgroups ON projectgroups.projectid=bookings.projectid "
        ."LEFT JOIN groups ON groups.id=projectgroups.groupid "
        ."LEFT JOIN projectrates ON (projects.id=projectrates.projectid AND bookings.instrument=projectrates.instrid) "
        ."LEFT JOIN costs AS dc ON (instruments.class=dc.instrumentclass AND projects.defaultclass=dc.userclass) "
        ."LEFT JOIN costs AS sc ON (projectrates.rate=sc.id) "
        ."WHERE (bookings.bookwhen>='$startdate' "
        ."AND bookings.bookwhen<='$stopdate') ";

        ########## FIXME ############

    $restrictions=array($q);
    ### Find the userids to be included in the billing analysis
    $wholist = array();
    for ($j=1; isset($_POST['billuser-'.$j]); $j++) {
      if ($_POST['billuser-'.$j]) {
        $wholist[] = "'" . $_POST['user-'.$j] . "'";
      }
    }
    # $wholist now contains the userid of all users to be included
    if (count($wholist)) {
      $restrictions[] = "users.id IN (".implode(",",$wholist).") ";
    }

    ### Find the intrumentids to be included in the billing analysis
    $instrlist = array();
    for ($j=1; isset($_POST['instr-'.$j]); $j++) {
      if ($_POST['billinstr-'.$j]) {
        $instrlist[] = "'".$_POST['instr-'.$j]."'";
      }
    }
    # $instrlist now contains the instrid of all instruments to be included
    if (count($instrlist)) {
      $restrictions[] = "instruments.id IN (".implode(",",$instrlist).") ";
    }

    ### Find the projectids to be included in the billing analysis
    $projlist = array();
    for ($j=1; isset($_POST['billproj-'.$j]); $j++) {
      if ($_POST['billproj-'.$j]) {
        $projlist[] = "'".$_POST['proj-'.$j]."'";
      }
    }
    # $projlist now contains the projid of all projects to be included
    if (count($projlist)) {
      $restrictions[] = "projects.id IN (".implode(",",$projlist).") ";
    }

    ### Now construct the entire query out of the different parts
    $q = implode("AND ", $restrictions);

    echo "<br /><br />Get booking details: '$q' <br />";

    echo "<table border='1'>";
    if (!$sql = mysql_query($q)) die(mysql_error());
    $first=1;
    #while ($g = mysql_fetch_row($sql)) {
    while ($g = mysql_fetch_array($sql)) {
      if ($first) {
        echo "<tr>";
        foreach ($g as $key => $column) {
          if (! is_int($key)) echo "<th>$key</th>";
        }
        $first = 0;
      }
      echo "<tr>";
      foreach ($g as $key => $supplied) {
      #for ($i=0; $i<=count($g); $i++) {
        #echo "<td>$g[$key]</td>";
        if (! is_int($key)) echo "<td>$supplied</td>";
      }
      echo "</tr>";
      /*
      foreach ($g as $key => $supplied) {
        echo $key ."=". $supplied ." ";
      }
      echo "<br />";*/
    }
    echo "</table>";
    echo "<br /><br />Get booking details: '$q' successful<br />";
    /*
    # appears to be a MySQL > 4.1 thing again. :(
    $newq = "SELECT * FROM ($q) AS billing WHERE groups.id='5'";
    echo "<table border='1'>";
    if (!$sql = mysql_query($newq)) die(mysql_error());
    while ($g = mysql_fetch_array($sql)) {
    #while ($g = mysql_fetch_row($sql)) {
      echo "<tr>";
      for ($i=0; $i<=count($g); $i++) {
        echo "<td>$g[$i]</td>";
      }
      echo "</tr>";
    }
    echo "</table>";
    */
  }

  function reflectData() {
    # we need to reflect all the previously selected options back
    # to the user (hidden) so we can do some mega processing later on
    foreach ($_POST as $key => $supplied) {
      echo "<input type='hidden' name='$key' value='$supplied' />";
    }
  }
?> 
