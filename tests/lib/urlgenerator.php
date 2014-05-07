<?php
/**
 * Copyright (c) 2014 Bjoern Schiessle <schiessle@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_Urlgenerator extends PHPUnit_Framework_TestCase {

	/**
	 * @small
	 * @brief test linkTo URL construction
	 * @dataProvider provideDocRootAppUrlParts
	 */
	public function testLinkToDocRoot($app, $file, $args, $expectedResult) {
		\OC::$WEBROOT = '';
		$config = $this->getMock('\OCP\IConfig');
		$urlGenerator = new \OC\URLGenerator($config);
		$result = $urlGenerator->linkTo($app, $file, $args);

		$this->assertEquals($expectedResult, $result);
	}

	/**
	 * @small
	 * @brief test linkTo URL construction in sub directory
	 * @dataProvider provideSubDirAppUrlParts
	 */
	public function testLinkToSubDir($app, $file, $args, $expectedResult) {
		\OC::$WEBROOT = '/owncloud';
		$config = $this->getMock('\OCP\IConfig');
		$urlGenerator = new \OC\URLGenerator($config);
		$result = $urlGenerator->linkTo($app, $file, $args);

		$this->assertEquals($expectedResult, $result);
	}

	public function provideDocRootAppUrlParts() {
		return array(
			array('files', 'index.php', array(), '/index.php/apps/files'),
			array('files', 'index.php', array('trut' => 'trat', 'dut' => 'dat'), '/index.php/apps/files?trut=trat&dut=dat'),
			array('', 'index.php', array('trut' => 'trat', 'dut' => 'dat'), '/index.php?trut=trat&dut=dat'),
		);
	}

	public function provideSubDirAppUrlParts() {
		return array(
			array('files', 'index.php', array(), '/owncloud/index.php/apps/files'),
			array('files', 'index.php', array('trut' => 'trat', 'dut' => 'dat'), '/owncloud/index.php/apps/files?trut=trat&dut=dat'),
			array('', 'index.php', array('trut' => 'trat', 'dut' => 'dat'), '/owncloud/index.php?trut=trat&dut=dat'),
		);
	}

	/**
	 * @small
	 * @brief test absolute URL construction
	 * @dataProvider provideDocRootURLs
	 */
	function testGetAbsoluteURLDocRoot($url, $expectedResult) {

		\OC::$WEBROOT = '';
		$urlGenerator = new \OC\URLGenerator(null);
		$result = $urlGenerator->getAbsoluteURL($url);

		$this->assertEquals($expectedResult, $result);
	}

	/**
	 * @small
	 * @brief test absolute URL construction
	 * @dataProvider provideSubDirURLs
	 */
	function testGetAbsoluteURLSubDir($url, $expectedResult) {

		\OC::$WEBROOT = '/owncloud';
		$urlGenerator = new \OC\URLGenerator(null);
		$result = $urlGenerator->getAbsoluteURL($url);

		$this->assertEquals($expectedResult, $result);
	}

	public function provideDocRootURLs() {
		return array(
			array("index.php", "http://localhost/index.php"),
			array("/index.php", "http://localhost/index.php"),
			array("/apps/index.php", "http://localhost/apps/index.php"),
			array("apps/index.php", "http://localhost/apps/index.php"),
			);
	}

	public function provideSubDirURLs() {
		return array(
			array("index.php", "http://localhost/owncloud/index.php"),
			array("/index.php", "http://localhost/owncloud/index.php"),
			array("/apps/index.php", "http://localhost/owncloud/apps/index.php"),
			array("apps/index.php", "http://localhost/owncloud/apps/index.php"),
			);
	}
}

