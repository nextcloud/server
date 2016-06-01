<?php
/**
 * Copyright (c) 2014 Bjoern Schiessle <schiessle@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test;

/**
 * Class UrlGeneratorTest
 *
 * @group DB
 */
class UrlGeneratorTest extends \Test\TestCase {

	/**
	 * @small
	 * test linkTo URL construction
	 * @dataProvider provideDocRootAppUrlParts
	 */
	public function testLinkToDocRoot($app, $file, $args, $expectedResult) {
		\OC::$WEBROOT = '';
		$config = $this->getMock('\OCP\IConfig');
		$cacheFactory = $this->getMock('\OCP\ICacheFactory');
		$urlGenerator = new \OC\URLGenerator($config, $cacheFactory);
		$result = $urlGenerator->linkTo($app, $file, $args);

		$this->assertEquals($expectedResult, $result);
	}

	/**
	 * @small
	 * test linkTo URL construction in sub directory
	 * @dataProvider provideSubDirAppUrlParts
	 */
	public function testLinkToSubDir($app, $file, $args, $expectedResult) {
		\OC::$WEBROOT = '/owncloud';
		$config = $this->getMock('\OCP\IConfig');
		$cacheFactory = $this->getMock('\OCP\ICacheFactory');
		$urlGenerator = new \OC\URLGenerator($config, $cacheFactory);
		$result = $urlGenerator->linkTo($app, $file, $args);

		$this->assertEquals($expectedResult, $result);
	}

	/**
	 * @dataProvider provideRoutes
	 */
	public function testLinkToRouteAbsolute($route, $expected) {
		\OC::$WEBROOT = '/owncloud';
		$config = $this->getMock('\OCP\IConfig');
		$cacheFactory = $this->getMock('\OCP\ICacheFactory');
		$urlGenerator = new \OC\URLGenerator($config, $cacheFactory);
		$result = $urlGenerator->linkToRouteAbsolute($route);
		$this->assertEquals($expected, $result);

	}

	public function provideRoutes() {
		return array(
			array('files_ajax_list', 'http://localhost/owncloud/index.php/apps/files/ajax/list.php'),
			array('core_ajax_preview', 'http://localhost/owncloud/index.php/core/preview.png'),
		);
	}

	public function provideDocRootAppUrlParts() {
		return array(
			array('files', 'ajax/list.php', array(), '/index.php/apps/files/ajax/list.php'),
			array('files', 'ajax/list.php', array('trut' => 'trat', 'dut' => 'dat'), '/index.php/apps/files/ajax/list.php?trut=trat&dut=dat'),
			array('', 'index.php', array('trut' => 'trat', 'dut' => 'dat'), '/index.php?trut=trat&dut=dat'),
		);
	}

	public function provideSubDirAppUrlParts() {
		return array(
			array('files', 'ajax/list.php', array(), '/owncloud/index.php/apps/files/ajax/list.php'),
			array('files', 'ajax/list.php', array('trut' => 'trat', 'dut' => 'dat'), '/owncloud/index.php/apps/files/ajax/list.php?trut=trat&dut=dat'),
			array('', 'index.php', array('trut' => 'trat', 'dut' => 'dat'), '/owncloud/index.php?trut=trat&dut=dat'),
		);
	}

	/**
	 * @small
	 * test absolute URL construction
	 * @dataProvider provideDocRootURLs
	 */
	function testGetAbsoluteURLDocRoot($url, $expectedResult) {

		\OC::$WEBROOT = '';
		$config = $this->getMock('\OCP\IConfig');
		$cacheFactory = $this->getMock('\OCP\ICacheFactory');
		$urlGenerator = new \OC\URLGenerator($config, $cacheFactory);
		$result = $urlGenerator->getAbsoluteURL($url);

		$this->assertEquals($expectedResult, $result);
	}

	/**
	 * @small
	 * test absolute URL construction
	 * @dataProvider provideSubDirURLs
	 */
	function testGetAbsoluteURLSubDir($url, $expectedResult) {

		\OC::$WEBROOT = '/owncloud';
		$config = $this->getMock('\OCP\IConfig');
		$cacheFactory = $this->getMock('\OCP\ICacheFactory');
		$urlGenerator = new \OC\URLGenerator($config, $cacheFactory);
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

