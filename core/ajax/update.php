<?php
set_time_limit(0);
require_once '../../lib/base.php';

if (OC::checkUpgrade(false)) {
	$l = new \OC_L10N('core');
	$eventSource = \OC::$server->getEventSource();
	$updater = new \OC\Updater(\OC_Log::$object);
	$updater->listen('\OC\Updater', 'maintenanceStart', function () use ($eventSource, $l) {
		$eventSource->send('success', (string)$l->t('Turned on maintenance mode'));
	});
	$updater->listen('\OC\Updater', 'maintenanceEnd', function () use ($eventSource, $l) {
		$eventSource->send('success', (string)$l->t('Turned off maintenance mode'));
	});
	$updater->listen('\OC\Updater', 'dbUpgrade', function () use ($eventSource, $l) {
		$eventSource->send('success', (string)$l->t('Updated database'));
	});
	$updater->listen('\OC\Updater', 'dbSimulateUpgrade', function () use ($eventSource, $l) {
		$eventSource->send('success', (string)$l->t('Checked database schema update'));
	});
	$updater->listen('\OC\Updater', 'appUpgradeCheck', function () use ($eventSource, $l) {
		$eventSource->send('success', (string)$l->t('Checked database schema update for apps'));
	});
	$updater->listen('\OC\Updater', 'appUpgrade', function ($app, $version) use ($eventSource, $l) {
		$eventSource->send('success', (string)$l->t('Updated "%s" to %s', array($app, $version)));
	});
	$updater->listen('\OC\Updater', 'disabledApps', function ($appList) use ($eventSource, $l) {
		$list = array();
		foreach ($appList as $appId) {
			$info = OC_App::getAppInfo($appId);
			$list[] = $info['name'] . ' (' . $info['id'] . ')';
		}
		$eventSource->send('success', (string)$l->t('Disabled incompatible apps: %s', implode(', ', $list)));
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
