<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use OCP\App\IAppManager;

/**
 * Class InfoXmlTest
 *
 * @group DB
 * @package Test
 */
class InfoXmlTest extends TestCase {
	public function dataApps() {
		return [
			['admin_audit'],
			['comments'],
			['dav'],
			['encryption'],
			['federatedfilesharing'],
			['federation'],
			['files'],
			['files_external'],
			['files_sharing'],
			['files_trashbin'],
			['files_versions'],
			['provisioning_api'],
			['systemtags'],
			['theming'],
			['settings'],
			['twofactor_backupcodes'],
			['updatenotification'],
			['user_ldap'],
			['workflowengine'],
		];
	}

	/**
	 * @dataProvider dataApps
	 *
	 * @param string $app
	 */
	public function testClasses($app) {
		$appInfo = \OCP\Server::get(IAppManager::class)->getAppInfo($app);
		$appPath = \OC_App::getAppPath($app);
		\OC_App::registerAutoloading($app, $appPath);

		//Add the appcontainer
		$applicationClassName = \OCP\AppFramework\App::buildAppNamespace($app) . '\\AppInfo\\Application';
		if (class_exists($applicationClassName)) {
			$application = new $applicationClassName();
			$this->addToAssertionCount(1);
		} else {
			$application = new \OCP\AppFramework\App($app);
			$this->addToAssertionCount(1);
		}

		if (isset($appInfo['background-jobs'])) {
			foreach ($appInfo['background-jobs'] as $job) {
				$this->assertTrue(class_exists($job), 'Asserting background job "' . $job . '" exists');
				$this->assertInstanceOf($job, \OC::$server->query($job));
			}
		}

		if (isset($appInfo['two-factor-providers'])) {
			foreach ($appInfo['two-factor-providers'] as $provider) {
				$this->assertTrue(class_exists($provider), 'Asserting two-factor providers "' . $provider . '" exists');
				$this->assertInstanceOf($provider, \OC::$server->query($provider));
			}
		}

		if (isset($appInfo['commands'])) {
			foreach ($appInfo['commands'] as $command) {
				$this->assertTrue(class_exists($command), 'Asserting command "' . $command . '" exists');
				$this->assertInstanceOf($command, \OC::$server->query($command));
			}
		}

		if (isset($appInfo['repair-steps']['pre-migration'])) {
			foreach ($appInfo['repair-steps']['pre-migration'] as $migration) {
				$this->assertTrue(class_exists($migration), 'Asserting pre-migration "' . $migration . '" exists');
				$this->assertInstanceOf($migration, \OC::$server->query($migration));
			}
		}

		if (isset($appInfo['repair-steps']['post-migration'])) {
			foreach ($appInfo['repair-steps']['post-migration'] as $migration) {
				$this->assertTrue(class_exists($migration), 'Asserting post-migration "' . $migration . '" exists');
				$this->assertInstanceOf($migration, \OC::$server->query($migration));
			}
		}

		if (isset($appInfo['repair-steps']['live-migration'])) {
			foreach ($appInfo['repair-steps']['live-migration'] as $migration) {
				$this->assertTrue(class_exists($migration), 'Asserting live-migration "' . $migration . '" exists');
				$this->assertInstanceOf($migration, \OC::$server->query($migration));
			}
		}

		if (isset($appInfo['repair-steps']['install'])) {
			foreach ($appInfo['repair-steps']['install'] as $migration) {
				$this->assertTrue(class_exists($migration), 'Asserting install-migration "' . $migration . '" exists');
				$this->assertInstanceOf($migration, \OC::$server->query($migration));
			}
		}

		if (isset($appInfo['repair-steps']['uninstall'])) {
			foreach ($appInfo['repair-steps']['uninstall'] as $migration) {
				$this->assertTrue(class_exists($migration), 'Asserting uninstall-migration "' . $migration . '" exists');
				$this->assertInstanceOf($migration, \OC::$server->query($migration));
			}
		}

		if (isset($appInfo['commands'])) {
			foreach ($appInfo['commands'] as $command) {
				$this->assertTrue(class_exists($command), 'Asserting command "'. $command . '"exists');
				$this->assertInstanceOf($command, \OC::$server->query($command));
			}
		}
	}
}
