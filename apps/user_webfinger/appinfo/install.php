<?php
$appInfoDir = __DIR__;
$thisAppDir = dirname($appInfoDir);
$appsDir = dirname($thisAppDir);
$ownCloudDir = dirname($appsDir);
@symlink($thisAppDir, $ownCloudDir.'/.well-known');
