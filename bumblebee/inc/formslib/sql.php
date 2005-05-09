<?php
# $Id$
# simplifying sql functions

include_once('inc/statuscodes.php');

function db_quiet($q, $fatal_sql=0) {
  #returns false on success, true (error) on failure
  $sql = mysql_query($q);
  echoSQL($q);
  if (! $sql) {
    return echoSQLerror(mysql_error(), $fatal_sql);
  } else {
    return STATUS_OK; // should this return the $sql handle?
  }
}

function db_get($q, $fatal_sql=0) {
  #returns false on success, true (error) on failure
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
  if ($echo != '' && $echo) {
    if ($VERBOSESQL) {
      echo "<div class='sql error'>$echo</div>";
    }
   if ($fatal) {
      preDump(debug_backtrace());
      die("<b>Fatal SQL error. Aborting.</b>");
    }
  }
  return STATUS_ERR;
}
  
function quickSQLSelect($table, $key, $value, $fatal=1, $countonly=0) {
  global $TABLEPREFIX;
  if (! is_array($key) && ! is_array($value) && $key != '' && $value != '') {
    $key = array($key);
    $value = array($value);
  }
  $where = array();
  foreach ($key as $k => $col) {
    $where[] = $col.'='.qw($value[$k]);
  }
  $q = 'SELECT '.($countonly ? 'count(*)' : '*')
      .' FROM '.$TABLEPREFIX.$table
      .(count($where) ? ' WHERE '.join($where,' AND ') : '');
  return db_get_single($q, $fatal);
}

?> 
