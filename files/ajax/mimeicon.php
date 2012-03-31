<?php

// no need for apps
$RUNTIME_NOAPPS=false;

// Init owncloud
require_once('../../lib/base.php');

print OC_Helper::mimetypeIcon($_GET['mime']);

?>
