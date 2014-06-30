<?php
/**
* SQL interface functions, return statuscodes as appropriate
*
* The SQL functions are encapsulated here to make it easier
* to keep track of them, particularly for porting to other databases.
* Encapsulation is done here with a functional interface not an object
* interface.
*
* @todo //TODO: work out why we didn't just use PEAR::DB and be done with it
* right from the beginning.
*
* @author     Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage FormsLibrary
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

/** we need the connection made */
require_once('inc/db.php');

/** status codes for success/failure of database actions */
require_once('inc/statuscodes.php');

/**
* run an sql query without returning data
*
* @param string $q       the sql query, properly constructed and quoted
* @param boolean $fatal_sql   db errors are fatal
* @return integer status from statuscodes
*/
function db_quiet($q, $fatal_sql = 0)
{
  global $DBH;

  //preDump(debug_backtrace());
  // returns from statuscodes
  $sql = db_get($q, $fatal_sql);
  if ($sql)
    return STATUS_OK; // should this return the $sql handle?
}

/**
* run an sql query and return the sql handle for further requests
*
* @param string $q       the sql query, properly constructed and quoted
* @param boolean $fatal_sql   db errors are fatal
* @return PDOStatement
*/
function db_get($q, $fatal_sql = 0)
{
  global $DBH;

  //preDump(debug_backtrace());
  // returns from statuscodes or a db handle
  $sql = $DBH->query($q);
  echoSQL($q);
  if (! $sql)
    return echoSQLerror($DBH->erroInfo()[2], $fatal_sql);
  else
    return $sql;
}

/**
* run an sql query and return the single (or first) row returned
*
* @param string $q       the sql query, properly constructed and quoted
* @param boolean $fatal_sql   db errors are fatal
* @return mixed   array if successful, false if error
*/
function db_get_single($q, $fatal_sql = 0)
{
  $sql = db_get($q, $fatal_sql);
  //preDump($sql);
  // XXX: we should refactor this. We could make comparisons here back when
  // using the mysql_ module. The comparison will give a warning now that
  // it is a PDOStatement. But db_get may be catching errors and returning
  // a error so we have to do this until we fix this whole thing.
  return (is_a($sql, 'PDOStatement') ? $sql->fetch() : false);
}

/**
* return the last insert ID from the database
* @return integer id (row number) of previous insert operation
*/
function db_new_id()
{
  global $DBH;
  return intval($DBH->lastInsertId());
}

/**
* return the next row from a query
*
* @param PDStatement
* @return array next row from query
*/
function db_fetch_array($sql)
{
  if (! is_a($sql, 'PDOStatement'))
    return null;
  else
    return $sql->fetch();
}

/**
* get the number of rows returned by a query
*
* @param PDOStatement
* @return integer number of rows
*/
function db_num_rows($sql)
{
  return $sql::rowCount();
}

/**
* echo the SQL query to the browser
*
* @param string $echo       the sql query
* @param boolean $success   query was successful
*/
function echoSQL($echo, $success=0)
{
  $conf = ConfigReader::getInstance();
  if ($conf->VerboseSQL)
    {
      echo "<div class='sql'>" . xssqw($echo, false)
            . ($success ? '<div>'.T_('(successful)').'</div>' : '')
            . "</div>";
    }
}

/**
* echo the SQL query to the browser
*
* @param string $echo       the sql query
* @param boolean $fatal     die on error
*/
function echoSQLerror($echo, $fatal = 0)
{
  $conf = ConfigReader::getInstance();
  if ($echo != '' && $echo)
    {
      if ($conf->VerboseSQL)
        echo "<div class='sql error'>". xssqw($echo) ."</div>";

      if ($fatal)
        {
          $i_errmsg = <<<'END'
<div class='sql error'>
Ooops. Something went very wrong. Please send the following log information
to <a href='mailto:%s'>your Bumblebee Administrator</a> along with a
description of what you were doing and ask them to pass it on to the
Bumblebee developers. Thanks!
</div>
END;
          echo sprintf(T_($i_errmsg), $conf->AdminEmail);

          if ($conf->VerboseSQL)
            preDump(debug_backtrace());
          else
            {
              logmsg(1, "SQL ERROR=[$echo]");
              logmsg(1, serialize(debug_backtrace()));
            }
          die('<b>' . T_('Fatal SQL error. Aborting.') . '</b>');
        }
    }
  return STATUS_ERR;
}

/**
* construct and perform a simple SQL select
*
* @param string  $table  name of the table (will have TABLEPREFIX added to it
* @param mixed   $key    single column name or list of columns for the WHERE
*                        clause
* @param mixed   $value  single value or list of values for WHERE $key=$value
* @param boolean $fatal     die on error
* @param boolean $countonly   run a COUNT(*) query not a SELECT query
* @return mixed   array if successful, false if error
* @global string prefix for tabl nname
*/
function quickSQLSelect($table, $key, $value, $fatal = 1, $countonly = 0)
{
  global $TABLEPREFIX;

  if (! is_array($key))
    {
      if ($key != '')
        $key = array($key);
      else
        $key = array();
    }
  if (! is_array($value))
    {
      if ($value != '')
        $value = array($value);
      else
        $value = array();
    }

  $where = array();
  foreach ($key as $k => $col)
    $where[] = $col.'='.qw($value[$k]);

  $q = 'SELECT ' . ($countonly ? 'count(*)' : '*')
     . ' FROM ' . $TABLEPREFIX . $table
     . (count($where) ? ' WHERE ' . join($where,' AND ') : '')
     . ' LIMIT 1'; // we only ever return one row from this func
  return db_get_single($q, $fatal);
}

function db_last_error()
{
  global $DBH;
  return $DBH->erroInfo()[2];
}

?>
