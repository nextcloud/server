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
 *  - filecacheStart()
 *  - filecacheProgress(int $percentage)
 *  - filecacheDone()
 *  - failure(string $message)
 */
class Updater extends BasicEmitter {

	/**
	 * @var \OC\Log $log
	 */
	private $log;

	/**
	 * @param \OC\Log $log
	 */
	public function __construct($log = null) {
		$this->log = $log;
	}

	/**
	 * Check if a new version is available
	 * @param string $updateUrl the url to check, i.e. 'http://apps.owncloud.com/updater.php'
	 * @return array | bool
	 */
	public function check($updaterUrl) {

		// Look up the cache - it is invalidated all 30 minutes
		if ((\OC_Appconfig::getValue('core', 'lastupdatedat') + 1800) > time()) {
			return json_decode(\OC_Appconfig::getValue('core', 'lastupdateResult'), true);
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
		$data = @simplexml_load_string($xml);

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
	 */
	public function upgrade() {
		\OC_DB::enableCaching(false);
		\OC_Config::setValue('maintenance', true);
		$installedVersion = \OC_Config::getValue('version', '0.0.0');
		$currentVersion = implode('.', \OC_Util::getVersion());
		if ($this->log) {
			$this->log->debug('starting upgrade from ' . $installedVersion . ' to ' . $currentVersion, array('app' => 'core'));
		}
		$this->emit('\OC\Updater', 'maintenanceStart');
		try {
			\OC_DB::updateDbFromStructure(\OC::$SERVERROOT . '/db_structure.xml');
			$this->emit('\OC\Updater', 'dbUpgrade');

			// do a file cache upgrade for users with files
			// this can take loooooooooooooooooooooooong
			$this->upgradeFileCache();
		} catch (\Exception $exception) {
			$this->emit('\OC\Updater', 'failure', array($exception->getMessage()));
		}
		\OC_Config::setValue('version', implode('.', \OC_Util::getVersion()));
		\OC_App::checkAppsRequirements();
		// load all apps to also upgrade enabled apps
		\OC_App::loadApps();
		\OC_Config::setValue('maintenance', false);
		$this->emit('\OC\Updater', 'maintenanceEnd');
	}

	private function upgradeFileCache() {
		try {
			$query = \OC_DB::prepare('
				SELECT DISTINCT `user`
				FROM `*PREFIX*fscache`
			');
			$result = $query->execute();
		} catch (\Exception $e) {
			return;
		}
		$users = $result->fetchAll();
		if (count($users) == 0) {
			return;
		}
		$step = 100 / count($users);
		$percentCompleted = 0;
		$lastPercentCompletedOutput = 0;
		$startInfoShown = false;
		foreach ($users as $userRow) {
			$user = $userRow['user'];
			\OC\Files\Filesystem::initMountPoints($user);
			\OC\Files\Cache\Upgrade::doSilentUpgrade($user);
			if (!$startInfoShown) {
				//We show it only now, because otherwise Info about upgraded apps
				//will appear between this and progress info
				$this->emit('\OC\Updater', 'filecacheStart');
				$startInfoShown = true;
			}
			$percentCompleted += $step;
			$out = floor($percentCompleted);
			if ($out != $lastPercentCompletedOutput) {
				$this->emit('\OC\Updater', 'filecacheProgress', array($out));
				$lastPercentCompletedOutput = $out;
			}
		}
		$this->emit('\OC\Updater', 'filecacheDone');
	}
}
