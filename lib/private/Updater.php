<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Frank Karlitschek <frank@karlitschek.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Steffen Lindner <mail@steffen-lindner.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 * @author Vincent Petry <pvince81@owncloud.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC;

use OC\Hooks\BasicEmitter;
use OC\IntegrityCheck\Checker;
use OC_App;
use OCP\IConfig;
use OC\Setup;
use OCP\ILogger;
use Symfony\Component\EventDispatcher\GenericEvent;

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

	/** @var ILogger $log */
	private $log;
	
	/** @var IConfig */
	private $config;

	/** @var Checker */
	private $checker;

	/** @var bool */
	private $simulateStepEnabled;

	/** @var bool */
	private $updateStepEnabled;

	/** @var bool */
	private $skip3rdPartyAppsDisable;

	private $logLevelNames = [
		0 => 'Debug',
		1 => 'Info',
		2 => 'Warning',
		3 => 'Error',
		4 => 'Fatal',
	];

	/**
	 * @param IConfig $config
	 * @param Checker $checker
	 * @param ILogger $log
	 */
	public function __construct(IConfig $config,
								Checker $checker,
								ILogger $log = null) {
		$this->log = $log;
		$this->config = $config;
		$this->checker = $checker;
		$this->simulateStepEnabled = true;
		$this->updateStepEnabled = true;
	}

	/**
	 * Sets whether the database migration simulation must
	 * be enabled.
	 * This can be set to false to skip this test.
	 *
	 * @param bool $flag true to enable simulation, false otherwise
	 */
	public function setSimulateStepEnabled($flag) {
		$this->simulateStepEnabled = $flag;
	}

	/**
	 * Sets whether the update must be performed.
	 * This can be set to false to skip the actual update.
	 *
	 * @param bool $flag true to enable update, false otherwise
	 */
	public function setUpdateStepEnabled($flag) {
		$this->updateStepEnabled = $flag;
	}

	/**
	 * Sets whether the update disables 3rd party apps.
	 * This can be set to true to skip the disable.
	 *
	 * @param bool $flag false to not disable, true otherwise
	 */
	public function setSkip3rdPartyAppsDisable($flag) {
		$this->skip3rdPartyAppsDisable = $flag;
	}

	/**
	 * runs the update actions in maintenance mode, does not upgrade the source files
	 * except the main .htaccess file
	 *
	 * @return bool true if the operation succeeded, false otherwise
	 */
	public function upgrade() {
		$this->emitRepairEvents();

		$logLevel = $this->config->getSystemValue('loglevel', \OCP\Util::WARN);
		$this->emit('\OC\Updater', 'setDebugLogLevel', [ $logLevel, $this->logLevelNames[$logLevel] ]);
		$this->config->setSystemValue('loglevel', \OCP\Util::DEBUG);

		$wasMaintenanceModeEnabled = $this->config->getSystemValue('maintenance', false);

		if(!$wasMaintenanceModeEnabled) {
			$this->config->setSystemValue('maintenance', true);
			$this->emit('\OC\Updater', 'maintenanceEnabled');
		}

		$installedVersion = $this->config->getSystemValue('version', '0.0.0');
		$currentVersion = implode('.', \OCP\Util::getVersion());
		$this->log->debug('starting upgrade from ' . $installedVersion . ' to ' . $currentVersion, array('app' => 'core'));

		$success = true;
		try {
			$this->doUpgrade($currentVersion, $installedVersion);
		} catch (HintException $exception) {
			$this->log->logException($exception, ['app' => 'core']);
			$this->emit('\OC\Updater', 'failure', array($exception->getMessage() . ': ' .$exception->getHint()));
			$success = false;
		} catch (\Exception $exception) {
			$this->log->logException($exception, ['app' => 'core']);
			$this->emit('\OC\Updater', 'failure', array(get_class($exception) . ': ' .$exception->getMessage()));
			$success = false;
		}

		$this->emit('\OC\Updater', 'updateEnd', array($success));

		if(!$wasMaintenanceModeEnabled && $success) {
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
	 * @return string allowed previous version
	 */
	private function getAllowedPreviousVersion() {
		// this should really be a JSON file
		require \OC::$SERVERROOT . '/version.php';
		/** @var array $OC_VersionCanBeUpgradedFrom */
		return implode('.', $OC_VersionCanBeUpgradedFrom);
	}

	/**
	 * Return vendor from which this version was published
	 *
	 * @return string Get the vendor
	 */
	private function getVendor() {
		// this should really be a JSON file
		require \OC::$SERVERROOT . '/version.php';
		/** @var string $vendor */
		return (string) $vendor;
	}

	/**
	 * Whether an upgrade to a specified version is possible
	 * @param string $oldVersion
	 * @param string $newVersion
	 * @param string $allowedPreviousVersion
	 * @return bool
	 */
	public function isUpgradePossible($oldVersion, $newVersion, $allowedPreviousVersion) {
		$allowedUpgrade = (version_compare($allowedPreviousVersion, $oldVersion, '<=')
			&& (version_compare($oldVersion, $newVersion, '<=') || $this->config->getSystemValue('debug', false)));

		if ($allowedUpgrade) {
			return $allowedUpgrade;
		}

		// Upgrade not allowed, someone switching vendor?
		if ($this->getVendor() !== $this->config->getAppValue('core', 'vendor', '')) {
			$oldVersion = explode('.', $oldVersion);
			$newVersion = explode('.', $newVersion);

			return $oldVersion[0] === $newVersion[0] && $oldVersion[1] === $newVersion[1];
		}

		return false;
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
	private function doUpgrade($currentVersion, $installedVersion) {
		// Stop update if the update is over several major versions
		$allowedPreviousVersion = $this->getAllowedPreviousVersion();
		if (!self::isUpgradePossible($installedVersion, $currentVersion, $allowedPreviousVersion)) {
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
		file_put_contents($this->config->getSystemValue('datadirectory', \OC::$SERVERROOT . '/data') . '/.ocdata', '');

		// pre-upgrade repairs
		$repair = new Repair(Repair::getBeforeUpgradeRepairSteps(), \OC::$server->getEventDispatcher());
		$repair->run();

		// simulate DB upgrade
		if ($this->simulateStepEnabled) {
			$this->checkCoreUpgrade();

			// simulate apps DB upgrade
			$this->checkAppUpgrade($currentVersion);

		}

		if ($this->updateStepEnabled) {
			$this->doCoreUpgrade();

			try {
				// TODO: replace with the new repair step mechanism https://github.com/owncloud/core/pull/24378
				Setup::installBackgroundJobs();
			} catch (\Exception $e) {
				throw new \Exception($e->getMessage());
			}

			// update all shipped apps
			$disabledApps = $this->checkAppsRequirements();
			$this->doAppUpgrade();

			// upgrade appstore apps
			$this->upgradeAppStoreApps($disabledApps);

			// install new shipped apps on upgrade
			OC_App::loadApps('authentication');
			$errors = Installer::installShippedApps(true);
			foreach ($errors as $appId => $exception) {
				/** @var \Exception $exception */
				$this->log->logException($exception, ['app' => $appId]);
				$this->emit('\OC\Updater', 'failure', [$appId . ': ' . $exception->getMessage()]);
			}

			// post-upgrade repairs
			$repair = new Repair(Repair::getRepairSteps(), \OC::$server->getEventDispatcher());
			$repair->run();

			//Invalidate update feed
			$this->config->setAppValue('core', 'lastupdatedat', 0);

			// Check for code integrity if not disabled
			if(\OC::$server->getIntegrityCodeChecker()->isCodeCheckEnforced()) {
				$this->emit('\OC\Updater', 'startCheckCodeIntegrity');
				$this->checker->runInstanceVerification();
				$this->emit('\OC\Updater', 'finishedCheckCodeIntegrity');
			}

			// only set the final version if everything went well
			$this->config->setSystemValue('version', implode('.', \OCP\Util::getVersion()));
			$this->config->setAppValue('core', 'vendor', $this->getVendor());
		}
	}

	protected function checkCoreUpgrade() {
		$this->emit('\OC\Updater', 'dbSimulateUpgradeBefore');

		// simulate core DB upgrade
		\OC_DB::simulateUpdateDbFromStructure(\OC::$SERVERROOT . '/db_structure.xml');

		$this->emit('\OC\Updater', 'dbSimulateUpgrade');
	}

	protected function doCoreUpgrade() {
		$this->emit('\OC\Updater', 'dbUpgradeBefore');

		// do the real upgrade
		\OC_DB::updateDbFromStructure(\OC::$SERVERROOT . '/db_structure.xml');

		$this->emit('\OC\Updater', 'dbUpgrade');
	}

	/**
	 * @param string $version the oc version to check app compatibility with
	 */
	protected function checkAppUpgrade($version) {
		$apps = \OC_App::getEnabledApps();
		$this->emit('\OC\Updater', 'appUpgradeCheckBefore');

		foreach ($apps as $appId) {
			$info = \OC_App::getAppInfo($appId);
			$compatible = \OC_App::isAppCompatible($version, $info);
			$isShipped = \OC_App::isShipped($appId);

			if ($compatible && $isShipped && \OC_App::shouldUpgrade($appId)) {
				/**
				 * FIXME: The preupdate check is performed before the database migration, otherwise database changes
				 * are not possible anymore within it. - Consider this when touching the code.
				 * @link https://github.com/owncloud/core/issues/10980
				 * @see \OC_App::updateApp
				 */
				if (file_exists(\OC_App::getAppPath($appId) . '/appinfo/preupdate.php')) {
					$this->includePreUpdate($appId);
				}
				if (file_exists(\OC_App::getAppPath($appId) . '/appinfo/database.xml')) {
					$this->emit('\OC\Updater', 'appSimulateUpdate', array($appId));
					\OC_DB::simulateUpdateDbFromStructure(\OC_App::getAppPath($appId) . '/appinfo/database.xml');
				}
			}
		}

		$this->emit('\OC\Updater', 'appUpgradeCheck');
	}

	/**
	 * Includes the pre-update file. Done here to prevent namespace mixups.
	 * @param string $appId
	 */
	private function includePreUpdate($appId) {
		include \OC_App::getAppPath($appId) . '/appinfo/preupdate.php';
	}

	/**
	 * upgrades all apps within a major ownCloud upgrade. Also loads "priority"
	 * (types authentication, filesystem, logging, in that order) afterwards.
	 *
	 * @throws NeedsUpdateException
	 */
	protected function doAppUpgrade() {
		$apps = \OC_App::getEnabledApps();
		$priorityTypes = array('authentication', 'filesystem', 'logging');
		$pseudoOtherType = 'other';
		$stacks = array($pseudoOtherType => array());

		foreach ($apps as $appId) {
			$priorityType = false;
			foreach ($priorityTypes as $type) {
				if(!isset($stacks[$type])) {
					$stacks[$type] = array();
				}
				if (\OC_App::isType($appId, $type)) {
					$stacks[$type][] = $appId;
					$priorityType = true;
					break;
				}
			}
			if (!$priorityType) {
				$stacks[$pseudoOtherType][] = $appId;
			}
		}
		foreach ($stacks as $type => $stack) {
			foreach ($stack as $appId) {
				if (\OC_App::shouldUpgrade($appId)) {
					$this->emit('\OC\Updater', 'appUpgradeStarted', [$appId, \OC_App::getAppVersion($appId)]);
					\OC_App::updateApp($appId);
					$this->emit('\OC\Updater', 'appUpgrade', [$appId, \OC_App::getAppVersion($appId)]);
				}
				if($type !== $pseudoOtherType) {
					// load authentication, filesystem and logging apps after
					// upgrading them. Other apps my need to rely on modifying
					// user and/or filesystem aspects.
					\OC_App::loadApp($appId, false);
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
	 * @return array
	 * @throws \Exception
	 */
	private function checkAppsRequirements() {
		$isCoreUpgrade = $this->isCodeUpgrade();
		$apps = OC_App::getEnabledApps();
		$version = \OCP\Util::getVersion();
		$disabledApps = [];
		foreach ($apps as $app) {
			// check if the app is compatible with this version of ownCloud
			$info = OC_App::getAppInfo($app);
			if(!OC_App::isAppCompatible($version, $info)) {
				if (OC_App::isShipped($app)) {
					throw new \UnexpectedValueException('The files of the app "' . $app . '" were not correctly replaced before running the update');
				}
				OC_App::disable($app);
				$this->emit('\OC\Updater', 'incompatibleAppDisabled', array($app));
			}
			// no need to disable any app in case this is a non-core upgrade
			if (!$isCoreUpgrade) {
				continue;
			}
			// shipped apps will remain enabled
			if (OC_App::isShipped($app)) {
				continue;
			}
			// authentication and session apps will remain enabled as well
			if (OC_App::isType($app, ['session', 'authentication'])) {
				continue;
			}

			// disable any other 3rd party apps if not overriden
			if(!$this->skip3rdPartyAppsDisable) {
				\OC_App::disable($app);
				$disabledApps[]= $app;
				$this->emit('\OC\Updater', 'thirdPartyAppDisabled', array($app));
			};
		}
		return $disabledApps;
	}

	/**
	 * @return bool
	 */
	private function isCodeUpgrade() {
		$installedVersion = $this->config->getSystemValue('version', '0.0.0');
		$currentVersion = implode('.', \OCP\Util::getVersion());
		if (version_compare($currentVersion, $installedVersion, '>')) {
			return true;
		}
		return false;
	}

	/**
	 * @param array $disabledApps
	 * @throws \Exception
	 */
	private function upgradeAppStoreApps(array $disabledApps) {
		foreach($disabledApps as $app) {
			try {
				if (Installer::isUpdateAvailable($app)) {
					$ocsId = \OC::$server->getConfig()->getAppValue($app, 'ocsid', '');

					$this->emit('\OC\Updater', 'upgradeAppStoreApp', array($app));
					Installer::updateAppByOCSId($ocsId);
				}
			} catch (\Exception $ex) {
				$this->log->logException($ex, ['app' => 'core']);
			}
		}
	}

	/**
	 * Forward messages emitted by the repair routine
	 */
	private function emitRepairEvents() {
		$dispatcher = \OC::$server->getEventDispatcher();
		$dispatcher->addListener('\OC\Repair::warning', function ($event) {
			if ($event instanceof GenericEvent) {
				$this->emit('\OC\Updater', 'repairWarning', $event->getArguments());
			}
		});
		$dispatcher->addListener('\OC\Repair::error', function ($event) {
			if ($event instanceof GenericEvent) {
				$this->emit('\OC\Updater', 'repairError', $event->getArguments());
			}
		});
		$dispatcher->addListener('\OC\Repair::info', function ($event) {
			if ($event instanceof GenericEvent) {
				$this->emit('\OC\Updater', 'repairInfo', $event->getArguments());
			}
		});
		$dispatcher->addListener('\OC\Repair::step', function ($event) {
			if ($event instanceof GenericEvent) {
				$this->emit('\OC\Updater', 'repairStep', $event->getArguments());
			}
		});
	}

}

