<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
use OC\DB\MigratorExecuteSqlEvent;
use OC\Repair\Events\RepairAdvanceEvent;
use OC\Repair\Events\RepairErrorEvent;
use OC\Repair\Events\RepairFinishEvent;
use OC\Repair\Events\RepairInfoEvent;
use OC\Repair\Events\RepairStartEvent;
use OC\Repair\Events\RepairStepEvent;
use OC\Repair\Events\RepairWarningEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IEventSource;
use OCP\IEventSourceFactory;
use OCP\IL10N;
use OCP\L10N\IFactory;
use OCP\Server;
use Psr\Log\LoggerInterface;

if (!str_contains(@ini_get('disable_functions'), 'set_time_limit')) {
	@set_time_limit(0);
}

require_once '../../lib/base.php';

/** @var \OCP\IL10N $l */
$l = \OC::$server->get(IFactory::class)->get('core');

$eventSource = \OC::$server->get(IEventSourceFactory::class)->create();
// need to send an initial message to force-init the event source,
// which will then trigger its own CSRF check and produces its own CSRF error
// message
$eventSource->send('success', $l->t('Preparing update'));

class FeedBackHandler {
	private int $progressStateMax = 100;
	private int $progressStateStep = 0;
	private string $currentStep = '';

	public function __construct(
		private IEventSource $eventSource,
		private IL10N $l10n,
	) {
	}

	public function handleRepairFeedback(Event $event): void {
		if ($event instanceof RepairStartEvent) {
			$this->progressStateMax = $event->getMaxStep();
			$this->progressStateStep = 0;
			$this->currentStep = $event->getCurrentStepName();
		} elseif ($event instanceof RepairAdvanceEvent) {
			$this->progressStateStep += $event->getIncrement();
			$desc = $event->getDescription();
			if (empty($desc)) {
				$desc = $this->currentStep;
			}
			$this->eventSource->send('success', $this->l10n->t('[%d / %d]: %s', [$this->progressStateStep, $this->progressStateMax, $desc]));
		} elseif ($event instanceof RepairFinishEvent) {
			$this->progressStateMax = $this->progressStateStep;
			$this->eventSource->send('success', $this->l10n->t('[%d / %d]: %s', [$this->progressStateStep, $this->progressStateMax, $this->currentStep]));
		} elseif ($event instanceof RepairStepEvent) {
			$this->eventSource->send('success', $this->l10n->t('Repair step:') . ' ' . $event->getStepName());
		} elseif ($event instanceof RepairInfoEvent) {
			$this->eventSource->send('success', $this->l10n->t('Repair info:') . ' ' . $event->getMessage());
		} elseif ($event instanceof RepairWarningEvent) {
			$this->eventSource->send('notice', $this->l10n->t('Repair warning:') . ' ' . $event->getMessage());
		} elseif ($event instanceof RepairErrorEvent) {
			$this->eventSource->send('error', $this->l10n->t('Repair error:') . ' ' . $event->getMessage());
		}
	}
}

