<?php
$thisAppDir = __DIR__;
$appsDir = dirname($thisAppDir);
$ownCloudDir = dirname($appsDir);
symlink($thisAppDir, $ownCloudDir.'/.well-known');
