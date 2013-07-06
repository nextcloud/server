<?php
set_time_limit(0);
$RUNTIME_NOAPPS = true;
require_once '../../lib/base.php';

if (OC::checkUpgrade(false)) {
	$eventSource = new OC_EventSource();
	$updater = new \OC\Updater(\OC_Log::$object);
	$updater->listen('\OC\Updater', 'maintenanceStart', function () use ($eventSource) {
		$eventSource->send('success', 'Turned on maintenance mode');
	});
	$updater->listen('\OC\Updater', 'maintenanceEnd', function () use ($eventSource) {
		$eventSource->send('success', 'Turned off maintenance mode');
	});
	$updater->listen('\OC\Updater', 'dbUpgrade', function () use ($eventSource) {
		$eventSource->send('success', 'Updated database');
	});
	$updater->listen('\OC\Updater', 'filecacheStart', function () use ($eventSource) {
		$eventSource->send('success', 'Updating filecache, this may take really long...');
	});
	$updater->listen('\OC\Updater', 'filecacheDone', function () use ($eventSource) {
		$eventSource->send('success', 'Updated filecache');
	});
	$updater->listen('\OC\Updater', 'filecacheProgress', function ($out) use ($eventSource) {
		$eventSource->send('success', '... ' . $out . '% done ...');
	});
	$updater->listen('\OC\Updater', 'failure', function ($message) use ($eventSource) {
		$eventSource->send('failure', $message);
		$eventSource->close();
		OC_Config::setValue('maintenance', false);
	});

	$updater->upgrade();

	$eventSource->send('done', '');
	$eventSource->close();
}
