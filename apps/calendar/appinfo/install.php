<?php
if(!file_exists(OC::$WEBROOT.'/remote/caldav.php')){
	file_put_contents(OC::$WEBROOT.'/remote/caldav.php', file_get_contents(OC::$APPSROOT . '/apps/calendar/appinfo/remote.php'));
}
