<?php
/**
 * Copyright (c) 2014 Georg Ehrke <georg@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_Installer extends \Test\TestCase {

	private static $appid = 'testapp';
	private $appstore;

	protected function setUp() {
		parent::setUp();

		$config = \OC::$server->getConfig();
		$this->appstore = $config->setSystemValue('appstoreenabled', true);
		$config->setSystemValue('appstoreenabled', true);
		OC_Installer::removeApp(self::$appid);
	}

	protected function tearDown() {
		OC_Installer::removeApp(self::$appid);
		\OC::$server->getConfig()->setSystemValue('appstoreenabled', $this->appstore);

		parent::tearDown();
	}

	public function testInstallApp() {
		$pathOfTestApp  = __DIR__;
		$pathOfTestApp .= '/../data/';
		$pathOfTestApp .= 'testapp.zip';

		$tmp = \OC::$server->getTempManager()->getTemporaryFile('.zip');
		OC_Helper::copyr($pathOfTestApp, $tmp);

		$data = array(
			'path' => $tmp,
			'source' => 'path',
			'appdata' => [
				'id' => 'Bar',
				'level' => 100,
			]
		);

		OC_Installer::installApp($data);
		$isInstalled = OC_Installer::isInstalled(self::$appid);

		$this->assertTrue($isInstalled);
	}

	public function testUpdateApp() {
		$pathOfOldTestApp  = __DIR__;
		$pathOfOldTestApp .= '/../data/';
		$pathOfOldTestApp .= 'testapp.zip';

		$oldTmp = \OC::$server->getTempManager()->getTemporaryFile('.zip');
		OC_Helper::copyr($pathOfOldTestApp, $oldTmp);

		$oldData = array(
			'path' => $oldTmp,
			'source' => 'path',
			'appdata' => [
				'id' => 'Bar',
				'level' => 100,
			]
		);

		$pathOfNewTestApp  = __DIR__;
		$pathOfNewTestApp .= '/../data/';
		$pathOfNewTestApp .= 'testapp2.zip';

		$newTmp = \OC::$server->getTempManager()->getTemporaryFile('.zip');
		OC_Helper::copyr($pathOfNewTestApp, $newTmp);

		$newData = array(
			'path' => $newTmp,
			'source' => 'path',
			'appdata' => [
				'id' => 'Bar',
				'level' => 100,
			]
		);

		OC_Installer::installApp($oldData);
		$oldVersionNumber = OC_App::getAppVersion(self::$appid);

		OC_Installer::updateApp($newData);
		$newVersionNumber = OC_App::getAppVersion(self::$appid);

		$this->assertNotEquals($oldVersionNumber, $newVersionNumber);
	}
}
