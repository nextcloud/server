<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @copyright Copyright (c) 2016, Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Frank Karlitschek <frank@karlitschek.de>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author J0WI <J0WI@users.noreply.github.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Steffen Lindner <mail@steffen-lindner.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC;

use OC\App\AppManager;
use OC\DB\Connection;
use OC\DB\MigrationService;
use OC\DB\MigratorExecuteSqlEvent;
use OC\Hooks\BasicEmitter;
use OC\IntegrityCheck\Checker;
use OC\Repair\Events\RepairAdvanceEvent;
use OC\Repair\Events\RepairErrorEvent;
use OC\Repair\Events\RepairFinishEvent;
use OC\Repair\Events\RepairInfoEvent;
use OC\Repair\Events\RepairStartEvent;
use OC\Repair\Events\RepairStepEvent;
use OC\Repair\Events\RepairWarningEvent;
use OC_App;
use OCP\App\IAppManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\HintException;
use OCP\IConfig;
use OCP\ILogger;
use OCP\Util;
use Psr\Log\LoggerInterface;

/**
 * Class that handles autoupdating of ownCloud
 *
 * Hooks provided in scope \OC\Updater
 *  - maintenanceStart()
 *  - maintenanceEnd()
 *  - dbUpgrade()
 *  - failure(string $message)
 */
class Updater extends BasicEmitter {
	/** @var LoggerInterface */
	private $log;

	/** @var IConfig */
	private $config;

	/** @var Checker */
	private $checker;

	/** @var Installer */
	private $installer;

	private $logLevelNames = [
		0 => 'Debug',
		1 => 'Info',
		2 => 'Warning',
		3 => 'Error',
		4 => 'Fatal',
	];

	public function __construct(IConfig $config,
		Checker $checker,
		?LoggerInterface $log,
		Installer $installer) {
		$this->log = $log;
		$this->config = $config;
		$this->checker = $checker;
		$this->installer = $installer;
	}

	/**
	 * runs the update actions in maintenance mode, does not upgrade the source files
	 * except the main .htaccess file
	 *
	 * @return bool true if the operation succeeded, false otherwise
	 */
	public function upgrade(): bool {
		$this->logAllEvents();

		$logLevel = $this->config->getSystemValue('loglevel', ILogger::WARN);
		$this->emit('\OC\Updater', 'setDebugLogLevel', [ $logLevel, $this->logLevelNames[$logLevel] ]);
		$this->config->setSystemValue('loglevel', ILogger::DEBUG);

		$wasMaintenanceModeEnabled = $this->config->getSystemValueBool('maintenance');

		if (!$wasMaintenanceModeEnabled) {
			$this->config->setSystemValue('maintenance', true);
			$this->emit('\OC\Updater', 'maintenanceEnabled');
		}

		// Clear CAN_INSTALL file if not on git
		if (\OC_Util::getChannel() !== 'git' && is_file(\OC::$configDir.'/CAN_INSTALL')) {
			if (!unlink(\OC::$configDir . '/CAN_INSTALL')) {
				$this->log->error('Could not cleanup CAN_INSTALL from your config folder. Please remove this file manually.');
			}
		}

		$installedVersion = $this->config->getSystemValueString('version', '0.0.0');
		$currentVersion = implode('.', \OCP\Util::getVersion());

		$this->log->debug('starting upgrade from ' . $installedVersion . ' to ' . $currentVersion, ['app' => 'core']);

		$success = true;
		try {
			$this->doUpgrade($currentVersion, $installedVersion);
		} catch (HintException $exception) {
			$this->log->error($exception->getMessage(), [
				'exception' => $exception,
			]);
			$this->emit('\OC\Updater', 'failure', [$exception->getMessage() . ': ' .$exception->getHint()]);
			$success = false;
		} catch (\Exception $exception) {
			$this->log->error($exception->getMessage(), [
				'exception' => $exception,
			]);
			$this->emit('\OC\Updater', 'failure', [get_class($exception) . ': ' .$exception->getMessage()]);
			$success = false;
		}

		$this->emit('\OC\Updater', 'updateEnd', [$success]);

		if (!$wasMaintenanceModeEnabled && $success) {
			$this->config->setSystemValue('maintenance', false);
			$this->emit('\OC\Updater', 'maintenanceDisabled');
		} else {
			$this->emit('\OC\Updater', 'maintenanceActive');
		}

		$this->emit('\OC\Updater', 'resetLogLevel', [ $logLevel, $this->logLevelNames[$logLevel] ]);
		$this->config->setSystemValue('loglevel', $logLevel);
		$this->config->setSystemValue('installed', true);

		return $success;
	}