if (\OCP\Util::needUpgrade()) {
	$config = \OC::$server->getSystemConfig();
	if ($config->getValue('upgrade.disable-web', false)) {
		$eventSource->send('failure', $l->t('Please use the command line updater because updating via browser is disabled in your config.php.'));
		$eventSource->close();
		exit();
	}

	// if a user is currently logged in, their session must be ignored to
	// avoid side effects
	\OC_User::setIncognitoMode(true);

	$config = Server::get(IConfig::class);
	$updater = new \OC\Updater(
		Server::get(\OCP\ServerVersion::class),
		$config,
		Server::get(IAppConfig::class),
		\OC::$server->getIntegrityCodeChecker(),
		Server::get(LoggerInterface::class),
		Server::get(\OC\Installer::class)
	);
	$incompatibleApps = [];
	$incompatibleOverwrites = $config->getSystemValue('app_install_overwrite', []);

	/** @var IEventDispatcher $dispatcher */
	$dispatcher = \OC::$server->get(IEventDispatcher::class);
	$dispatcher->addListener(
		MigratorExecuteSqlEvent::class,
		function (MigratorExecuteSqlEvent $event) use ($eventSource, $l): void {
			$eventSource->send('success', $l->t('[%d / %d]: %s', [$event->getCurrentStep(), $event->getMaxStep(), $event->getSql()]));
		}
	);
	$feedBack = new FeedBackHandler($eventSource, $l);
	$dispatcher->addListener(RepairStartEvent::class, [$feedBack, 'handleRepairFeedback']);
	$dispatcher->addListener(RepairAdvanceEvent::class, [$feedBack, 'handleRepairFeedback']);
	$dispatcher->addListener(RepairFinishEvent::class, [$feedBack, 'handleRepairFeedback']);
	$dispatcher->addListener(RepairStepEvent::class, [$feedBack, 'handleRepairFeedback']);
	$dispatcher->addListener(RepairInfoEvent::class, [$feedBack, 'handleRepairFeedback']);
	$dispatcher->addListener(RepairWarningEvent::class, [$feedBack, 'handleRepairFeedback']);
	$dispatcher->addListener(RepairErrorEvent::class, [$feedBack, 'handleRepairFeedback']);

	$updater->listen('\OC\Updater', 'maintenanceEnabled', function () use ($eventSource, $l) {
		$eventSource->send('success', $l->t('Turned on maintenance mode'));
	});
	$updater->listen('\OC\Updater', 'maintenanceDisabled', function () use ($eventSource, $l) {
		$eventSource->send('success', $l->t('Turned off maintenance mode'));
	});
	$updater->listen('\OC\Updater', 'maintenanceActive', function () use ($eventSource, $l) {
		$eventSource->send('success', $l->t('Maintenance mode is kept active'));
	});
	$updater->listen('\OC\Updater', 'dbUpgradeBefore', function () use ($eventSource, $l) {
		$eventSource->send('success', $l->t('Updating database schema'));
	});
	$updater->listen('\OC\Updater', 'dbUpgrade', function () use ($eventSource, $l) {
		$eventSource->send('success', $l->t('Updated database'));
	});
	$updater->listen('\OC\Updater', 'upgradeAppStoreApp', function ($app) use ($eventSource, $l) {
		$eventSource->send('success', $l->t('Update app "%s" from App Store', [$app]));
	});
	$updater->listen('\OC\Updater', 'appSimulateUpdate', function ($app) use ($eventSource, $l) {
		$eventSource->send('success', $l->t('Checking whether the database schema for %s can be updated (this can take a long time depending on the database size)', [$app]));
	});
	$updater->listen('\OC\Updater', 'appUpgrade', function ($app, $version) use ($eventSource, $l) {
		$eventSource->send('success', $l->t('Updated "%1$s" to %2$s', [$app, $version]));
	});
	$updater->listen('\OC\Updater', 'incompatibleAppDisabled', function ($app) use (&$incompatibleApps, &$incompatibleOverwrites) {
		if (!in_array($app, $incompatibleOverwrites)) {
			$incompatibleApps[] = $app;
		}
	});
	$updater->listen('\OC\Updater', 'failure', function ($message) use ($eventSource, $config) {
		$eventSource->send('failure', $message);
		$eventSource->close();
		$config->setSystemValue('maintenance', false);
	});
	$updater->listen('\OC\Updater', 'setDebugLogLevel', function ($logLevel, $logLevelName) use ($eventSource, $l) {
		$eventSource->send('success', $l->t('Set log level to debug'));
	});
	$updater->listen('\OC\Updater', 'resetLogLevel', function ($logLevel, $logLevelName) use ($eventSource, $l) {
		$eventSource->send('success', $l->t('Reset log level'));
	});
	$updater->listen('\OC\Updater', 'startCheckCodeIntegrity', function () use ($eventSource, $l) {
		$eventSource->send('success', $l->t('Starting code integrity check'));
	});
	$updater->listen('\OC\Updater', 'finishedCheckCodeIntegrity', function () use ($eventSource, $l) {
		$eventSource->send('success', $l->t('Finished code integrity check'));
	});

	try {
		$updater->upgrade();
	} catch (\Exception $e) {
		Server::get(LoggerInterface::class)->error(
			$e->getMessage(),
			[
				'exception' => $e,
				'app' => 'update',
			]);
		$eventSource->send('failure', get_class($e) . ': ' . $e->getMessage());
		$eventSource->close();
		exit();
	}

	$disabledApps = [];
	foreach ($incompatibleApps as $app) {
		$disabledApps[$app] = $l->t('%s (incompatible)', [$app]);
	}

	if (!empty($disabledApps)) {
		$eventSource->send('notice', $l->t('The following apps have been disabled: %s', [implode(', ', $disabledApps)]));
	}
} else {
	$eventSource->send('notice', $l->t('Already up to date'));
}

$eventSource->send('done', '');
$eventSource->close();
