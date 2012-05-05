<?php

$installedVersion=OCP\Config::getAppValue('calendar', 'installed_version');
if (version_compare($installedVersion, '0.2.1', '<')) {
	$stmt = OCP\DB::prepare( 'SELECT id, calendarcolor FROM *PREFIX*calendar_calendars WHERE calendarcolor IS NOT NULL' );
	$result = $stmt->execute();
	while( $row = $result->fetchRow()) {
		$id = $row['id'];
		$color = $row['calendarcolor'];
		if ($color[0] == '#' || strlen($color) < 6) {
			continue;
		}
		$color = '#' .$color;
		$stmt = OCP\DB::prepare( 'UPDATE *PREFIX*calendar_calendars SET calendarcolor=? WHERE id=?' );
		$r = $stmt->execute(array($color,$id));
	}
}