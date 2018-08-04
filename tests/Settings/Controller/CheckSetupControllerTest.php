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

namespace Tests\Settings\Controller;

use OC\DB\Connection;
use OC\MemoryInfo;
use OC\Settings\Controller\CheckSetupController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IDateTimeFormatter;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IURLGenerator;
use OC_Util;
use OCP\Lock\ILockingProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Test\TestCase;
use OC\IntegrityCheck\Checker;

/**
 * Class CheckSetupControllerTest
 *
 * @package Tests\Settings\Controller
 */
class CheckSetupControllerTest extends TestCase {
	/** @var CheckSetupController | \PHPUnit_Framework_MockObject_MockObject */
	private $checkSetupController;
	/** @var IRequest | \PHPUnit_Framework_MockObject_MockObject */
	private $request;
	/** @var IConfig | \PHPUnit_Framework_MockObject_MockObject */
	private $config;
	/** @var IClientService | \PHPUnit_Framework_MockObject_MockObject*/
	private $clientService;
	/** @var IURLGenerator | \PHPUnit_Framework_MockObject_MockObject */
	private $urlGenerator;
	/** @var OC_Util */
	private $util;
	/** @var IL10N | \PHPUnit_Framework_MockObject_MockObject */
	private $l10n;
	/** @var ILogger */
	private $logger;
	/** @var Checker|\PHPUnit_Framework_MockObject_MockObject */
	private $checker;
	/** @var EventDispatcher|\PHPUnit_Framework_MockObject_MockObject */
	private $dispatcher;
	/** @var Connection|\PHPUnit_Framework_MockObject_MockObject */
	private $db;
	/** @var ILockingProvider|\PHPUnit_Framework_MockObject_MockObject */
	private $lockingProvider;
	/** @var IDateTimeFormatter|\PHPUnit_Framework_MockObject_MockObject */
	private $dateTimeFormatter;
	/** @var MemoryInfo|MockObject */
	private $memoryInfo;

