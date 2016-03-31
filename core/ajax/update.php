<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
set_time_limit(0);
require_once '../../lib/base.php';

$l = \OC::$server->getL10N('core');

$eventSource = \OC::$server->createEventSource();
// need to send an initial message to force-init the event source,
// which will then trigger its own CSRF check and produces its own CSRF error
// message
$eventSource->send('success', (string)$l->t('Preparing update'));

if (OC::checkUpgrade(false)) {
	// if a user is currently logged in, their session must be ignored to
	// avoid side effects
	\OC_User::setIncognitoMode(true);

	$logger = \OC::$server->getLogger();
	$config = \OC::$server->getConfig();
	$updater = new \OC\Updater(
			\OC::$server->getHTTPHelper(),
			$config,
			\OC::$server->getIntegrityCodeChecker(),
			$logger
	);
	$incompatibleApps = [];
	$disabledThirdPartyApps = [];

	$updater->listen('\OC\Updater', 'maintenanceEnabled', function () use ($eventSource, $l) {
		$eventSource->send('success', (string)$l->t('Turned on maintenance mode'));
	});
	$updater->listen('\OC\Updater', 'maintenanceDisabled', function () use ($eventSource, $l) {
		$eventSource->send('success', (string)$l->t('Turned off maintenance mode'));
	});
	$updater->listen('\OC\Updater', 'maintenanceActive', function () use ($eventSource, $l) {
		$eventSource->send('success', (string)$l->t('Maintenance mode is kept active'));
	});
	$updater->listen('\OC\Updater', 'dbUpgradeBefore', function () use($eventSource, $l) {
		$eventSource->send('success', (string)$l->t('Updating database schema'));
	});
	$updater->listen('\OC\Updater', 'dbUpgrade', function () use ($eventSource, $l) {
		$eventSource->send('success', (string)$l->t('Updated database'));
	});
	$updater->listen('\OC\Updater', 'dbSimulateUpgradeBefore', function () use($eventSource, $l) {
		$eventSource->send('success', (string)$l->t('Checking whether the database schema can be updated (this can take a long time depending on the database size)'));
	});
	$updater->listen('\OC\Updater', 'dbSimulateUpgrade', function () use ($eventSource, $l) {
		$eventSource->send('success', (string)$l->t('Checked database schema update'));
	});
	$updater->listen('\OC\Updater', 'appUpgradeCheckBefore', function () use ($eventSource, $l) {
		$eventSource->send('success', (string)$l->t('Checking updates of apps'));
	});
	$updater->listen('\OC\Updater', 'appSimulateUpdate', function ($app) use ($eventSource, $l) {
		$eventSource->send('success', (string)$l->t('Checking whether the database schema for %s can be updated (this can take a long time depending on the database size)', [$app]));
	});
	$updater->listen('\OC\Updater', 'appUpgradeCheck', function () use ($eventSource, $l) {
		$eventSource->send('success', (string)$l->t('Checked database schema update for apps'));
	});
	$updater->listen('\OC\Updater', 'appUpgrade', function ($app, $version) use ($eventSource, $l) {
		$eventSource->send('success', (string)$l->t('Updated "%s" to %s', array($app, $version)));
	});
	$updater->listen('\OC\Updater', 'repairWarning', function ($description) use ($eventSource, $l) {
		$eventSource->send('notice', (string)$l->t('Repair warning: ') . $description);
	});
	$updater->listen('\OC\Updater', 'repairError', function ($description) use ($eventSource, $l) {
		$eventSource->send('notice', (string)$l->t('Repair error: ') . $description);
	});
	$updater->listen('\OC\Updater', 'incompatibleAppDisabled', function ($app) use (&$incompatibleApps) {
		$incompatibleApps[]= $app;
	});
	$updater->listen('\OC\Updater', 'thirdPartyAppDisabled', function ($app) use (&$disabledThirdPartyApps) {
		$disabledThirdPartyApps[]= $app;
	});
	$updater->listen('\OC\Updater', 'failure', function ($message) use ($eventSource, $config) {
		$eventSource->send('failure', $message);
		$eventSource->close();
		$config->setSystemValue('maintenance', false);
	});
	$updater->listen('\OC\Updater', 'setDebugLogLevel', function ($logLevel, $logLevelName) use($eventSource, $l) {
		$eventSource->send('success', (string)$l->t('Set log level to debug'));
	});
	$updater->listen('\OC\Updater', 'resetLogLevel', function ($logLevel, $logLevelName) use($eventSource, $l) {
		$eventSource->send('success', (string)$l->t('Reset log level'));
	});
	$updater->listen('\OC\Updater', 'startCheckCodeIntegrity', function () use($eventSource, $l) {
		$eventSource->send('success', (string)$l->t('Starting code integrity check'));
	});
	$updater->listen('\OC\Updater', 'finishedCheckCodeIntegrity', function () use($eventSource, $l) {
		$eventSource->send('success', (string)$l->t('Finished code integrity check'));
	});

	try {
		$updater->upgrade();
	} catch (\Exception $e) {
		$eventSource->send('failure', get_class($e) . ': ' . $e->getMessage());
		$eventSource->close();
		exit();
	}

	$disabledApps = [];
	foreach ($disabledThirdPartyApps as $app) {
		$disabledApps[$app] = (string) $l->t('%s (3rdparty)', [$app]);
	}
	foreach ($incompatibleApps as $app) {
		$disabledApps[$app] = (string) $l->t('%s (incompatible)', [$app]);
	}

	if (!empty($disabledApps)) {
		$eventSource->send('notice',
			(string)$l->t('Following apps have been disabled: %s', implode(', ', $disabledApps)));
	}
} else {
	$eventSource->send('notice', (string)$l->t('Already up to date'));
}

$eventSource->send('done', '');
$eventSource->close();

