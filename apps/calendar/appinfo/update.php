<?php

$installedVersion=OCP\Config::getAppValue('calendar', 'installed_version');
if (version_compare($installedVersion, '0.2.1', '<')) {
	$stmt = OC_DB::prepare( 'SELECT id, calendarcolor FROM *PREFIX*calendar_calendars WHERE calendarcolor IS NOT NULL' );
	$result = $stmt->execute();
	while( $row = $result->fetchRow()) {
		$id = $row['id'];
		$color = $row['calendarcolor'];
		if ($color[0] == '#' || strlen($color) < 6) {
			continue;
		}
		$color = '#' .$color;
		$stmt = OC_DB::prepare( 'UPDATE *PREFIX*calendar_calendars SET calendarcolor=? WHERE id=?' );
		$r = $stmt->execute(array($color,$id));
	}
}
if(!file_exists(OC::$WEBROOT.'/remote/caldav.php')){
	file_put_contents(OC::$WEBROOT.'/remote/caldav.php', file_get_contents(OC::$APPROOT . '/apps/calendar/appinfo/remote.php'));
}