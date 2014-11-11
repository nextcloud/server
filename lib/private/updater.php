<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC;

use OC\Hooks\BasicEmitter;

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

	/**
	 * @var \OC\Log $log
	 */
	private $log;

	private $simulateStepEnabled;

	private $updateStepEnabled;

	/**
	 * @param \OC\Log $log
	 */
	public function __construct($log = null) {
		$this->log = $log;
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
	 * Check if a new version is available
	 *
	 * @param string $updaterUrl the url to check, i.e. 'http://apps.owncloud.com/updater.php'
	 * @return array|bool
	 */
	public function check($updaterUrl = null) {

		// Look up the cache - it is invalidated all 30 minutes
		if ((\OC_Appconfig::getValue('core', 'lastupdatedat') + 1800) > time()) {
			return json_decode(\OC_Appconfig::getValue('core', 'lastupdateResult'), true);
		}

		if (is_null($updaterUrl)) {
			$updaterUrl = 'https://apps.owncloud.com/updater.php';
		}

		\OC_Appconfig::setValue('core', 'lastupdatedat', time());

		if (\OC_Appconfig::getValue('core', 'installedat', '') == '') {
			\OC_Appconfig::setValue('core', 'installedat', microtime(true));
		}

		$version = \OC_Util::getVersion();
		$version['installed'] = \OC_Appconfig::getValue('core', 'installedat');
		$version['updated'] = \OC_Appconfig::getValue('core', 'lastupdatedat');
		$version['updatechannel'] = \OC_Util::getChannel();
		$version['edition'] = \OC_Util::getEditionString();
		$version['build'] = \OC_Util::getBuild();
		$versionString = implode('x', $version);

		//fetch xml data from updater
		$url = $updaterUrl . '?version=' . $versionString;

		// set a sensible timeout of 10 sec to stay responsive even if the update server is down.
		$ctx = stream_context_create(
			array(
				'http' => array(
					'timeout' => 10
				)
			)
		);
		$xml = @file_get_contents($url, 0, $ctx);
		if ($xml == false) {
			return array();
		}
		$loadEntities = libxml_disable_entity_loader(true);
		$data = @simplexml_load_string($xml);
		libxml_disable_entity_loader($loadEntities);

		$tmp = array();
		$tmp['version'] = $data->version;
		$tmp['versionstring'] = $data->versionstring;
		$tmp['url'] = $data->url;
		$tmp['web'] = $data->web;

		// Cache the result
		\OC_Appconfig::setValue('core', 'lastupdateResult', json_encode($data));

		return $tmp;
	}

	/**
	 * runs the update actions in maintenance mode, does not upgrade the source files
	 * except the main .htaccess file
	 *
	 * @return bool true if the operation succeeded, false otherwise
	 */
	public function upgrade() {
		\OC_Config::setValue('maintenance', true);

		$installedVersion = \OC_Config::getValue('version', '0.0.0');
		$currentVersion = implode('.', \OC_Util::getVersion());
		if ($this->log) {
			$this->log->debug('starting upgrade from ' . $installedVersion . ' to ' . $currentVersion, array('app' => 'core'));
		}
		$this->emit('\OC\Updater', 'maintenanceStart');

		try {
			$this->doUpgrade($currentVersion, $installedVersion);
		} catch (\Exception $exception) {
			$this->emit('\OC\Updater', 'failure', array($exception->getMessage()));
		}

		\OC_Config::setValue('maintenance', false);
		$this->emit('\OC\Updater', 'maintenanceEnd');
	}

	/**
	 * Whether an upgrade to a specified version is possible
	 * @param string $oldVersion
	 * @param string $newVersion
	 * @return bool
	 */
	public function isUpgradePossible($oldVersion, $newVersion) {
		$oldVersion = explode('.', $oldVersion);
		$newVersion = explode('.', $newVersion);

		if($newVersion[0] > ($oldVersion[0] + 1) || $oldVersion[0] > $newVersion[0]) {
			return false;
		}
		return true;
	}

	/**
	 * runs the update actions in maintenance mode, does not upgrade the source files
	 * except the main .htaccess file
	 *
	 * @param string $currentVersion current version to upgrade to
	 * @param string $installedVersion previous version from which to upgrade from
	 *
	 * @throws \Exception
	 * @return bool true if the operation succeeded, false otherwise
	 */
	private function doUpgrade($currentVersion, $installedVersion) {
		// Stop update if the update is over several major versions
		if (!self::isUpgradePossible($installedVersion, $currentVersion)) {
			throw new \Exception('Updates between multiple major versions are unsupported.');
		}

		// Update htaccess files for apache hosts
		if (isset($_SERVER['SERVER_SOFTWARE']) && strstr($_SERVER['SERVER_SOFTWARE'], 'Apache')) {
			\OC_Setup::updateHtaccess();
		}

		// create empty file in data dir, so we can later find
		// out that this is indeed an ownCloud data directory
		// (in case it didn't exist before)
		file_put_contents(\OC_Config::getValue('datadirectory', \OC::$SERVERROOT . '/data') . '/.ocdata', '');

		// pre-upgrade repairs
		$repair = new \OC\Repair(\OC\Repair::getBeforeUpgradeRepairSteps());
		$repair->run();

		// simulate DB upgrade
		if ($this->simulateStepEnabled) {
			$this->checkCoreUpgrade();

			// simulate apps DB upgrade
			$this->checkAppUpgrade($currentVersion);

		}

		if ($this->updateStepEnabled) {
			$this->doCoreUpgrade();

			$disabledApps = \OC_App::checkAppsRequirements();
			if (!empty($disabledApps)) {
				$this->emit('\OC\Updater', 'disabledApps', array($disabledApps));
			}

			$this->doAppUpgrade();

			// post-upgrade repairs
			$repair = new \OC\Repair(\OC\Repair::getRepairSteps());
			$repair->run();

			//Invalidate update feed
			\OC_Appconfig::setValue('core', 'lastupdatedat', 0);

			// only set the final version if everything went well
			\OC_Config::setValue('version', implode('.', \OC_Util::getVersion()));
		}
	}

	protected function checkCoreUpgrade() {
		// simulate core DB upgrade
		\OC_DB::simulateUpdateDbFromStructure(\OC::$SERVERROOT . '/db_structure.xml');

		$this->emit('\OC\Updater', 'dbSimulateUpgrade');
	}

	protected function doCoreUpgrade() {
		// do the real upgrade
		\OC_DB::updateDbFromStructure(\OC::$SERVERROOT . '/db_structure.xml');

		$this->emit('\OC\Updater', 'dbUpgrade');
	}

	/**
	 * @param string $version the oc version to check app compatibility with
	 */
	protected function checkAppUpgrade($version) {
		$apps = \OC_App::getEnabledApps();

		foreach ($apps as $appId) {
			if ($version) {
				$info = \OC_App::getAppInfo($appId);
				$compatible = \OC_App::isAppCompatible($version, $info);
			} else {
				$compatible = true;
			}

			if ($compatible && \OC_App::shouldUpgrade($appId)) {
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

	protected function doAppUpgrade() {
		$apps = \OC_App::getEnabledApps();

		foreach ($apps as $appId) {
			if (\OC_App::shouldUpgrade($appId)) {
				\OC_App::updateApp($appId);
				$this->emit('\OC\Updater', 'appUpgrade', array($appId, \OC_App::getAppVersion($appId)));
			}
		}
	}
}

