<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Controller;

use OC\Core\Listener\FeedBackHandler;
use OC\DB\MigratorExecuteSqlEvent;
use OC\Repair\Events\RepairAdvanceEvent;
use OC\Repair\Events\RepairErrorEvent;
use OC\Repair\Events\RepairFinishEvent;
use OC\Repair\Events\RepairInfoEvent;
use OC\Repair\Events\RepairStartEvent;
use OC\Repair\Events\RepairStepEvent;
use OC\Repair\Events\RepairWarningEvent;
use OC\Updater;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\DataResponse;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IEventSourceFactory;
use OCP\IL10N;
use OCP\IRequest;
use OCP\Util;
use Psr\Log\LoggerInterface;

class UpdateController extends \OCP\AppFramework\OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly IEventSourceFactory $eventSourceFactory,
		private readonly IL10N $l,
		private readonly IConfig $config,
		private readonly Updater $updater,
		private readonly IEventDispatcher $dispatcher,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Update the server via the web interface
	 *
	 * @return DataResponse<Http::STATUS_OK, array{}, array{}>
	 * 200: Success
	 */
	#[ApiRoute(verb: 'GET', url: '/update', root: '/core')]
	#[PublicPage]
	public function update(): DataResponse {
		if (!str_contains(@ini_get('disable_functions'), 'set_time_limit')) {
			@set_time_limit(0);
		}

		\OC_User::setIncognitoMode(true);

		$eventSource = $this->eventSourceFactory->create();
		// need to send an initial message to force-init the event source,
		// which will then trigger its own CSRF check and produces its own CSRF error
		// message
		$eventSource->send('success', $this->l->t('Preparing update'));
		if (!Util::needUpgrade()) {
			$eventSource->send('notice', $this->l->t('Already up to date'));
			$eventSource->send('done', '');
			$eventSource->close();
			return new DataResponse([]);
		}

		if ($this->config->getSystemValueBool('upgrade.disable-web', false)) {
			$eventSource->send('failure', $this->l->t('Please use the command line updater because updating via browser is disabled in your config.php.'));
			$eventSource->close();
			return new DataResponse([]);
		}

		// if a user is currently logged in, their session must be ignored to
		// avoid side effects
		\OC_User::setIncognitoMode(true);

		$incompatibleApps = [];
		$incompatibleOverwrites = $this->config->getSystemValue('app_install_overwrite', []);

		$this->dispatcher->addListener(
			MigratorExecuteSqlEvent::class,
			function (MigratorExecuteSqlEvent $event) use ($eventSource): void {
				$eventSource->send('success', $this->l->t('[%d / %d]: %s', [$event->getCurrentStep(), $event->getMaxStep(), $event->getSql()]));
			}
		);
		$feedBack = new FeedBackHandler($eventSource, $this->l);
		$this->dispatcher->addListener(RepairStartEvent::class, $feedBack->handleRepairFeedback(...));
		$this->dispatcher->addListener(RepairAdvanceEvent::class, $feedBack->handleRepairFeedback(...));
		$this->dispatcher->addListener(RepairFinishEvent::class, $feedBack->handleRepairFeedback(...));
		$this->dispatcher->addListener(RepairStepEvent::class, $feedBack->handleRepairFeedback(...));
		$this->dispatcher->addListener(RepairInfoEvent::class, $feedBack->handleRepairFeedback(...));
		$this->dispatcher->addListener(RepairWarningEvent::class, $feedBack->handleRepairFeedback(...));
		$this->dispatcher->addListener(RepairErrorEvent::class, $feedBack->handleRepairFeedback(...));

		$this->updater->listen('\OC\Updater', 'maintenanceEnabled', function () use ($eventSource): void {
			$eventSource->send('success', $this->l->t('Turned on maintenance mode'));
		});
		$this->updater->listen('\OC\Updater', 'maintenanceDisabled', function () use ($eventSource): void {
			$eventSource->send('success', $this->l->t('Turned off maintenance mode'));
		});
		$this->updater->listen('\OC\Updater', 'maintenanceActive', function () use ($eventSource): void {
			$eventSource->send('success', $this->l->t('Maintenance mode is kept active'));
		});
		$this->updater->listen('\OC\Updater', 'dbUpgradeBefore', function () use ($eventSource): void {
			$eventSource->send('success', $this->l->t('Updating database schema'));
		});
		$this->updater->listen('\OC\Updater', 'dbUpgrade', function () use ($eventSource): void {
			$eventSource->send('success', $this->l->t('Updated database'));
		});
		$this->updater->listen('\OC\Updater', 'upgradeAppStoreApp', function ($app) use ($eventSource): void {
			$eventSource->send('success', $this->l->t('Update app "%s" from App Store', [$app]));
		});
		$this->updater->listen('\OC\Updater', 'appSimulateUpdate', function ($app) use ($eventSource): void {
			$eventSource->send('success', $this->l->t('Checking whether the database schema for %s can be updated (this can take a long time depending on the database size)', [$app]));
		});
		$this->updater->listen('\OC\Updater', 'appUpgrade', function ($app, $version) use ($eventSource): void {
			$eventSource->send('success', $this->l->t('Updated "%1$s" to %2$s', [$app, $version]));
		});
		$this->updater->listen('\OC\Updater', 'incompatibleAppDisabled', function ($app) use (&$incompatibleApps, &$incompatibleOverwrites): void {
			if (!in_array($app, $incompatibleOverwrites)) {
				$incompatibleApps[] = $app;
			}
		});
		$this->updater->listen('\OC\Updater', 'failure', function ($message) use ($eventSource): void {
			$eventSource->send('failure', $message);
			$this->config->setSystemValue('maintenance', false);
		});
		$this->updater->listen('\OC\Updater', 'setDebugLogLevel', function ($logLevel, $logLevelName) use ($eventSource): void {
			$eventSource->send('success', $this->l->t('Set log level to debug'));
		});
		$this->updater->listen('\OC\Updater', 'resetLogLevel', function ($logLevel, $logLevelName) use ($eventSource): void {
			$eventSource->send('success', $this->l->t('Reset log level'));
		});
		$this->updater->listen('\OC\Updater', 'startCheckCodeIntegrity', function () use ($eventSource): void {
			$eventSource->send('success', $this->l->t('Starting code integrity check'));
		});
		$this->updater->listen('\OC\Updater', 'finishedCheckCodeIntegrity', function () use ($eventSource): void {
			$eventSource->send('success', $this->l->t('Finished code integrity check'));
		});

		try {
			$this->updater->upgrade();
		} catch (\Exception $e) {
			$this->logger->error(
				$e->getMessage(),
				[
					'exception' => $e,
					'app' => 'update',
				]);
			$eventSource->send('failure', get_class($e) . ': ' . $e->getMessage());
			$eventSource->close();
			return new DataResponse([]);
		}

		$disabledApps = [];
		foreach ($incompatibleApps as $app) {
			$disabledApps[$app] = $this->l->t('%s (incompatible)', [$app]);
		}

		if (!empty($disabledApps)) {
			$eventSource->send('notice', $this->l->t('The following apps have been disabled: %s', [implode(', ', $disabledApps)]));
		}

		$eventSource->send('done', '');
		$eventSource->close();
		return new DataResponse([]);
	}
}
