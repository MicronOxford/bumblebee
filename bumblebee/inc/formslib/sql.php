<?php
# $Id$
# simplifying sql functions

function db_quiet($q, $fatal_sql=0) {
  #returns false on success, true (error) on failure
  $sql = mysql_query($q);
  echoSQL($q);
  if (! $sql) {
    return echoSQLerror(mysql_error(), $fatal_sql);
  } else {
    return 0;
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
  return mysql_fetch_array($sql);
}

function db_new_id() {
  return mysql_insert_id();
}

function echoSQL($echo, $success=0) {
  global $VERBOSESQL;
  if ($VERBOSESQL) {
    echo "<div class='sql'>$echo "
        .($success ? "<div>(successful)</div>" : "")
        ."</div>";
  }
}
  

function echoSQLerror($echo, $fatal=0) {
  global $VERBOSESQL;
  if ($echo != "" && $echo) {
    if ($VERBOSESQL) {
      echo "<div class='sql error'>$echo</div>";
    }
    if ($fatal) {
      die("<b>Fatal SQL error. Aborting.</b>");
    }
  }
  return $echo;
}
  

?> 
