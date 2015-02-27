<?php
set_time_limit(0);
require_once '../../lib/base.php';

if (OC::checkUpgrade(false)) {
	// if a user is currently logged in, their session must be ignored to
	// avoid side effects
	\OC_User::setIncognitoMode(true);

	$l = new \OC_L10N('core');
	$eventSource = \OC::$server->createEventSource();
	$updater = new \OC\Updater(
			\OC::$server->getHTTPHelper(),
			\OC::$server->getConfig(),
			\OC_Log::$object
	);
	$incompatibleApps = [];
	$disabledThirdPartyApps = [];

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
	$updater->listen('\OC\Updater', 'incompatibleAppDisabled', function ($app) use (&$incompatibleApps) {
		$incompatibleApps[]= $app;
	});
	$updater->listen('\OC\Updater', 'thirdPartyAppDisabled', function ($app) use (&$disabledThirdPartyApps) {
		$disabledThirdPartyApps[]= $app;
	});
	$updater->listen('\OC\Updater', 'failure', function ($message) use ($eventSource) {
		$eventSource->send('failure', $message);
		$eventSource->close();
		OC_Config::setValue('maintenance', false);
	});

	$updater->upgrade();

	if (!empty($incompatibleApps)) {
		$eventSource->send('notice',
			(string)$l->t('Following incompatible apps have been disabled: %s', implode(', ', $incompatibleApps)));
	}
	if (!empty($disabledThirdPartyApps)) {
		$eventSource->send('notice',
			(string)$l->t('Following 3rd party apps have been disabled: %s', implode(', ', $disabledThirdPartyApps)));
	}

	$eventSource->send('done', '');
	$eventSource->close();
}