	/**
	 * Return version from which this version is allowed to upgrade from
	 *
	 * @return array allowed previous versions per vendor
	 */
	private function getAllowedPreviousVersions(): array {
		// this should really be a JSON file
		require \OC::$SERVERROOT . '/version.php';
		/** @var array $OC_VersionCanBeUpgradedFrom */
		return $OC_VersionCanBeUpgradedFrom;
	}

	/**
	 * Return vendor from which this version was published
	 *
	 * @return string Get the vendor
	 */
	private function getVendor(): string {
		// this should really be a JSON file
		require \OC::$SERVERROOT . '/version.php';
		/** @var string $vendor */
		return (string) $vendor;
	}

	/**
	 * Whether an upgrade to a specified version is possible
	 * @param string $oldVersion
	 * @param string $newVersion
	 * @param array $allowedPreviousVersions
	 * @return bool
	 */
	public function isUpgradePossible(string $oldVersion, string $newVersion, array $allowedPreviousVersions): bool {
		$version = explode('.', $oldVersion);
		$majorMinor = $version[0] . '.' . $version[1];

		$currentVendor = $this->config->getAppValue('core', 'vendor', '');

		// Vendor was not set correctly on install, so we have to white-list known versions
		if ($currentVendor === '' && (
			isset($allowedPreviousVersions['owncloud'][$oldVersion]) ||
			isset($allowedPreviousVersions['owncloud'][$majorMinor])
		)) {
			$currentVendor = 'owncloud';
			$this->config->setAppValue('core', 'vendor', $currentVendor);
		}

		if ($currentVendor === 'nextcloud') {
			return isset($allowedPreviousVersions[$currentVendor][$majorMinor])
				&& (version_compare($oldVersion, $newVersion, '<=') ||
					$this->config->getSystemValueBool('debug', false));
		}

		// Check if the instance can be migrated
		return isset($allowedPreviousVersions[$currentVendor][$majorMinor]) ||
			isset($allowedPreviousVersions[$currentVendor][$oldVersion]);
	}

	/**
	 * runs the update actions in maintenance mode, does not upgrade the source files
	 * except the main .htaccess file
	 *
	 * @param string $currentVersion current version to upgrade to
	 * @param string $installedVersion previous version from which to upgrade from
	 *
	 * @throws \Exception
	 */
	private function doUpgrade(string $currentVersion, string $installedVersion): void {
		// Stop update if the update is over several major versions
		$allowedPreviousVersions = $this->getAllowedPreviousVersions();
		if (!$this->isUpgradePossible($installedVersion, $currentVersion, $allowedPreviousVersions)) {
			throw new \Exception('Updates between multiple major versions and downgrades are unsupported.');
		}

		// Update .htaccess files
		try {
			Setup::updateHtaccess();
			Setup::protectDataDirectory();
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage());
		}

		// create empty file in data dir, so we can later find
		// out that this is indeed an ownCloud data directory
		// (in case it didn't exist before)
		file_put_contents($this->config->getSystemValueString('datadirectory', \OC::$SERVERROOT . '/data') . '/.ocdata', '');

		// pre-upgrade repairs
		$repair = new Repair(Repair::getBeforeUpgradeRepairSteps(), \OC::$server->get(\OCP\EventDispatcher\IEventDispatcher::class), \OC::$server->get(LoggerInterface::class));
		$repair->run();

		$this->doCoreUpgrade();

