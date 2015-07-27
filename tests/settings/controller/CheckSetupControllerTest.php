<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Settings\Controller;

use OCP\AppFramework\Http\DataResponse;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OC_Util;
use Test\TestCase;

/**
 * Class CheckSetupControllerTest
 *
 * @package OC\Settings\Controller
 */
class CheckSetupControllerTest extends TestCase {
	/** @var CheckSetupController */
	private $checkSetupController;
	/** @var IRequest */
	private $request;
	/** @var IConfig */
	private $config;
	/** @var IClientService */
	private $clientService;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var OC_Util */
	private $util;
	/** @var IL10N */
	private $l10n;

	public function setUp() {
		parent::setUp();

		$this->request = $this->getMockBuilder('\OCP\IRequest')
			->disableOriginalConstructor()->getMock();
		$this->config = $this->getMockBuilder('\OCP\IConfig')
			->disableOriginalConstructor()->getMock();
		$this->config = $this->getMockBuilder('\OCP\IConfig')
			->disableOriginalConstructor()->getMock();
		$this->clientService = $this->getMockBuilder('\OCP\Http\Client\IClientService')
			->disableOriginalConstructor()->getMock();
		$this->util = $this->getMockBuilder('\OC_Util')
			->disableOriginalConstructor()->getMock();
		$this->urlGenerator = $this->getMockBuilder('\OCP\IURLGenerator')
			->disableOriginalConstructor()->getMock();
		$this->l10n = $this->getMockBuilder('\OCP\IL10N')
			->disableOriginalConstructor()->getMock();
		$this->l10n->expects($this->any())
			->method('t')
			->will($this->returnCallback(function($message, array $replace) {
				return vsprintf($message, $replace);
			}));
		$this->checkSetupController = $this->getMockBuilder('\OC\Settings\Controller\CheckSetupController')
			->setConstructorArgs([
				'settings',
				$this->request,
				$this->config,
				$this->clientService,
				$this->urlGenerator,
				$this->util,
				$this->l10n,
				])
			->setMethods(['getCurlVersion'])->getMock();
	}

	public function testIsInternetConnectionWorkingDisabledViaConfig() {
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('has_internet_connection', true)
			->will($this->returnValue(false));

		$this->assertFalse(
			self::invokePrivate(
				$this->checkSetupController,
				'isInternetConnectionWorking'
			)
		);
	}

	public function testIsInternetConnectionWorkingCorrectly() {
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('has_internet_connection', true)
			->will($this->returnValue(true));

		$client = $this->getMockBuilder('\OCP\Http\Client\IClient')
			->disableOriginalConstructor()->getMock();
		$client->expects($this->at(0))
			->method('get')
			->with('https://www.owncloud.org/', []);
		$client->expects($this->at(1))
			->method('get')
			->with('http://www.owncloud.org/', []);

		$this->clientService->expects($this->once())
			->method('newClient')
			->will($this->returnValue($client));


		$this->assertTrue(
			self::invokePrivate(
				$this->checkSetupController,
				'isInternetConnectionWorking'
			)
		);
	}

	public function testIsInternetConnectionHttpsFail() {
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('has_internet_connection', true)
			->will($this->returnValue(true));

		$client = $this->getMockBuilder('\OCP\Http\Client\IClient')
			->disableOriginalConstructor()->getMock();
		$client->expects($this->at(0))
			->method('get')
			->with('https://www.owncloud.org/', [])
			->will($this->throwException(new \Exception()));

		$this->clientService->expects($this->once())
			->method('newClient')
			->will($this->returnValue($client));

		$this->assertFalse(
			self::invokePrivate(
				$this->checkSetupController,
				'isInternetConnectionWorking'
			)
		);
	}

	public function testIsInternetConnectionHttpFail() {
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('has_internet_connection', true)
			->will($this->returnValue(true));

		$client = $this->getMockBuilder('\OCP\Http\Client\IClient')
			->disableOriginalConstructor()->getMock();
		$client->expects($this->at(0))
			->method('get')
			->with('https://www.owncloud.org/', []);
		$client->expects($this->at(1))
			->method('get')
			->with('http://www.owncloud.org/', [])
			->will($this->throwException(new \Exception()));

		$this->clientService->expects($this->once())
			->method('newClient')
			->will($this->returnValue($client));

		$this->assertFalse(
			self::invokePrivate(
				$this->checkSetupController,
				'isInternetConnectionWorking'
			)
		);
	}

