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

	/**
	 * @param \OC\Log $log
	 */
	public function __construct($log = null) {
		$this->log = $log;
	}

	/**
	 * Check if a new version is available
	 * @param string $updaterUrl the url to check, i.e. 'http://apps.owncloud.com/updater.php'
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

		// Update htaccess files for apache hosts
		if (isset($_SERVER['SERVER_SOFTWARE']) && strstr($_SERVER['SERVER_SOFTWARE'], 'Apache')) {
			\OC_Setup::updateHtaccess();
		}

		// create empty file in data dir, so we can later find
		// out that this is indeed an ownCloud data directory
		// (in case it didn't exist before)
		file_put_contents(\OC_Config::getValue('datadirectory', \OC::$SERVERROOT.'/data').'/.ocdata', '');

		/*
		 * START CONFIG CHANGES FOR OLDER VERSIONS
		 */
		if (!\OC::$CLI && version_compare($installedVersion, '6.90.1', '<')) {
			// Add the trusted_domains config if it is not existant
			// This is added to prevent host header poisoning
			\OC_Config::setValue('trusted_domains', \OC_Config::getValue('trusted_domains', array(\OC_Request::serverHost()))); 
		}
		/*
		 * STOP CONFIG CHANGES FOR OLDER VERSIONS
		 */


		try {
			\OC_DB::updateDbFromStructure(\OC::$SERVERROOT . '/db_structure.xml');
			$this->emit('\OC\Updater', 'dbUpgrade');

		} catch (\Exception $exception) {
			$this->emit('\OC\Updater', 'failure', array($exception->getMessage()));
		}
		\OC_Config::setValue('version', implode('.', \OC_Util::getVersion()));
		\OC_App::checkAppsRequirements();
		// load all apps to also upgrade enabled apps
		\OC_App::loadApps();

		$repair = new Repair();
		$repair->run();

		//Invalidate update feed
		\OC_Appconfig::setValue('core', 'lastupdatedat', 0);
		\OC_Config::setValue('maintenance', false);
		$this->emit('\OC\Updater', 'maintenanceEnd');
	}

}

