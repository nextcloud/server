<?php
/**
 * Copyright (c) 2014 Georg Ehrke <georg@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_Installer extends PHPUnit_Framework_TestCase {

	private static $appid = 'testapp';

	public function testInstallApp() {
		$pathOfTestApp  = __DIR__;
		$pathOfTestApp .= '/../data/';
		$pathOfTestApp .= 'testapp.zip';

		$tmp = OC_Helper::tmpFile();
		OC_Helper::copyr($pathOfTestApp, $tmp);

		$data = array(
			'path' => $tmp,
			'source' => 'path',
		);

		OC_Installer::installApp($data);
		$isInstalled = OC_Installer::isInstalled(self::$appid);

		$this->assertTrue($isInstalled);

		//clean-up
		OC_Installer::removeApp(self::$appid);
		unlink($tmp);
	}

	public function testUpdateApp() {
		$pathOfOldTestApp  = __DIR__;
		$pathOfOldTestApp .= '/../data/';
		$pathOfOldTestApp .= 'testapp.zip';

		$oldTmp = OC_Helper::tmpFile();
		OC_Helper::copyr($pathOfOldTestApp, $oldTmp);

		$oldData = array(
			'path' => $oldTmp,
			'source' => 'path',
		);

		$pathOfNewTestApp  = __DIR__;
		$pathOfNewTestApp .= '/../data/';
		$pathOfNewTestApp .= 'testapp2.zip';

		$newTmp = OC_Helper::tmpFile();
		OC_Helper::copyr($pathOfNewTestApp, $newTmp);

		$newData = array(
			'path' => $newTmp,
			'source' => 'path',
		);

		OC_Installer::installApp($oldData);
		$oldVersionNumber = OC_App::getAppVersion(self::$appid);

		OC_Installer::updateApp($newData);
		$newVersionNumber = OC_App::getAppVersion(self::$appid);

		$this->assertNotEquals($oldVersionNumber, $newVersionNumber);

		//clean-up
		OC_Installer::removeApp(self::$appid);
		unlink($oldTmp);
		unlink($newTmp);
	}
}