	public function testIsMemcacheConfiguredFalse() {
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('memcache.local', null)
			->will($this->returnValue(null));

		$this->assertFalse(
			self::invokePrivate(
				$this->checkSetupController,
				'isMemcacheConfigured'
			)
		);
	}

	public function testIsMemcacheConfiguredTrue() {
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('memcache.local', null)
			->will($this->returnValue('SomeProvider'));

		$this->assertTrue(
			self::invokePrivate(
				$this->checkSetupController,
				'isMemcacheConfigured'
			)
		);
	}

	public function testCheck() {
		$this->config->expects($this->at(0))
			->method('getSystemValue')
			->with('has_internet_connection', true)
			->will($this->returnValue(true));
		$this->config->expects($this->at(1))
			->method('getSystemValue')
			->with('memcache.local', null)
			->will($this->returnValue('SomeProvider'));

		$client = $this->getMockBuilder('\OCP\Http\Client\IClient')
			->disableOriginalConstructor()->getMock();
		$client->expects($this->at(0))
			->method('get')
			->with('https://www.owncloud.org/', []);
		$client->expects($this->at(1))
			->method('get')
			->with('http://www.owncloud.org/', [])
			->will($this->throwException(new \Exception()));

		$this->clientService->expects($this->once())
			->method('newClient')
			->will($this->returnValue($client));

		$this->util->expects($this->once())
			->method('isHtaccessWorking')
			->will($this->returnValue(true));
		$this->urlGenerator->expects($this->at(0))
			->method('linkToDocs')
			->with('admin-performance')
			->willReturn('http://doc.owncloud.org/server/go.php?to=admin-performance');
		$this->urlGenerator->expects($this->at(1))
			->method('linkToDocs')
			->with('admin-security')
			->willReturn('https://doc.owncloud.org/server/8.1/admin_manual/configuration_server/hardening.html');

		$expected = new DataResponse(
			[
				'serverHasInternetConnection' => false,
				'dataDirectoryProtected' => true,
				'isMemcacheConfigured' => true,
				'memcacheDocs' => 'http://doc.owncloud.org/server/go.php?to=admin-performance',
				'isUrandomAvailable' => self::invokePrivate($this->checkSetupController, 'isUrandomAvailable'),
				'securityDocs' => 'https://doc.owncloud.org/server/8.1/admin_manual/configuration_server/hardening.html',
				'isUsedTlsLibOutdated' => '',
			]
		);
		$this->assertEquals($expected, $this->checkSetupController->check());
	}

	public function testGetCurlVersion() {
		$checkSetupController = $this->getMockBuilder('\OC\Settings\Controller\CheckSetupController')
			->setConstructorArgs([
				'settings',
				$this->request,
				$this->config,
				$this->clientService,
				$this->urlGenerator,
				$this->util,
				$this->l10n,
			])
			->setMethods(null)->getMock();

		$this->assertArrayHasKey('ssl_version', $checkSetupController->getCurlVersion());
	}

	public function testIsUsedTlsLibOutdatedWithAnotherLibrary() {
		$this->checkSetupController
			->expects($this->once())
			->method('getCurlVersion')
			->will($this->returnValue(['ssl_version' => 'SSLlib']));
		$this->assertSame('', $this->invokePrivate($this->checkSetupController, 'isUsedTlsLibOutdated'));
	}

	public function testIsUsedTlsLibOutdatedWithMisbehavingCurl() {
		$this->checkSetupController
			->expects($this->once())
			->method('getCurlVersion')
			->will($this->returnValue([]));
		$this->assertSame('', $this->invokePrivate($this->checkSetupController, 'isUsedTlsLibOutdated'));
	}

