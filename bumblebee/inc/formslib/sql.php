<?php
/**
* SQL interface functions, return statuscodes as appropriate
*
* The SQL functions are encapsulated here to make it easier
* to keep track of them, particularly for porting to other databases. Encapsulation is
* done here with a functional interface not an object interface.
*
* @todo work out why we didn't just use PEAR::DB and be done with it right from the beginning
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage FormsLibrary
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

/** status codes for success/failure of database actions */
require_once('inc/statuscodes.php');

/**
* run an sql query without returning data
*
* @param string $q       the sql query, properly constructed and quoted
* @param boolean $fatal_sql   db errors are fatal
* @return integer status from statuscodes
*/
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

/**
* run an sql query and return the sql handle for further requests
*
* @param string $q       the sql query, properly constructed and quoted
* @param boolean $fatal_sql   db errors are fatal
* @return resource mysql query handle
*/
function db_get($q, $fatal_sql=0) {
  // returns from statuscodes or a db handle
  $sql = mysql_query($q);
  echoSQL($q);
  if (! $sql) {
    return echoSQLerror(mysql_error(), $fatal_sql);
  } else {
    return $sql;
  }
}

/**
* run an sql query and return the single (or first) row returned
*
* @param string $q       the sql query, properly constructed and quoted
* @param boolean $fatal_sql   db errors are fatal
* @return mixed   array if successful, false if error
*/
function db_get_single($q, $fatal_sql=0) {
  $sql = db_get($q, $fatal_sql);
  //preDump($sql);
  return ($sql != STATUS_ERR ? mysql_fetch_array($sql) : false);
}

/**
* return the last insert ID from the database
* @return integer id (row number) of previous insert operation
*/
function db_new_id() {
  return mysql_insert_id();
}

/**
* return the next row from a query
*
* @param resource db query handle
* @return array next row from query
*/
function db_fetch_array($sql) {
  return mysql_fetch_array($sql);
}


/**
* get the number of rows returned by a query
*
* @param resource db query handle
* @return integer number of rows
*/
function db_num_rows($sql) {
  return mysql_num_rows($sql);
}

/**
* echo the SQL query to the browser
*
* @param string $echo       the sql query
* @param boolean $success   query was successful
* @global boolean should the SQL be shown
*/
function echoSQL($echo, $success=0) {
  global $VERBOSESQL;
  if ($VERBOSESQL) {
    echo "<div class='sql'>$echo "
        .($success ? '<div>'.T_('(successful)').'</div>' : '')
        ."</div>";
  }
}
  

/**
* echo the SQL query to the browser
*
* @param string $echo       the sql query
* @param boolean $fatal     die on error
* @global boolean should the SQL be shown
* @global string the email address of the administrator
*/
function echoSQLerror($echo, $fatal=0) {
  global $VERBOSESQL, $ADMINEMAIL;
  if ($echo != '' && $echo) {
    if ($VERBOSESQL) {
      echo "<div class='sql error'>$echo</div>";
    }
   if ($fatal) {
      echo "<div class='sql error'>"
        .sprintf(T_("Ooops. Something went very wrong. Please send the following log information to <a href='mailto:%s'>your Bumblebee Administrator</a> along with a description of what you were doing and ask them to pass it on to the Bumblebee developers. Thanks!"), $ADMINEMAIL)
        .'</div>';
      if ($VERBOSESQL) {
        preDump(debug_backtrace());
      } else {
        logmsg(1, "SQL ERROR=[$echo]");
        logmsg(1, join("//", debug_backtrace()));
      }
      die('<b>'.T_('Fatal SQL error. Aborting.').'</b>');
    }
  }
  return STATUS_ERR;
}
  
/**
* construct and perform a simple SQL select 
*
* @param string  $table  name of the table (will have TABLEPREFIX added to it
* @param mixed   $key    single column name or list of columns for the WHERE clause
* @param mixed   $value  single value or list of values for WHERE $key=$value
* @param boolean $fatal     die on error
* @param boolean $countonly   run a COUNT(*) query not a SELECT query
* @return mixed   array if successful, false if error
* @global string prefix for tabl nname 
*/
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
      .' LIMIT 1';         // we only ever return one row from this func, so LIMIT the query.
  return db_get_single($q, $fatal);
}

/**
* returns the current version of the database that is being talked to
* @return string database version
*/
function db_get_version() {
  return mysql_get_server_info();
}

/**
* returns the name of the database software that is being talked to
* @return string database server software name
*/
function db_get_name() {
  return 'MySQL';
}

?> 
