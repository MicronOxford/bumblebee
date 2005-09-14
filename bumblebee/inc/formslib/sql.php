<?php
# $Id$
# simplifying sql functions

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
