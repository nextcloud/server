<?php
if(!file_exists(OC::$WEBROOT.'/remote/webdav.php')){
	file_put_contents(OC::$WEBROOT.'/remote/webdav.php', file_get_contents(OC::$APPSROOT . '/apps/files/appinfo/webdav.php'));
}