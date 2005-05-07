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
    $row = quickSQLSelect($table, '', '', 0, 1);
    return $row[0];
  }

  function get($t) {
    return $this->stats[$t];
  }
}
?>
