<?php
# $Id$

define('STATUS_NOOP',     -1);
define('STATUS_OK',        0);
define('STATUS_WARN',      1);
define('STATUS_ERR',       2);
define('STATUS_FORBIDDEN', STATUS_ERR | 4);

?>