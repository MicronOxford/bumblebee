<?php
/**
* SQL interface functions, return statuscodes as appropriate
*
* <b>Most</b> of the SQL functions are encapsulated here to make it easier
* to keep track of them, particularly for porting to other databases. Encapsulation is
* done here with a functional interface not an object interface.
*
* Note that thre are a number of mysql_fetch_array calls in other places where
* there are a number of rows in the query so the encapsulation is not that good.
*
* @todo provide a sensible function to allow full db encapsulation and remove mysql specific functions from code.
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage FormsLibrary
*/

/** status codes for success/failure of database actions */
include_once('inc/statuscodes.php');

function db_quiet($q, $fatal_sql=0) {
  // returns from statuscodes
  $sql = mysql_query($q);
  echoSQL($q);
  if (! $sql) {
    return echoSQLerror(mysql_error(), $fatal_sql);
  } else {
    return STATUS_OK; // should this return the $sql handle?
  }
}

function db_get($q, $fatal_sql=0) {
  // returns from statuscodes
  $sql = mysql_query($q);
  echoSQL($q);
  if (! $sql) {
    return echoSQLerror(mysql_error(), $fatal_sql);
  } else {
    return $sql;
  }
}

function db_get_single($q, $fatal_sql=0) {
  $sql = db_get($q, $fatal_sql);
  //preDump($sql);
  return ($sql != STATUS_ERR ? mysql_fetch_array($sql) : false);
}

function db_new_id() {
  return mysql_insert_id();
}

function echoSQL($echo, $success=0) {
  global $VERBOSESQL;
  if ($VERBOSESQL) {
    echo "<div class='sql'>$echo "
        .($success ? '<div>(successful)</div>' : '')
        ."</div>";
  }
}
  

function echoSQLerror($echo, $fatal=0) {
  global $VERBOSESQL;
  global $ADMINEMAIL;
  if ($echo != '' && $echo) {
    if ($VERBOSESQL) {
      echo "<div class='sql error'>$echo</div>";
    }
   if ($fatal) {
      echo "<div class='sql error'>Ooops. Something went very wrong. Please send the following log information to <a href='mailto:$ADMINEMAIL'>your Bumblebee Administrator</a> along with a description of what you were doing and ask them to pass it on to the Bumblebee developers. Thanks!</div>";
      preDump(debug_backtrace());
      die("<b>Fatal SQL error. Aborting.</b>");
    }
  }
  return STATUS_ERR;
}
  
function quickSQLSelect($table, $key, $value, $fatal=1, $countonly=0) {
  global $TABLEPREFIX;
  if (! is_array($key)) {
    if ($key != '') {
      $key = array($key);
    } else {
      $key = array();
    }
  }
  if (! is_array($value)) {
    if ($value != '') {
      $value = array($value);
    } else {
      $value = array();
    }
  }
  $where = array();
  foreach ($key as $k => $col) {
    $where[] = $col.'='.qw($value[$k]);
  }
  $q = 'SELECT '.($countonly ? 'count(*)' : '*')
      .' FROM '.$TABLEPREFIX.$table
      .(count($where) ? ' WHERE '.join($where,' AND ') : '')
      .' LIMIT 1';         // we only ever return one row from this fn, so LIMIT the query.
  return db_get_single($q, $fatal);
}

?> 
