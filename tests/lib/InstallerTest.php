<?php
/**
 * Copyright (c) 2014 Georg Ehrke <georg@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test;


use OC\Archive\ZIP;
use OC\Installer;

class InstallerTest extends TestCase {

	private static $appid = 'testapp';
	private $appstore;

	protected function setUp() {
		parent::setUp();

		$config = \OC::$server->getConfig();
		$this->appstore = $config->setSystemValue('appstoreenabled', true);
		$config->setSystemValue('appstoreenabled', true);
		$installer = new Installer(
			\OC::$server->getAppFetcher(),
			\OC::$server->getHTTPClientService(),
			\OC::$server->getTempManager(),
			\OC::$server->getLogger()
		);
		$installer->removeApp(self::$appid);
	}

	protected function tearDown() {
		$installer = new Installer(
			\OC::$server->getAppFetcher(),
			\OC::$server->getHTTPClientService(),
			\OC::$server->getTempManager(),
			\OC::$server->getLogger()
		);
		$installer->removeApp(self::$appid);
		\OC::$server->getConfig()->setSystemValue('appstoreenabled', $this->appstore);

		parent::tearDown();
	}

	public function testInstallApp() {
		// Extract app
		$pathOfTestApp  = __DIR__ . '/../data/testapp.zip';
		$tar = new ZIP($pathOfTestApp);
		$tar->extract(\OC_App::getInstallPath());

		// Install app
		$installer = new Installer(
			\OC::$server->getAppFetcher(),
			\OC::$server->getHTTPClientService(),
			\OC::$server->getTempManager(),
			\OC::$server->getLogger()
		);
		$installer->installApp(self::$appid);
		$isInstalled = Installer::isInstalled(self::$appid);
		$this->assertTrue($isInstalled);
		$installer->removeApp(self::$appid);
	}
}