	public function setUp() {
		parent::setUp();

		$this->request = $this->getMockBuilder(IRequest::class)
			->disableOriginalConstructor()->getMock();
		$this->config = $this->getMockBuilder(IConfig::class)
			->disableOriginalConstructor()->getMock();
		$this->clientService = $this->getMockBuilder(IClientService::class)
			->disableOriginalConstructor()->getMock();
		$this->util = $this->getMockBuilder('\OC_Util')
			->disableOriginalConstructor()->getMock();
		$this->urlGenerator = $this->getMockBuilder(IURLGenerator::class)
			->disableOriginalConstructor()->getMock();
		$this->l10n = $this->getMockBuilder(IL10N::class)
			->disableOriginalConstructor()->getMock();
		$this->l10n->expects($this->any())
			->method('t')
			->will($this->returnCallback(function($message, array $replace) {
				return vsprintf($message, $replace);
			}));
		$this->dispatcher = $this->getMockBuilder(EventDispatcher::class)
			->disableOriginalConstructor()->getMock();
		$this->checker = $this->getMockBuilder('\OC\IntegrityCheck\Checker')
				->disableOriginalConstructor()->getMock();
		$this->logger = $this->getMockBuilder(ILogger::class)->getMock();
		$this->db = $this->getMockBuilder(Connection::class)
			->disableOriginalConstructor()->getMock();
		$this->lockingProvider = $this->getMockBuilder(ILockingProvider::class)->getMock();
		$this->dateTimeFormatter = $this->getMockBuilder(IDateTimeFormatter::class)->getMock();
		$this->memoryInfo = $this->getMockBuilder(MemoryInfo::class)
			->setMethods(['isMemoryLimitSufficient',])
			->getMock();
		$this->checkSetupController = $this->getMockBuilder('\OC\Settings\Controller\CheckSetupController')
			->setConstructorArgs([
				'settings',
				$this->request,
				$this->config,
				$this->clientService,
				$this->urlGenerator,
				$this->util,
				$this->l10n,
				$this->checker,
				$this->logger,
				$this->dispatcher,
				$this->db,
				$this->lockingProvider,
				$this->dateTimeFormatter,
				$this->memoryInfo,
				])
			->setMethods([
				'isReadOnlyConfig',
				'hasValidTransactionIsolationLevel',
				'hasFileinfoInstalled',
				'hasWorkingFileLocking',
				'getLastCronInfo',
				'getSuggestedOverwriteCliURL',
				'getOutdatedCaches',
				'getCurlVersion',
				'isPhpOutdated',
				'isOpcacheProperlySetup',
				'hasFreeTypeSupport',
				'hasMissingIndexes',
				'isSqliteUsed',
				'isPhpMailerUsed',
				'hasOpcacheLoaded',
			])->getMock();
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
		$client->expects($this->any())
			->method('get');

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

	public function testIsInternetConnectionFail() {
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('has_internet_connection', true)
			->will($this->returnValue(true));

		$client = $this->getMockBuilder('\OCP\Http\Client\IClient')
			->disableOriginalConstructor()->getMock();
		$client->expects($this->any())
			->method('get')
			->will($this->throwException(new \Exception()));

		$this->clientService->expects($this->exactly(4))
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

	public function testIsPhpSupportedFalse() {
		$this->checkSetupController
			->expects($this->once())
			->method('isPhpOutdated')
			->willReturn(true);

		$this->assertEquals(
			['eol' => true, 'version' => PHP_VERSION],
			self::invokePrivate($this->checkSetupController, 'isPhpSupported')
		);
	}

	public function testIsPhpSupportedTrue() {
		$this->checkSetupController
			->expects($this->exactly(2))
			->method('isPhpOutdated')
			->willReturn(false);

		$this->assertEquals(
			['eol' => false, 'version' => PHP_VERSION],
			self::invokePrivate($this->checkSetupController, 'isPhpSupported')
		);


		$this->assertEquals(
			['eol' => false, 'version' => PHP_VERSION],
			self::invokePrivate($this->checkSetupController, 'isPhpSupported')
		);
	}

	public function testForwardedForHeadersWorkingFalse() {
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('trusted_proxies', [])
			->willReturn(['1.2.3.4']);
		$this->request->expects($this->once())
			->method('getRemoteAddress')
			->willReturn('1.2.3.4');

		$this->assertFalse(
			self::invokePrivate(
				$this->checkSetupController,
				'forwardedForHeadersWorking'
			)
		);
	}

	public function testForwardedForHeadersWorkingTrue() {
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('trusted_proxies', [])
			->willReturn(['1.2.3.4']);
		$this->request->expects($this->once())
			->method('getRemoteAddress')
			->willReturn('4.3.2.1');

		$this->assertTrue(
			self::invokePrivate(
				$this->checkSetupController,
				'forwardedForHeadersWorking'
			)
		);
	}

	public function testCheck() {
		$this->config->expects($this->at(0))
			->method('getAppValue')
			->with('core', 'cronErrors')
			->willReturn('');
		$this->config->expects($this->at(2))
			->method('getSystemValue')
			->with('memcache.local', null)
			->will($this->returnValue('SomeProvider'));
		$this->config->expects($this->at(3))
			->method('getSystemValue')
			->with('has_internet_connection', true)
			->will($this->returnValue(true));
		$this->config->expects($this->at(4))
			->method('getSystemValue')
			->with('appstoreenabled', true)
			->will($this->returnValue(false));

		$this->request->expects($this->once())
			->method('getRemoteAddress')
			->willReturn('4.3.2.1');

		$client = $this->getMockBuilder('\OCP\Http\Client\IClient')
			->disableOriginalConstructor()->getMock();
		$client->expects($this->at(0))
			->method('get')
			->with('http://www.nextcloud.com/', [])
			->will($this->throwException(new \Exception()));
		$client->expects($this->at(1))
			->method('get')
			->with('http://www.startpage.com/', [])
			->will($this->throwException(new \Exception()));
		$client->expects($this->at(2))
			->method('get')
			->with('http://www.eff.org/', [])
			->will($this->throwException(new \Exception()));
		$client->expects($this->at(3))
			->method('get')
			->with('http://www.edri.org/', [])
			->will($this->throwException(new \Exception()));
		$this->clientService->expects($this->exactly(4))
			->method('newClient')
			->will($this->returnValue($client));
		$this->urlGenerator->expects($this->at(0))
			->method('linkToDocs')
			->with('admin-performance')
			->willReturn('http://docs.example.org/server/go.php?to=admin-performance');
		$this->urlGenerator->expects($this->at(1))
			->method('linkToDocs')
			->with('admin-security')
			->willReturn('https://docs.example.org/server/8.1/admin_manual/configuration_server/hardening.html');
		$this->checkSetupController
			->expects($this->once())
			->method('isPhpOutdated')
			->willReturn(true);
		$this->checkSetupController
			->expects($this->once())
			->method('isOpcacheProperlySetup')
			->willReturn(false);
		$this->urlGenerator->expects($this->at(2))
			->method('linkToDocs')
			->with('admin-reverse-proxy')
			->willReturn('reverse-proxy-doc-link');
		$this->urlGenerator->expects($this->at(3))
			->method('linkToDocs')
			->with('admin-code-integrity')
			->willReturn('http://docs.example.org/server/go.php?to=admin-code-integrity');
		$this->urlGenerator->expects($this->at(4))
			->method('linkToDocs')
			->with('admin-php-opcache')
			->willReturn('http://docs.example.org/server/go.php?to=admin-php-opcache');
		$this->urlGenerator->expects($this->at(5))
			->method('linkToDocs')
			->with('admin-db-conversion')
			->willReturn('http://docs.example.org/server/go.php?to=admin-db-conversion');
		$this->urlGenerator->expects($this->at(6))
			->method('getAbsoluteURL')
			->with('index.php/settings/admin')
			->willReturn('https://server/index.php/settings/admin');
		$this->checkSetupController
			->method('hasFreeTypeSupport')
			->willReturn(false);
		$this->checkSetupController
			->method('hasMissingIndexes')
			->willReturn([]);
		$this->checkSetupController
			->method('getOutdatedCaches')
			->willReturn([]);
		$this->checkSetupController
			->method('isSqliteUsed')
			->willReturn(false);
		$this->checkSetupController
			->expects($this->once())
			->method('isReadOnlyConfig')
			->willReturn(false);
		$this->checkSetupController
			->expects($this->once())
			->method('hasValidTransactionIsolationLevel')
			->willReturn(true);
		$this->checkSetupController
			->expects($this->once())
			->method('hasFileinfoInstalled')
			->willReturn(true);
		$this->checkSetupController
			->expects($this->once())
			->method('hasOpcacheLoaded')
			->willReturn(true);
		$this->checkSetupController
			->expects($this->once())
			->method('hasWorkingFileLocking')
			->willReturn(true);
		$this->checkSetupController
			->expects($this->once())
			->method('getSuggestedOverwriteCliURL')
			->willReturn('');
		$this->checkSetupController
			->expects($this->once())
			->method('getLastCronInfo')
			->willReturn([
				'diffInSeconds' => 123,
				'relativeTime' => '2 hours ago',
				'backgroundJobsUrl' => 'https://example.org',
			]);
		$this->checkSetupController
			->expects($this->once())
			->method('isPhpMailerUsed')
			->willReturn(false);
		$this->checker
			->expects($this->once())
			->method('hasPassedCheck')
			->willReturn(true);
		$this->memoryInfo
			->method('isMemoryLimitSufficient')
			->willReturn(true);

		$expected = new DataResponse(
			[
				'isGetenvServerWorking' => true,
				'isReadOnlyConfig' => false,
				'hasValidTransactionIsolationLevel' => true,
				'outdatedCaches' => [],
				'hasFileinfoInstalled' => true,
				'hasWorkingFileLocking' => true,
				'suggestedOverwriteCliURL' => '',
				'cronInfo' => [
					'diffInSeconds' => 123,
					'relativeTime' => '2 hours ago',
					'backgroundJobsUrl' => 'https://example.org',
				],
				'cronErrors' => [],
				'serverHasInternetConnection' => false,
				'isMemcacheConfigured' => true,
				'memcacheDocs' => 'http://docs.example.org/server/go.php?to=admin-performance',
				'isUrandomAvailable' => self::invokePrivate($this->checkSetupController, 'isUrandomAvailable'),
				'securityDocs' => 'https://docs.example.org/server/8.1/admin_manual/configuration_server/hardening.html',
				'isUsedTlsLibOutdated' => '',
				'phpSupported' => [
					'eol' => true,
					'version' => PHP_VERSION
				],
				'forwardedForHeadersWorking' => true,
				'reverseProxyDocs' => 'reverse-proxy-doc-link',
				'isCorrectMemcachedPHPModuleInstalled' => true,
				'hasPassedCodeIntegrityCheck' => true,
				'codeIntegrityCheckerDocumentation' => 'http://docs.example.org/server/go.php?to=admin-code-integrity',
				'isOpcacheProperlySetup' => false,
				'hasOpcacheLoaded' => true,
				'phpOpcacheDocumentation' => 'http://docs.example.org/server/go.php?to=admin-php-opcache',
				'isSettimelimitAvailable' => true,
				'hasFreeTypeSupport' => false,
				'isSqliteUsed' => false,
				'databaseConversionDocumentation' => 'http://docs.example.org/server/go.php?to=admin-db-conversion',
				'missingIndexes' => [],
				'isPhpMailerUsed' => false,
				'mailSettingsDocumentation' => 'https://server/index.php/settings/admin',
				'isMemoryLimitSufficient' => true,
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
				$this->checker,
				$this->logger,
				$this->dispatcher,
				$this->db,
				$this->lockingProvider,
				$this->dateTimeFormatter,
				$this->memoryInfo,
			])
			->setMethods(null)->getMock();

		$this->assertArrayHasKey('ssl_version', $this->invokePrivate($checkSetupController, 'getCurlVersion'));
	}

	public function testIsUsedTlsLibOutdatedWithAnotherLibrary() {
		$this->config->expects($this->any())
			->method('getSystemValue')
			->will($this->returnValue(true));
		$this->checkSetupController
			->expects($this->once())
			->method('getCurlVersion')
			->will($this->returnValue(['ssl_version' => 'SSLlib']));
		$this->assertSame('', $this->invokePrivate($this->checkSetupController, 'isUsedTlsLibOutdated'));
	}

	public function testIsUsedTlsLibOutdatedWithMisbehavingCurl() {
		$this->config->expects($this->any())
			->method('getSystemValue')
			->will($this->returnValue(true));
		$this->checkSetupController
			->expects($this->once())
			->method('getCurlVersion')
			->will($this->returnValue([]));
		$this->assertSame('', $this->invokePrivate($this->checkSetupController, 'isUsedTlsLibOutdated'));
	}

	public function testIsUsedTlsLibOutdatedWithOlderOpenSsl() {
		$this->config->expects($this->any())
			->method('getSystemValue')
			->will($this->returnValue(true));
		$this->checkSetupController
			->expects($this->once())
			->method('getCurlVersion')
			->will($this->returnValue(['ssl_version' => 'OpenSSL/1.0.1c']));
		$this->assertSame('cURL is using an outdated OpenSSL version (OpenSSL/1.0.1c). Please update your operating system or features such as installing and updating apps via the app store or Federated Cloud Sharing will not work reliably.', $this->invokePrivate($this->checkSetupController, 'isUsedTlsLibOutdated'));
	}

	public function testIsUsedTlsLibOutdatedWithOlderOpenSslAndWithoutAppstore() {
		$this->config
			->expects($this->at(0))
			->method('getSystemValue')
			->with('has_internet_connection', true)
			->will($this->returnValue(true));
		$this->checkSetupController
			->expects($this->once())
			->method('getCurlVersion')
			->will($this->returnValue(['ssl_version' => 'OpenSSL/1.0.1c']));
		$this->assertSame('cURL is using an outdated OpenSSL version (OpenSSL/1.0.1c). Please update your operating system or features such as Federated Cloud Sharing will not work reliably.', $this->invokePrivate($this->checkSetupController, 'isUsedTlsLibOutdated'));
	}

	public function testIsUsedTlsLibOutdatedWithOlderOpenSsl1() {
		$this->config->expects($this->any())
			->method('getSystemValue')
			->will($this->returnValue(true));
		$this->checkSetupController
			->expects($this->once())
			->method('getCurlVersion')
			->will($this->returnValue(['ssl_version' => 'OpenSSL/1.0.2a']));
		$this->assertSame('cURL is using an outdated OpenSSL version (OpenSSL/1.0.2a). Please update your operating system or features such as installing and updating apps via the app store or Federated Cloud Sharing will not work reliably.', $this->invokePrivate($this->checkSetupController, 'isUsedTlsLibOutdated'));
	}

	public function testIsUsedTlsLibOutdatedWithMatchingOpenSslVersion() {
		$this->config->expects($this->any())
			->method('getSystemValue')
			->will($this->returnValue(true));
		$this->checkSetupController
			->expects($this->once())
			->method('getCurlVersion')
			->will($this->returnValue(['ssl_version' => 'OpenSSL/1.0.1d']));
			$this->assertSame('', $this->invokePrivate($this->checkSetupController, 'isUsedTlsLibOutdated'));
	}

	public function testIsUsedTlsLibOutdatedWithMatchingOpenSslVersion1() {
		$this->config->expects($this->any())
			->method('getSystemValue')
			->will($this->returnValue(true));
		$this->checkSetupController
			->expects($this->once())
			->method('getCurlVersion')
			->will($this->returnValue(['ssl_version' => 'OpenSSL/1.0.2b']));
		$this->assertSame('', $this->invokePrivate($this->checkSetupController, 'isUsedTlsLibOutdated'));
	}

	public function testIsBuggyNss400() {
		$this->config->expects($this->any())
			->method('getSystemValue')
			->will($this->returnValue(true));
		$this->checkSetupController
			->expects($this->once())
			->method('getCurlVersion')
			->will($this->returnValue(['ssl_version' => 'NSS/1.0.2b']));
		$client = $this->getMockBuilder('\OCP\Http\Client\IClient')
			->disableOriginalConstructor()->getMock();
		$exception = $this->getMockBuilder('\GuzzleHttp\Exception\ClientException')
			->disableOriginalConstructor()->getMock();
		$response = $this->getMockBuilder(ResponseInterface::class)
			->disableOriginalConstructor()->getMock();
		$response->expects($this->once())
			->method('getStatusCode')
			->will($this->returnValue(400));
		$exception->expects($this->once())
			->method('getResponse')
			->will($this->returnValue($response));

		$client->expects($this->at(0))
			->method('get')
			->with('https://nextcloud.com/', [])
			->will($this->throwException($exception));

		$this->clientService->expects($this->once())
			->method('newClient')
			->will($this->returnValue($client));

		$this->assertSame('cURL is using an outdated NSS version (NSS/1.0.2b). Please update your operating system or features such as installing and updating apps via the app store or Federated Cloud Sharing will not work reliably.', $this->invokePrivate($this->checkSetupController, 'isUsedTlsLibOutdated'));
	}


	public function testIsBuggyNss200() {
		$this->config->expects($this->any())
			->method('getSystemValue')
			->will($this->returnValue(true));
		$this->checkSetupController
			->expects($this->once())
			->method('getCurlVersion')
			->will($this->returnValue(['ssl_version' => 'NSS/1.0.2b']));
		$client = $this->getMockBuilder('\OCP\Http\Client\IClient')
			->disableOriginalConstructor()->getMock();
		$exception = $this->getMockBuilder('\GuzzleHttp\Exception\ClientException')
			->disableOriginalConstructor()->getMock();
		$response = $this->getMockBuilder(ResponseInterface::class)
			->disableOriginalConstructor()->getMock();
		$response->expects($this->once())
			->method('getStatusCode')
			->will($this->returnValue(200));
		$exception->expects($this->once())
			->method('getResponse')
			->will($this->returnValue($response));

		$client->expects($this->at(0))
			->method('get')
			->with('https://nextcloud.com/', [])
			->will($this->throwException($exception));

		$this->clientService->expects($this->once())
			->method('newClient')
			->will($this->returnValue($client));

		$this->assertSame('', $this->invokePrivate($this->checkSetupController, 'isUsedTlsLibOutdated'));
	}

	public function testIsUsedTlsLibOutdatedWithInternetDisabled() {
		$this->config
			->expects($this->at(0))
			->method('getSystemValue')
			->with('has_internet_connection', true)
			->will($this->returnValue(false));
		$this->assertSame('', $this->invokePrivate($this->checkSetupController, 'isUsedTlsLibOutdated'));
	}

	public function testIsUsedTlsLibOutdatedWithAppstoreDisabledAndServerToServerSharingEnabled() {
		$this->config
			->expects($this->at(0))
			->method('getSystemValue')
			->with('has_internet_connection', true)
			->will($this->returnValue(true));
		$this->config
			->expects($this->at(1))
			->method('getSystemValue')
			->with('appstoreenabled', true)
			->will($this->returnValue(false));
		$this->config
			->expects($this->at(2))
			->method('getAppValue')
			->with('files_sharing', 'outgoing_server2server_share_enabled', 'yes')
			->will($this->returnValue('no'));
		$this->config
			->expects($this->at(3))
			->method('getAppValue')
			->with('files_sharing', 'incoming_server2server_share_enabled', 'yes')
			->will($this->returnValue('yes'));

		$this->checkSetupController
			->expects($this->once())
			->method('getCurlVersion')
			->will($this->returnValue([]));
		$this->assertSame('', $this->invokePrivate($this->checkSetupController, 'isUsedTlsLibOutdated'));
	}

	public function testIsUsedTlsLibOutdatedWithAppstoreDisabledAndServerToServerSharingDisabled() {
		$this->config
			->expects($this->at(0))
			->method('getSystemValue')
			->with('has_internet_connection', true)
			->will($this->returnValue(true));
		$this->config
			->expects($this->at(1))
			->method('getSystemValue')
			->with('appstoreenabled', true)
			->will($this->returnValue(false));
		$this->config
			->expects($this->at(2))
			->method('getAppValue')
			->with('files_sharing', 'outgoing_server2server_share_enabled', 'yes')
			->will($this->returnValue('no'));
		$this->config
			->expects($this->at(3))
			->method('getAppValue')
			->with('files_sharing', 'incoming_server2server_share_enabled', 'yes')
			->will($this->returnValue('no'));

		$this->checkSetupController
			->expects($this->never())
			->method('getCurlVersion')
			->will($this->returnValue([]));
		$this->assertSame('', $this->invokePrivate($this->checkSetupController, 'isUsedTlsLibOutdated'));
	}

	public function testRescanFailedIntegrityCheck() {
		$this->checker
			->expects($this->once())
			->method('runInstanceVerification');
		$this->urlGenerator
			->expects($this->once())
			->method('linkToRoute')
			->with('settings.AdminSettings.index')
			->will($this->returnValue('/admin'));

		$expected = new RedirectResponse('/admin');
		$this->assertEquals($expected, $this->checkSetupController->rescanFailedIntegrityCheck());
	}

	public function testGetFailedIntegrityCheckDisabled() {
		$this->checker
			->expects($this->once())
			->method('isCodeCheckEnforced')
			->willReturn(false);

		$expected = new DataDisplayResponse('Integrity checker has been disabled. Integrity cannot be verified.');
		$this->assertEquals($expected, $this->checkSetupController->getFailedIntegrityCheckFiles());
	}


	public function testGetFailedIntegrityCheckFilesWithNoErrorsFound() {
		$this->checker
			->expects($this->once())
			->method('isCodeCheckEnforced')
			->willReturn(true);
		$this->checker
			->expects($this->once())
			->method('getResults')
			->will($this->returnValue([]));

		$expected = new DataDisplayResponse(
				'No errors have been found.',
				Http::STATUS_OK,
				[
						'Content-Type' => 'text/plain',
				]
		);
		$this->assertEquals($expected, $this->checkSetupController->getFailedIntegrityCheckFiles());
	}

	public function testGetFailedIntegrityCheckFilesWithSomeErrorsFound() {
		$this->checker
			->expects($this->once())
			->method('isCodeCheckEnforced')
			->willReturn(true);
		$this->checker
				->expects($this->once())
				->method('getResults')
				->will($this->returnValue(array ( 'core' => array ( 'EXTRA_FILE' => array('/testfile' => array()), 'INVALID_HASH' => array ( '/.idea/workspace.xml' => array ( 'expected' => 'f1c5e2630d784bc9cb02d5a28f55d6f24d06dae2a0fee685f3c2521b050955d9d452769f61454c9ddfa9c308146ade10546cfa829794448eaffbc9a04a29d216', 'current' => 'ce08bf30bcbb879a18b49239a9bec6b8702f52452f88a9d32142cad8d2494d5735e6bfa0d8642b2762c62ca5be49f9bf4ec231d4a230559d4f3e2c471d3ea094', ), '/lib/private/integritycheck/checker.php' => array ( 'expected' => 'c5a03bacae8dedf8b239997901ba1fffd2fe51271d13a00cc4b34b09cca5176397a89fc27381cbb1f72855fa18b69b6f87d7d5685c3b45aee373b09be54742ea', 'current' => '88a3a92c11db91dec1ac3be0e1c87f862c95ba6ffaaaa3f2c3b8f682187c66f07af3a3b557a868342ef4a271218fe1c1e300c478e6c156c5955ed53c40d06585', ), '/settings/controller/checksetupcontroller.php' => array ( 'expected' => '3e1de26ce93c7bfe0ede7c19cb6c93cadc010340225b375607a7178812e9de163179b0dc33809f451e01f491d93f6f5aaca7929685d21594cccf8bda732327c4', 'current' => '09563164f9904a837f9ca0b5f626db56c838e5098e0ccc1d8b935f68fa03a25c5ec6f6b2d9e44a868e8b85764dafd1605522b4af8db0ae269d73432e9a01e63a', ), ), ), 'bookmarks' => array ( 'EXCEPTION' => array ( 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ), ), 'dav' => array ( 'EXCEPTION' => array ( 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ), ), 'encryption' => array ( 'EXCEPTION' => array ( 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ), ), 'external' => array ( 'EXCEPTION' => array ( 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ), ), 'federation' => array ( 'EXCEPTION' => array ( 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ), ), 'files' => array ( 'EXCEPTION' => array ( 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ), ), 'files_antivirus' => array ( 'EXCEPTION' => array ( 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ), ), 'files_drop' => array ( 'EXCEPTION' => array ( 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ), ), 'files_external' => array ( 'EXCEPTION' => array ( 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ), ), 'files_pdfviewer' => array ( 'EXCEPTION' => array ( 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ), ), 'files_sharing' => array ( 'EXCEPTION' => array ( 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ), ), 'files_trashbin' => array ( 'EXCEPTION' => array ( 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ), ), 'files_versions' => array ( 'EXCEPTION' => array ( 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ), ), 'files_videoviewer' => array ( 'EXCEPTION' => array ( 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ), ), 'firstrunwizard' => array ( 'EXCEPTION' => array ( 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ), ), 'gitsmart' => array ( 'EXCEPTION' => array ( 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ), ), 'logreader' => array ( 'EXCEPTION' => array ( 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature could not get verified.', ), ), 'password_policy' => array ( 'EXCEPTION' => array ( 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ), ), 'provisioning_api' => array ( 'EXCEPTION' => array ( 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ), ), 'sketch' => array ( 'EXCEPTION' => array ( 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ), ), 'threatblock' => array ( 'EXCEPTION' => array ( 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ), ), 'two_factor_auth' => array ( 'EXCEPTION' => array ( 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ), ), 'user_ldap' => array ( 'EXCEPTION' => array ( 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ), ), 'user_shibboleth' => array ( 'EXCEPTION' => array ( 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ), ), )));

		$expected = new DataDisplayResponse(
				'Technical information
=====================
The following list covers which files have failed the integrity check. Please read
the previous linked documentation to learn more about the errors and how to fix
them.

Results
=======
- core
	- EXTRA_FILE
		- /testfile
	- INVALID_HASH
		- /.idea/workspace.xml
		- /lib/private/integritycheck/checker.php
		- /settings/controller/checksetupcontroller.php
- bookmarks
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- dav
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- encryption
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- external
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- federation
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- files
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- files_antivirus
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- files_drop
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- files_external
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- files_pdfviewer
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- files_sharing
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- files_trashbin
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- files_versions
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- files_videoviewer
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- firstrunwizard
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- gitsmart
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- logreader
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature could not get verified.
- password_policy
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- provisioning_api
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- sketch
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- threatblock
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- two_factor_auth
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- user_ldap
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- user_shibboleth
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.

Raw output
==========
Array
(
    [core] => Array
        (
            [EXTRA_FILE] => Array
                (
                    [/testfile] => Array
                        (
                        )

                )

            [INVALID_HASH] => Array
                (
                    [/.idea/workspace.xml] => Array
                        (
                            [expected] => f1c5e2630d784bc9cb02d5a28f55d6f24d06dae2a0fee685f3c2521b050955d9d452769f61454c9ddfa9c308146ade10546cfa829794448eaffbc9a04a29d216
                            [current] => ce08bf30bcbb879a18b49239a9bec6b8702f52452f88a9d32142cad8d2494d5735e6bfa0d8642b2762c62ca5be49f9bf4ec231d4a230559d4f3e2c471d3ea094
                        )

                    [/lib/private/integritycheck/checker.php] => Array
                        (
                            [expected] => c5a03bacae8dedf8b239997901ba1fffd2fe51271d13a00cc4b34b09cca5176397a89fc27381cbb1f72855fa18b69b6f87d7d5685c3b45aee373b09be54742ea
                            [current] => 88a3a92c11db91dec1ac3be0e1c87f862c95ba6ffaaaa3f2c3b8f682187c66f07af3a3b557a868342ef4a271218fe1c1e300c478e6c156c5955ed53c40d06585
                        )

                    [/settings/controller/checksetupcontroller.php] => Array
                        (
                            [expected] => 3e1de26ce93c7bfe0ede7c19cb6c93cadc010340225b375607a7178812e9de163179b0dc33809f451e01f491d93f6f5aaca7929685d21594cccf8bda732327c4
                            [current] => 09563164f9904a837f9ca0b5f626db56c838e5098e0ccc1d8b935f68fa03a25c5ec6f6b2d9e44a868e8b85764dafd1605522b4af8db0ae269d73432e9a01e63a
                        )

                )

        )

    [bookmarks] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [dav] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [encryption] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [external] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [federation] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [files] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [files_antivirus] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [files_drop] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [files_external] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [files_pdfviewer] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [files_sharing] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [files_trashbin] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [files_versions] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [files_videoviewer] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [firstrunwizard] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [gitsmart] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [logreader] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature could not get verified.
                )

        )

    [password_policy] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [provisioning_api] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [sketch] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [threatblock] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [two_factor_auth] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [user_ldap] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [user_shibboleth] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

)
',
				Http::STATUS_OK,
				[
						'Content-Type' => 'text/plain',
				]
		);
		$this->assertEquals($expected, $this->checkSetupController->getFailedIntegrityCheckFiles());
	}
}
