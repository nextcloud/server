<?php
$ownCloudBaseUri = substr($_SERVER['REQUEST_URI'],0, -(strlen('/apps/user_webfinger/activate.php')));
$thisAppDir = __DIR__;
$appsDir = dirname($thisAppDir);
$ownCloudDir = dirname($appsDir);
try{
	symlink($thisAppDir, $ownCloudDir.'/.well-known');
	echo "Webfinger should now work.";
} catch(Exception $e) {
	echo "Please create a file called '.well-known in the ownCloud root, give the web server user permission to change it, and retry;";
}
