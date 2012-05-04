<?php
if(!file_exists(OC::$WEBROOT.'/remote/carddav.php')){
	file_put_contents(OC::$WEBROOT.'/remote/carddav.php', file_get_contents(OC::$APPSROOT . '/apps/contacts/appinfo/remote.php'));
}