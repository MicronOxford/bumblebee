<?php
# $Id$

define('STATUS_NOOP',      0);
define('STATUS_OK',        1);
define('STATUS_WARN',      2);
define('STATUS_ERR',       4);
define('STATUS_FORBIDDEN', STATUS_ERR | 4);

?>