	public function testIsUsedTlsLibOutdatedWithOlderOpenSsl() {
		$this->checkSetupController
			->expects($this->once())
			->method('getCurlVersion')
			->will($this->returnValue(['ssl_version' => 'OpenSSL/1.0.1c']));
		$this->assertSame('cURL is using an outdated OpenSSL version (OpenSSL/1.0.1c). Please update your operating system or features such as installing and updating apps via the app store or Federated Cloud Sharing will not work reliably.', $this->invokePrivate($this->checkSetupController, 'isUsedTlsLibOutdated'));
	}

	public function testIsUsedTlsLibOutdatedWithOlderOpenSsl1() {
		$this->checkSetupController
			->expects($this->once())
			->method('getCurlVersion')
			->will($this->returnValue(['ssl_version' => 'OpenSSL/1.0.2a']));
		$this->assertSame('cURL is using an outdated OpenSSL version (OpenSSL/1.0.2a). Please update your operating system or features such as installing and updating apps via the app store or Federated Cloud Sharing will not work reliably.', $this->invokePrivate($this->checkSetupController, 'isUsedTlsLibOutdated'));
	}

	public function testIsUsedTlsLibOutdatedWithMatchingOpenSslVersion() {
		$this->checkSetupController
			->expects($this->once())
			->method('getCurlVersion')
			->will($this->returnValue(['ssl_version' => 'OpenSSL/1.0.1d']));
			$this->assertSame('', $this->invokePrivate($this->checkSetupController, 'isUsedTlsLibOutdated'));
	}

	public function testIsUsedTlsLibOutdatedWithMatchingOpenSslVersion1() {
		$this->checkSetupController
			->expects($this->once())
			->method('getCurlVersion')
			->will($this->returnValue(['ssl_version' => 'OpenSSL/1.0.2b']));
		$this->assertSame('', $this->invokePrivate($this->checkSetupController, 'isUsedTlsLibOutdated'));
	}

	public function testIsBuggyNss400() {
		$this->checkSetupController
			->expects($this->once())
			->method('getCurlVersion')
			->will($this->returnValue(['ssl_version' => 'NSS/1.0.2b']));
		$client = $this->getMockBuilder('\OCP\Http\Client\IClient')
			->disableOriginalConstructor()->getMock();
		$exception = $this->getMockBuilder('\GuzzleHttp\Exception\ClientException')
			->disableOriginalConstructor()->getMock();
		$response = $this->getMockBuilder('\GuzzleHttp\Message\ResponseInterface')
			->disableOriginalConstructor()->getMock();
		$response->expects($this->once())
			->method('getStatusCode')
			->will($this->returnValue(400));
		$exception->expects($this->once())
			->method('getResponse')
			->will($this->returnValue($response));

		$client->expects($this->at(0))
			->method('get')
			->with('https://www.owncloud.org/', [])
			->will($this->throwException($exception));

		$this->clientService->expects($this->once())
			->method('newClient')
			->will($this->returnValue($client));

		$this->assertSame('cURL is using an outdated NSS version (NSS/1.0.2b). Please update your operating system or features such as installing and updating apps via the app store or Federated Cloud Sharing will not work reliably.', $this->invokePrivate($this->checkSetupController, 'isUsedTlsLibOutdated'));
	}


	public function testIsBuggyNss200() {
		$this->checkSetupController
			->expects($this->once())
			->method('getCurlVersion')
			->will($this->returnValue(['ssl_version' => 'NSS/1.0.2b']));
		$client = $this->getMockBuilder('\OCP\Http\Client\IClient')
			->disableOriginalConstructor()->getMock();
		$exception = $this->getMockBuilder('\GuzzleHttp\Exception\ClientException')
			->disableOriginalConstructor()->getMock();
		$response = $this->getMockBuilder('\GuzzleHttp\Message\ResponseInterface')
			->disableOriginalConstructor()->getMock();
		$response->expects($this->once())
			->method('getStatusCode')
			->will($this->returnValue(200));
		$exception->expects($this->once())
			->method('getResponse')
			->will($this->returnValue($response));

		$client->expects($this->at(0))
			->method('get')
			->with('https://www.owncloud.org/', [])
			->will($this->throwException($exception));

		$this->clientService->expects($this->once())
			->method('newClient')
			->will($this->returnValue($client));

		$this->assertSame('', $this->invokePrivate($this->checkSetupController, 'isUsedTlsLibOutdated'));
	}
}
