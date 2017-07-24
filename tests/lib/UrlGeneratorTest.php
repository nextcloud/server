<?php
/**
 * Copyright (c) 2014 Bjoern Schiessle <schiessle@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test;

use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IURLGenerator;

/**
 * Class UrlGeneratorTest
 *
 * @group DB
 */
class UrlGeneratorTest extends \Test\TestCase {

	/** @var \PHPUnit_Framework_MockObject_MockObject|IConfig */
	private $config;
	/** @var \PHPUnit_Framework_MockObject_MockObject|ICacheFactory */
	private $cacheFactory;
	/** @var \PHPUnit_Framework_MockObject_MockObject|IRequest */
	private $request;
	/** @var IURLGenerator */
	private $urlGenerator;

	public function setUp() {
		parent::setUp();
		$this->config = $this->createMock(IConfig::class);
		$this->cacheFactory = $this->createMock(ICacheFactory::class);
		$this->request = $this->createMock(IRequest::class);
		$this->urlGenerator = new \OC\URLGenerator(
			$this->config,
			$this->cacheFactory,
			$this->request
		);
	}

	private function mockBaseUrl() {
		$this->request->expects($this->once())
			->method('getServerProtocol')
			->willReturn('http');
		$this->request->expects($this->once())
			->method('getServerHost')
			->willReturn('localhost');
	}

	/**
	 * @small
	 * test linkTo URL construction
	 * @dataProvider provideDocRootAppUrlParts
	 */
	public function testLinkToDocRoot($app, $file, $args, $expectedResult) {
		\OC::$WEBROOT = '';
		$result = $this->urlGenerator->linkTo($app, $file, $args);
		$this->assertEquals($expectedResult, $result);
	}

	/**
	 * @small
	 * test linkTo URL construction in sub directory
	 * @dataProvider provideSubDirAppUrlParts
	 */
	public function testLinkToSubDir($app, $file, $args, $expectedResult) {
		\OC::$WEBROOT = '/owncloud';
		$result = $this->urlGenerator->linkTo($app, $file, $args);
		$this->assertEquals($expectedResult, $result);
	}

	/**
	 * @dataProvider provideRoutes
	 */
	public function testLinkToRouteAbsolute($route, $expected) {
		$this->mockBaseUrl();
		\OC::$WEBROOT = '/owncloud';
		$result = $this->urlGenerator->linkToRouteAbsolute($route);
		$this->assertEquals($expected, $result);
	}

	public function provideRoutes() {
		return [
			['files_ajax_list', 'http://localhost/owncloud/index.php/apps/files/ajax/list.php'],
			['core.Preview.getPreview', 'http://localhost/owncloud/index.php/core/preview.png'],
		];
	}

	public function provideDocRootAppUrlParts() {
		return [
			['files', 'ajax/list.php', [], '/index.php/apps/files/ajax/list.php'],
			['files', 'ajax/list.php', ['trut' => 'trat', 'dut' => 'dat'], '/index.php/apps/files/ajax/list.php?trut=trat&dut=dat'],
			['', 'index.php', ['trut' => 'trat', 'dut' => 'dat'], '/index.php?trut=trat&dut=dat'],
		];
	}

	public function provideSubDirAppUrlParts() {
		return [
			['files', 'ajax/list.php', [], '/owncloud/index.php/apps/files/ajax/list.php'],
			['files', 'ajax/list.php', ['trut' => 'trat', 'dut' => 'dat'], '/owncloud/index.php/apps/files/ajax/list.php?trut=trat&dut=dat'],
			['', 'index.php', ['trut' => 'trat', 'dut' => 'dat'], '/owncloud/index.php?trut=trat&dut=dat'],
		];
	}

	/**
	 * @small
	 * test absolute URL construction
	 * @dataProvider provideDocRootURLs
	 */
	public function testGetAbsoluteURLDocRoot($url, $expectedResult) {
		$this->mockBaseUrl();
		\OC::$WEBROOT = '';
		$result = $this->urlGenerator->getAbsoluteURL($url);
		$this->assertEquals($expectedResult, $result);
	}

	/**
	 * @small
	 * test absolute URL construction
	 * @dataProvider provideSubDirURLs
	 */
	public function testGetAbsoluteURLSubDir($url, $expectedResult) {
		$this->mockBaseUrl();
		\OC::$WEBROOT = '/owncloud';
		$result = $this->urlGenerator->getAbsoluteURL($url);
		$this->assertEquals($expectedResult, $result);
	}

	public function provideDocRootURLs() {
		return [
			["index.php", "http://localhost/index.php"],
			["/index.php", "http://localhost/index.php"],
			["/apps/index.php", "http://localhost/apps/index.php"],
			["apps/index.php", "http://localhost/apps/index.php"],
			];
	}

	public function provideSubDirURLs() {
		return [
			["index.php", "http://localhost/owncloud/index.php"],
			["/index.php", "http://localhost/owncloud/index.php"],
			["/apps/index.php", "http://localhost/owncloud/apps/index.php"],
			["apps/index.php", "http://localhost/owncloud/apps/index.php"],
			];
	}

	public function testGetBaseUrl() {
		$this->mockBaseUrl();
		\OC::$WEBROOT = '/nextcloud';
		$actual = $this->urlGenerator->getBaseUrl();
		$expected = "http://localhost/nextcloud";
		$this->assertEquals($expected, $actual);
	}
}
