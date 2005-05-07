<?php
# $Id$
# Collect some system stats

class SystemStats {
  var $stats;
  var $tables;

  function SystemStats() {
    $tables = array('users', 'projects', 'instruments', 'bookings');
    foreach ($tables as $t) {
      $this->stats[$t]       = $this->countEntries($t);
    }
  }

  function countEntries($table) {
    global $TABLEPREFIX;
    $query='SELECT count(*) FROM '.$TABLEPREFIX.$table;
    //FIXME use sql fns
    if(!$sql = mysql_query($query)) die(mysql_error());
    $row=mysql_fetch_row($sql);
    return $row[0];
  }

  function get($t) {
    return $this->stats[$t];
  }
}
?>