		try {
			// TODO: replace with the new repair step mechanism https://github.com/owncloud/core/pull/24378
			Setup::installBackgroundJobs();
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage());
		}

		// update all shipped apps
		$this->checkAppsRequirements();
		$this->doAppUpgrade();

		// Update the appfetchers version so it downloads the correct list from the appstore
		\OC::$server->getAppFetcher()->setVersion($currentVersion);

		/** @var AppManager $appManager */
		$appManager = \OC::$server->getAppManager();

		// upgrade appstore apps
		$this->upgradeAppStoreApps($appManager->getInstalledApps());
		$autoDisabledApps = $appManager->getAutoDisabledApps();
		if (!empty($autoDisabledApps)) {
			$this->upgradeAppStoreApps(array_keys($autoDisabledApps), $autoDisabledApps);
		}

		// install new shipped apps on upgrade
		$errors = Installer::installShippedApps(true);
		foreach ($errors as $appId => $exception) {
			/** @var \Exception $exception */
			$this->log->error($exception->getMessage(), [
				'exception' => $exception,
				'app' => $appId,
			]);
			$this->emit('\OC\Updater', 'failure', [$appId . ': ' . $exception->getMessage()]);
		}

		// post-upgrade repairs
		$repair = new Repair(Repair::getRepairSteps(), \OC::$server->get(\OCP\EventDispatcher\IEventDispatcher::class), \OC::$server->get(LoggerInterface::class));
		$repair->run();

		//Invalidate update feed
		$this->config->setAppValue('core', 'lastupdatedat', '0');

		// Check for code integrity if not disabled
		if (\OC::$server->getIntegrityCodeChecker()->isCodeCheckEnforced()) {
			$this->emit('\OC\Updater', 'startCheckCodeIntegrity');
			$this->checker->runInstanceVerification();
			$this->emit('\OC\Updater', 'finishedCheckCodeIntegrity');
		}

		// only set the final version if everything went well
		$this->config->setSystemValue('version', implode('.', Util::getVersion()));
		$this->config->setAppValue('core', 'vendor', $this->getVendor());
	}

	protected function doCoreUpgrade(): void {
		$this->emit('\OC\Updater', 'dbUpgradeBefore');

		// execute core migrations
		$ms = new MigrationService('core', \OC::$server->get(Connection::class));
		$ms->migrate();

		$this->emit('\OC\Updater', 'dbUpgrade');
	}

	/**
	 * upgrades all apps within a major ownCloud upgrade. Also loads "priority"
	 * (types authentication, filesystem, logging, in that order) afterwards.
	 *
	 * @throws NeedsUpdateException
	 */
	protected function doAppUpgrade(): void {
		$apps = \OC_App::getEnabledApps();
		$priorityTypes = ['authentication', 'extended_authentication', 'filesystem', 'logging'];
		$pseudoOtherType = 'other';
		$stacks = [$pseudoOtherType => []];

		foreach ($apps as $appId) {
			$priorityType = false;
			foreach ($priorityTypes as $type) {
				if (!isset($stacks[$type])) {
					$stacks[$type] = [];
				}
				if (\OC_App::isType($appId, [$type])) {
					$stacks[$type][] = $appId;
					$priorityType = true;
					break;
				}
			}
			if (!$priorityType) {
				$stacks[$pseudoOtherType][] = $appId;
			}
		}
		foreach (array_merge($priorityTypes, [$pseudoOtherType]) as $type) {
			$stack = $stacks[$type];
			foreach ($stack as $appId) {
				if (\OC_App::shouldUpgrade($appId)) {
					$this->emit('\OC\Updater', 'appUpgradeStarted', [$appId, \OCP\Server::get(IAppManager::class)->getAppVersion($appId)]);
					\OC_App::updateApp($appId);
					$this->emit('\OC\Updater', 'appUpgrade', [$appId, \OCP\Server::get(IAppManager::class)->getAppVersion($appId)]);
				}
				if ($type !== $pseudoOtherType) {
					// load authentication, filesystem and logging apps after
					// upgrading them. Other apps my need to rely on modifying
					// user and/or filesystem aspects.
					\OC_App::loadApp($appId);
				}
			}
		}
	}

	/**
	 * check if the current enabled apps are compatible with the current
	 * ownCloud version. disable them if not.
	 * This is important if you upgrade ownCloud and have non ported 3rd
	 * party apps installed.
	 *
	 * @throws \Exception
	 */
	private function checkAppsRequirements(): void {
		$isCoreUpgrade = $this->isCodeUpgrade();
		$apps = OC_App::getEnabledApps();
		$version = implode('.', Util::getVersion());
		$appManager = \OC::$server->getAppManager();
		foreach ($apps as $app) {
			// check if the app is compatible with this version of Nextcloud
			$info = $appManager->getAppInfo($app);
			if ($info === null || !OC_App::isAppCompatible($version, $info)) {
				if ($appManager->isShipped($app)) {
					throw new \UnexpectedValueException('The files of the app "' . $app . '" were not correctly replaced before running the update');
				}
				$appManager->disableApp($app, true);
				$this->emit('\OC\Updater', 'incompatibleAppDisabled', [$app]);
			}
		}
	}

	/**
	 * @return bool
	 */
	private function isCodeUpgrade(): bool {
		$installedVersion = $this->config->getSystemValueString('version', '0.0.0');
		$currentVersion = implode('.', Util::getVersion());
		if (version_compare($currentVersion, $installedVersion, '>')) {
			return true;
		}
		return false;
	}

	/**
	 * @param array $apps
	 * @param array $previousEnableStates
	 * @throws \Exception
	 */
	private function upgradeAppStoreApps(array $apps, array $previousEnableStates = []): void {
		foreach ($apps as $app) {
			try {
				$this->emit('\OC\Updater', 'checkAppStoreAppBefore', [$app]);
				if ($this->installer->isUpdateAvailable($app)) {
					$this->emit('\OC\Updater', 'upgradeAppStoreApp', [$app]);
					$this->installer->updateAppstoreApp($app);
				}
				$this->emit('\OC\Updater', 'checkAppStoreApp', [$app]);

				if (!empty($previousEnableStates)) {
					$ocApp = new \OC_App();
					if (!empty($previousEnableStates[$app]) && is_array($previousEnableStates[$app])) {
						$ocApp->enable($app, $previousEnableStates[$app]);
					} else {
						$ocApp->enable($app);
					}
				}
			} catch (\Exception $ex) {
				$this->log->error($ex->getMessage(), [
					'exception' => $ex,
				]);
			}
		}
	}

	private function logAllEvents(): void {
		$log = $this->log;

		/** @var IEventDispatcher $dispatcher */
		$dispatcher = \OC::$server->get(IEventDispatcher::class);
		$dispatcher->addListener(
			MigratorExecuteSqlEvent::class,
			function (MigratorExecuteSqlEvent $event) use ($log): void {
				$log->info(get_class($event).': ' . $event->getSql() . ' (' . $event->getCurrentStep() . ' of ' . $event->getMaxStep() . ')', ['app' => 'updater']);
			}
		);

		$repairListener = function (Event $event) use ($log): void {
			if ($event instanceof RepairStartEvent) {
				$log->info(get_class($event).': Starting ... ' . $event->getMaxStep() .  ' (' . $event->getCurrentStepName() . ')', ['app' => 'updater']);
			} elseif ($event instanceof RepairAdvanceEvent) {
				$desc = $event->getDescription();
				if (empty($desc)) {
					$desc = '';
				}
				$log->info(get_class($event).': ' . $desc . ' (' . $event->getIncrement() . ')', ['app' => 'updater']);
			} elseif ($event instanceof RepairFinishEvent) {
				$log->info(get_class($event), ['app' => 'updater']);
			} elseif ($event instanceof RepairStepEvent) {
				$log->info(get_class($event).': Repair step: ' . $event->getStepName(), ['app' => 'updater']);
			} elseif ($event instanceof RepairInfoEvent) {
				$log->info(get_class($event).': Repair info: ' . $event->getMessage(), ['app' => 'updater']);
			} elseif ($event instanceof RepairWarningEvent) {
				$log->warning(get_class($event).': Repair warning: ' . $event->getMessage(), ['app' => 'updater']);
			} elseif ($event instanceof RepairErrorEvent) {
				$log->error(get_class($event).': Repair error: ' . $event->getMessage(), ['app' => 'updater']);
			}
		};

		$dispatcher->addListener(RepairStartEvent::class, $repairListener);
		$dispatcher->addListener(RepairAdvanceEvent::class, $repairListener);
		$dispatcher->addListener(RepairFinishEvent::class, $repairListener);
		$dispatcher->addListener(RepairStepEvent::class, $repairListener);
		$dispatcher->addListener(RepairInfoEvent::class, $repairListener);
		$dispatcher->addListener(RepairWarningEvent::class, $repairListener);
		$dispatcher->addListener(RepairErrorEvent::class, $repairListener);


		$this->listen('\OC\Updater', 'maintenanceEnabled', function () use ($log) {
			$log->info('\OC\Updater::maintenanceEnabled: Turned on maintenance mode', ['app' => 'updater']);
		});
		$this->listen('\OC\Updater', 'maintenanceDisabled', function () use ($log) {
			$log->info('\OC\Updater::maintenanceDisabled: Turned off maintenance mode', ['app' => 'updater']);
		});
		$this->listen('\OC\Updater', 'maintenanceActive', function () use ($log) {
			$log->info('\OC\Updater::maintenanceActive: Maintenance mode is kept active', ['app' => 'updater']);
		});
		$this->listen('\OC\Updater', 'updateEnd', function ($success) use ($log) {
			if ($success) {
				$log->info('\OC\Updater::updateEnd: Update successful', ['app' => 'updater']);
			} else {
				$log->error('\OC\Updater::updateEnd: Update failed', ['app' => 'updater']);
			}
		});
		$this->listen('\OC\Updater', 'dbUpgradeBefore', function () use ($log) {
			$log->info('\OC\Updater::dbUpgradeBefore: Updating database schema', ['app' => 'updater']);
		});
		$this->listen('\OC\Updater', 'dbUpgrade', function () use ($log) {
			$log->info('\OC\Updater::dbUpgrade: Updated database', ['app' => 'updater']);
		});
		$this->listen('\OC\Updater', 'incompatibleAppDisabled', function ($app) use ($log) {
			$log->info('\OC\Updater::incompatibleAppDisabled: Disabled incompatible app: ' . $app, ['app' => 'updater']);
		});
		$this->listen('\OC\Updater', 'checkAppStoreAppBefore', function ($app) use ($log) {
			$log->debug('\OC\Updater::checkAppStoreAppBefore: Checking for update of app "' . $app . '" in appstore', ['app' => 'updater']);
		});
		$this->listen('\OC\Updater', 'upgradeAppStoreApp', function ($app) use ($log) {
			$log->info('\OC\Updater::upgradeAppStoreApp: Update app "' . $app . '" from appstore', ['app' => 'updater']);
		});
		$this->listen('\OC\Updater', 'checkAppStoreApp', function ($app) use ($log) {
			$log->debug('\OC\Updater::checkAppStoreApp: Checked for update of app "' . $app . '" in appstore', ['app' => 'updater']);
		});
		$this->listen('\OC\Updater', 'appSimulateUpdate', function ($app) use ($log) {
			$log->info('\OC\Updater::appSimulateUpdate: Checking whether the database schema for <' . $app . '> can be updated (this can take a long time depending on the database size)', ['app' => 'updater']);
		});
		$this->listen('\OC\Updater', 'appUpgradeStarted', function ($app) use ($log) {
			$log->info('\OC\Updater::appUpgradeStarted: Updating <' . $app . '> ...', ['app' => 'updater']);
		});
		$this->listen('\OC\Updater', 'appUpgrade', function ($app, $version) use ($log) {
			$log->info('\OC\Updater::appUpgrade: Updated <' . $app . '> to ' . $version, ['app' => 'updater']);
		});
		$this->listen('\OC\Updater', 'failure', function ($message) use ($log) {
			$log->error('\OC\Updater::failure: ' . $message, ['app' => 'updater']);
		});
		$this->listen('\OC\Updater', 'setDebugLogLevel', function () use ($log) {
			$log->info('\OC\Updater::setDebugLogLevel: Set log level to debug', ['app' => 'updater']);
		});
		$this->listen('\OC\Updater', 'resetLogLevel', function ($logLevel, $logLevelName) use ($log) {
			$log->info('\OC\Updater::resetLogLevel: Reset log level to ' . $logLevelName . '(' . $logLevel . ')', ['app' => 'updater']);
		});
		$this->listen('\OC\Updater', 'startCheckCodeIntegrity', function () use ($log) {
			$log->info('\OC\Updater::startCheckCodeIntegrity: Starting code integrity check...', ['app' => 'updater']);
		});
		$this->listen('\OC\Updater', 'finishedCheckCodeIntegrity', function () use ($log) {
			$log->info('\OC\Updater::finishedCheckCodeIntegrity: Finished code integrity check', ['app' => 'updater']);
		});
	}
}
