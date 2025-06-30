<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Updater;

use OC\Updater\VersionCheck;
use OCP\Http\Client\IClientService;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\Server;
use OCP\ServerVersion;
use OCP\Support\Subscription\IRegistry;
use Psr\Log\LoggerInterface;

class VersionCheckTest extends \Test\TestCase {
	/** @var ServerVersion|\PHPUnit\Framework\MockObject\MockObject */
	private $serverVersion;
	/** @var IConfig| \PHPUnit\Framework\MockObject\MockObject */
	private $config;
	/** @var IAppConfig| \PHPUnit\Framework\MockObject\MockObject */
	private $appConfig;
	/** @var VersionCheck | \PHPUnit\Framework\MockObject\MockObject */
	private $updater;
	/** @var IRegistry | \PHPUnit\Framework\Mo2ckObject\MockObject */
	private $registry;
	/** @var LoggerInterface | \PHPUnit\Framework\Mo2ckObject\MockObject */
	private $logger;

	protected function setUp(): void {
		parent::setUp();
		$this->serverVersion = $this->createMock(ServerVersion::class);
		$this->config = $this->createMock(IConfig::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$clientService = $this->createMock(IClientService::class);

		$this->serverVersion->method('getChannel')->willReturn('git');

		$this->registry = $this->createMock(IRegistry::class);
		$this->registry
			->method('delegateHasValidSubscription')
			->willReturn(false);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->updater = $this->getMockBuilder(VersionCheck::class)
			->onlyMethods(['getUrlContent'])
			->setConstructorArgs([
				$this->serverVersion,
				$clientService,
				$this->config,
				$this->appConfig,
				$this->createMock(IUserManager::class),
				$this->registry,
				$this->logger,
			])
			->getMock();
	}

	/**
	 * @param string $baseUrl
	 * @return string
	 */
	private function buildUpdateUrl($baseUrl) {
		$serverVersion = Server::get(ServerVersion::class);
		return $baseUrl . '?version=' . implode('x', $serverVersion->getVersion()) . 'xinstalledatx' . time() . 'x' . $serverVersion->getChannel() . 'xxx' . PHP_MAJOR_VERSION . 'x' . PHP_MINOR_VERSION . 'x' . PHP_RELEASE_VERSION . 'x0x0';
	}

	public function testCheckInCache(): void {
		$expectedResult = [
			'version' => '8.0.4.2',
			'versionstring' => 'ownCloud 8.0.4',
			'url' => 'https://download.example.org/community/owncloud-8.0.4.zip',
			'web' => 'http://doc.example.org/server/8.0/admin_manual/maintenance/upgrade.html',
			'changes' => '',
		];

		$this->config
			->expects($this->once())
			->method('getSystemValueBool')
			->with('has_internet_connection', true)
			->willReturn(true);
		$this->appConfig
			->expects($this->once())
			->method('getValueInt')
			->with('core', 'lastupdatedat')
			->willReturn(time());
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('core', 'lastupdateResult')
			->willReturn(json_encode($expectedResult));

		$this->assertSame($expectedResult, $this->updater->check());
	}

	public function testCheckWithoutUpdateUrl(): void {
		$expectedResult = [
			'version' => '8.0.4.2',
			'versionstring' => 'ownCloud 8.0.4',
			'url' => 'https://download.example.org/community/owncloud-8.0.4.zip',
			'web' => 'http://doc.example.org/server/8.0/admin_manual/maintenance/upgrade.html',
			'changes' => '',
			'autoupdater' => '0',
			'eol' => '1',
		];

		$this->config
			->expects($this->once())
			->method('getSystemValueBool')
			->with('has_internet_connection', true)
			->willReturn(true);
		$this->appConfig
			->expects($this->exactly(2))
			->method('getValueInt')
			->with('core', 'lastupdatedat')
			->willReturnOnConsecutiveCalls(
				0,
				time(),
			);
		$this->config
			->expects($this->exactly(2))
			->method('getAppValue')
			->with('core', 'installedat')
			->willReturn('installedat');
		$this->config
			->expects($this->once())
			->method('getSystemValueString')
			->with('updater.server.url', 'https://updates.nextcloud.com/updater_server/')
			->willReturnArgument(1);
		$this->appConfig
			->expects($this->once())
			->method('setValueInt')
			->with('core', 'lastupdatedat', time());
		$this->config
			->expects($this->once())
			->method('setAppValue')
			->with('core', 'lastupdateResult', json_encode($expectedResult));

		$updateXml = '<?xml version="1.0"?>
<owncloud>
  <version>8.0.4.2</version>
  <versionstring>ownCloud 8.0.4</versionstring>
  <url>https://download.example.org/community/owncloud-8.0.4.zip</url>
  <web>http://doc.example.org/server/8.0/admin_manual/maintenance/upgrade.html</web>
  <autoupdater>0</autoupdater>
  <eol>1</eol>
</owncloud>';
		$this->updater
			->expects($this->once())
			->method('getUrlContent')
			->with($this->buildUpdateUrl('https://updates.nextcloud.com/updater_server/'))
			->willReturn($updateXml);

		$this->assertSame($expectedResult, $this->updater->check());
	}

	public function testCheckWithInvalidXml(): void {
		$this->config
			->expects($this->once())
			->method('getSystemValueBool')
			->with('has_internet_connection', true)
			->willReturn(true);
		$this->appConfig
			->expects($this->exactly(2))
			->method('getValueInt')
			->with('core', 'lastupdatedat')
			->willReturnOnConsecutiveCalls(
				0,
				time(),
			);
		$this->config
			->expects($this->exactly(2))
			->method('getAppValue')
			->with('core', 'installedat')
			->willReturn('installedat');
		$this->config
			->expects($this->once())
			->method('getSystemValueString')
			->with('updater.server.url', 'https://updates.nextcloud.com/updater_server/')
			->willReturnArgument(1);
		$this->appConfig
			->expects($this->once())
			->method('setValueInt')
			->with('core', 'lastupdatedat', time());
		$this->config
			->expects($this->once())
			->method('setAppValue')
			->with('core', 'lastupdateResult', $this->isType('string'));

		$updateXml = 'Invalid XML Response!';
		$this->updater
			->expects($this->once())
			->method('getUrlContent')
			->with($this->buildUpdateUrl('https://updates.nextcloud.com/updater_server/'))
			->willReturn($updateXml);

		$this->assertSame([], $this->updater->check());
	}

	public function testCheckWithEmptyValidXmlResponse(): void {
		$expectedResult = [
			'version' => '',
			'versionstring' => '',
			'url' => '',
			'web' => '',
			'changes' => '',
			'autoupdater' => '',
			'eol' => '0',
		];

		$this->config
			->expects($this->once())
			->method('getSystemValueBool')
			->with('has_internet_connection', true)
			->willReturn(true);
		$this->appConfig
			->expects($this->exactly(2))
			->method('getValueInt')
			->with('core', 'lastupdatedat')
			->willReturnOnConsecutiveCalls(
				0,
				time(),
			);
		$this->config
			->expects($this->exactly(2))
			->method('getAppValue')
			->with('core', 'installedat')
			->willReturn('installedat');
		$this->config
			->expects($this->once())
			->method('getSystemValueString')
			->with('updater.server.url', 'https://updates.nextcloud.com/updater_server/')
			->willReturnArgument(1);
		$this->appConfig
			->expects($this->once())
			->method('setValueInt')
			->with('core', 'lastupdatedat', time());
		$this->config
			->expects($this->once())
			->method('setAppValue')
			->with('core', 'lastupdateResult', $this->isType('string'));

		$updateXml = '<?xml version="1.0"?>
<owncloud>
  <version></version>
  <versionstring></versionstring>
  <url></url>
  <web></web>
  <autoupdater></autoupdater>
</owncloud>';
		$this->updater
			->expects($this->once())
			->method('getUrlContent')
			->with($this->buildUpdateUrl('https://updates.nextcloud.com/updater_server/'))
			->willReturn($updateXml);

		$this->assertSame($expectedResult, $this->updater->check());
	}

	public function testCheckWithEmptyInvalidXmlResponse(): void {
		$expectedResult = [];

		$this->config
			->expects($this->once())
			->method('getSystemValueBool')
			->with('has_internet_connection', true)
			->willReturn(true);
		$this->appConfig
			->expects($this->exactly(2))
			->method('getValueInt')
			->with('core', 'lastupdatedat')
			->willReturnOnConsecutiveCalls(
				0,
				time(),
			);
		$this->config
			->expects($this->exactly(2))
			->method('getAppValue')
			->with('core', 'installedat')
			->willReturn('installedat');
		$this->config
			->expects($this->once())
			->method('getSystemValueString')
			->with('updater.server.url', 'https://updates.nextcloud.com/updater_server/')
			->willReturnArgument(1);
		$this->appConfig
			->expects($this->once())
			->method('setValueInt')
			->with('core', 'lastupdatedat', time());
		$this->config
			->expects($this->once())
			->method('setAppValue')
			->with('core', 'lastupdateResult', $this->isType('string'));

		$updateXml = '';
		$this->updater
			->expects($this->once())
			->method('getUrlContent')
			->with($this->buildUpdateUrl('https://updates.nextcloud.com/updater_server/'))
			->willReturn($updateXml);

		$this->assertSame($expectedResult, $this->updater->check());
	}

	public function testCheckWithMissingAttributeXmlResponse(): void {
		$expectedResult = [
			'version' => '',
			'versionstring' => '',
			'url' => '',
			'web' => '',
			'changes' => '',
			'autoupdater' => '',
			'eol' => '0',
		];

		$this->config
			->expects($this->once())
			->method('getSystemValueBool')
			->with('has_internet_connection', true)
			->willReturn(true);
		$this->appConfig
			->expects($this->exactly(2))
			->method('getValueInt')
			->with('core', 'lastupdatedat')
			->willReturnOnConsecutiveCalls(
				0,
				time(),
			);
		$this->config
			->expects($this->exactly(2))
			->method('getAppValue')
			->with('core', 'installedat')
			->willReturn('installedat');
		$this->config
			->expects($this->once())
			->method('getSystemValueString')
			->with('updater.server.url', 'https://updates.nextcloud.com/updater_server/')
			->willReturnArgument(1);
		$this->appConfig
			->expects($this->once())
			->method('setValueInt')
			->with('core', 'lastupdatedat', time());
		$this->config
			->expects($this->once())
			->method('setAppValue')
			->with('core', 'lastupdateResult', $this->isType('string'));

		// missing autoupdater element should still not fail
		$updateXml = '<?xml version="1.0"?>
<owncloud>
  <version></version>
  <versionstring></versionstring>
  <url></url>
  <web></web>
</owncloud>';
		$this->updater
			->expects($this->once())
			->method('getUrlContent')
			->with($this->buildUpdateUrl('https://updates.nextcloud.com/updater_server/'))
			->willReturn($updateXml);

		$this->assertSame($expectedResult, $this->updater->check());
	}

	public function testNoInternet(): void {
		$this->config
			->expects($this->once())
			->method('getSystemValueBool')
			->with('has_internet_connection', true)
			->willReturn(false);

		$this->assertFalse($this->updater->check());
	}
}
