<?php
/**
 * Copyright (c) 2014 Bjoern Schiessle <schiessle@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test;

use OC\Route\Router;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;

/**
 * Class UrlGeneratorTest
 *
 * @package Test
 */
class UrlGeneratorTest extends \Test\TestCase {
	/** @var \PHPUnit\Framework\MockObject\MockObject|IConfig */
	private $config;
	/** @var \PHPUnit\Framework\MockObject\MockObject|IUserSession */
	private $userSession;
	/** @var \PHPUnit\Framework\MockObject\MockObject|ICacheFactory */
	private $cacheFactory;
	/** @var \PHPUnit\Framework\MockObject\MockObject|IRequest */
	private $request;
	/** @var \PHPUnit\Framework\MockObject\MockObject|Router */
	private $router;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var string */
	private $originalWebRoot;

	protected function setUp(): void {
		parent::setUp();
		$this->config = $this->createMock(IConfig::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->cacheFactory = $this->createMock(ICacheFactory::class);
		$this->request = $this->createMock(IRequest::class);
		$this->router = $this->createMock(Router::class);
		$this->urlGenerator = new \OC\URLGenerator(
			$this->config,
			$this->userSession,
			$this->cacheFactory,
			$this->request,
			$this->router
		);
		$this->originalWebRoot = \OC::$WEBROOT;
	}

	protected function tearDown(): void {
		// Reset webRoot
		\OC::$WEBROOT = $this->originalWebRoot;
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
		\OC::$WEBROOT = '/nextcloud';
		$result = $this->urlGenerator->linkTo($app, $file, $args);
		$this->assertEquals($expectedResult, $result);
	}

	/**
	 * @dataProvider provideRoutes
	 */
	public function testLinkToRouteAbsolute($route, $expected) {
		$this->mockBaseUrl();
		\OC::$WEBROOT = '/nextcloud';
		$this->router->expects($this->once())
			->method('generate')
			->willReturnCallback(function ($routeName, $parameters) {
				if ($routeName === 'core.Preview.getPreview') {
					return '/index.php/core/preview.png';
				} elseif ($routeName === 'cloud_federation_api.requesthandlercontroller.addShare') {
					return '/index.php/ocm/shares';
				}
			});
		$result = $this->urlGenerator->linkToRouteAbsolute($route);
		$this->assertEquals($expected, $result);
	}

	public function provideRoutes() {
		return [
			['core.Preview.getPreview', 'http://localhost/nextcloud/index.php/core/preview.png'],
			['cloud_federation_api.requesthandlercontroller.addShare', 'http://localhost/nextcloud/index.php/ocm/shares'],
		];
	}

	public function provideDocRootAppUrlParts() {
		return [
			['files', 'ajax/download.php', [], '/index.php/apps/files/ajax/download.php'],
			['files', 'ajax/download.php', ['trut' => 'trat', 'dut' => 'dat'], '/index.php/apps/files/ajax/download.php?trut=trat&dut=dat'],
			['', 'index.php', ['trut' => 'trat', 'dut' => 'dat'], '/index.php?trut=trat&dut=dat'],
		];
	}

	public function provideSubDirAppUrlParts() {
		return [
			['files', 'ajax/download.php', [], '/nextcloud/index.php/apps/files/ajax/download.php'],
			['files', 'ajax/download.php', ['trut' => 'trat', 'dut' => 'dat'], '/nextcloud/index.php/apps/files/ajax/download.php?trut=trat&dut=dat'],
			['', 'index.php', ['trut' => 'trat', 'dut' => 'dat'], '/nextcloud/index.php?trut=trat&dut=dat'],
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
		\OC::$WEBROOT = '/nextcloud';
		$result = $this->urlGenerator->getAbsoluteURL($url);
		$this->assertEquals($expectedResult, $result);
	}

	public function provideDocRootURLs() {
		return [
			['index.php', 'http://localhost/index.php'],
			['/index.php', 'http://localhost/index.php'],
			['/apps/index.php', 'http://localhost/apps/index.php'],
			['apps/index.php', 'http://localhost/apps/index.php'],
		];
	}

	public function provideSubDirURLs() {
		return [
			['', 'http://localhost/nextcloud/'],
			['/', 'http://localhost/nextcloud/'],
			['index.php', 'http://localhost/nextcloud/index.php'],
			['/index.php', 'http://localhost/nextcloud/index.php'],
			['/apps/index.php', 'http://localhost/nextcloud/apps/index.php'],
			['apps/index.php', 'http://localhost/nextcloud/apps/index.php'],
		];
	}

	public function testGetBaseUrl() {
		$this->mockBaseUrl();
		\OC::$WEBROOT = '/nextcloud';
		$actual = $this->urlGenerator->getBaseUrl();
		$expected = 'http://localhost/nextcloud';
		$this->assertEquals($expected, $actual);
	}

	public function testGetWebroot() {
		\OC::$WEBROOT = '/nextcloud';
		$actual = $this->urlGenerator->getWebroot();
		$this->assertEquals(\OC::$WEBROOT, $actual);
	}

	/**
	 * @dataProvider provideOCSRoutes
	 */
	public function testLinkToOCSRouteAbsolute(string $route, bool $ignoreFrontController, string $expected): void {
		$this->mockBaseUrl();
		\OC::$WEBROOT = '/nextcloud';
		$this->router->expects($this->once())
			->method('generate')
			->willReturnCallback(function (string $routeName, array $parameters) use ($ignoreFrontController) {
				if ($routeName === 'ocs.core.OCS.getCapabilities') {
					if (!$ignoreFrontController) {
						return '/nextcloud/index.php/ocsapp/cloud/capabilities';
					}
					return '/nextcloud/ocsapp/cloud/capabilities';
				} elseif ($routeName === 'ocs.core.WhatsNew.dismiss') {
					if (!$ignoreFrontController) {
						return '/nextcloud/index.php/ocsapp/core/whatsnew';
					}
					return '/nextcloud/ocsapp/core/whatsnew';
				}
			});
		$result = $this->urlGenerator->linkToOCSRouteAbsolute($route);
		$this->assertEquals($expected, $result);
	}

	public function provideOCSRoutes(): array {
		return [
			['core.OCS.getCapabilities', false, 'http://localhost/nextcloud/ocs/v2.php/cloud/capabilities'],
			['core.OCS.getCapabilities', true, 'http://localhost/nextcloud/ocs/v2.php/cloud/capabilities'],
			['core.WhatsNew.dismiss', false, 'http://localhost/nextcloud/ocs/v2.php/core/whatsnew'],
			['core.WhatsNew.dismiss', true, 'http://localhost/nextcloud/ocs/v2.php/core/whatsnew'],
		];
	}

	private function mockLinkToDefaultPageUrl(bool $ignoreFrontControllerConfig = false) {
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('core', 'defaultpage')
			->willReturn('');

		$this->config->expects($this->once())
			->method('getSystemValueBool')
			->with('htaccess.IgnoreFrontController', $this->anything())
			->willReturn($ignoreFrontControllerConfig);
	}

	public function testLinkToDefaultPageUrlWithRedirectUrlWithoutFrontController() {
		$this->mockBaseUrl();

		$_REQUEST['redirect_url'] = 'myRedirectUrl.com';
		$this->assertSame('http://localhost' . \OC::$WEBROOT . '/myRedirectUrl.com', $this->urlGenerator->linkToDefaultPageUrl());
	}

	public function testLinkToDefaultPageUrlWithRedirectUrlRedirectBypassWithoutFrontController() {
		$this->mockBaseUrl();
		$this->mockLinkToDefaultPageUrl();
		putenv('front_controller_active=false');

		$_REQUEST['redirect_url'] = 'myRedirectUrl.com@foo.com:a';
		$this->assertSame('http://localhost' . \OC::$WEBROOT . '/index.php/apps/dashboard/', $this->urlGenerator->linkToDefaultPageUrl());
	}

	public function testLinkToDefaultPageUrlWithRedirectUrlRedirectBypassWithFrontController() {
		$this->mockBaseUrl();
		$this->mockLinkToDefaultPageUrl();
		putenv('front_controller_active=true');

		$_REQUEST['redirect_url'] = 'myRedirectUrl.com@foo.com:a';
		$this->assertSame('http://localhost' . \OC::$WEBROOT . '/apps/dashboard/', $this->urlGenerator->linkToDefaultPageUrl());
	}

	public function testLinkToDefaultPageUrlWithRedirectUrlWithIgnoreFrontController() {
		$this->mockBaseUrl();
		$this->mockLinkToDefaultPageUrl(true);
		putenv('front_controller_active=false');

		$_REQUEST['redirect_url'] = 'myRedirectUrl.com@foo.com:a';
		$this->assertSame('http://localhost' . \OC::$WEBROOT . '/apps/dashboard/', $this->urlGenerator->linkToDefaultPageUrl());
	}

	public function imagePathProvider(): array {
		return [
			['core', 'favicon-mask.svg', \OC::$WEBROOT . '/core/img/favicon-mask.svg'],
			['files', 'folder.svg', \OC::$WEBROOT . '/apps/files/img/folder.svg'],
		];
	}

	/**
	 * @dataProvider imagePathProvider
	 */
	public function testImagePath(string $appName, string $file, string $result): void {
		$this->assertSame($result, $this->urlGenerator->imagePath($appName, $file));
	}
}
