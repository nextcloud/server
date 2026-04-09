<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Updater;

use OC\Updater\VersionCheck;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Http\Client\IClientService;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\Server;
use OCP\ServerVersion;
use OCP\Support\Subscription\IRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class VersionCheckTest extends \Test\TestCase {
	private ServerVersion&MockObject $serverVersion;
	private IConfig&MockObject $config;
	private IAppConfig&MockObject $appConfig;
	private VersionCheck&MockObject $updater;
	private IRegistry&MockObject $registry;
	private LoggerInterface&MockObject $logger;
	private ITimeFactory&MockObject $timeFactory;

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
		$this->timeFactory = $this->createMock(ITimeFactory::class);
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
				$this->timeFactory,
			])
			->getMock();
	}

	private function buildUpdateUrl(string $baseUrl, int $lastUpdateDate): string {
		$serverVersion = Server::get(ServerVersion::class);
		return $baseUrl . '?version=' . implode('x', $serverVersion->getVersion()) . 'xinstalledatx' . $lastUpdateDate . 'x' . $serverVersion->getChannel() . 'xxx' . PHP_MAJOR_VERSION . 'x' . PHP_MINOR_VERSION . 'x' . PHP_RELEASE_VERSION . 'x0x0';
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
		$lastUpdateDate = time();
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
				$lastUpdateDate,
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
			->with('core', 'lastupdatedat', $lastUpdateDate);
		$this->config
			->expects($this->once())
			->method('setAppValue')
			->with('core', 'lastupdateResult', json_encode($expectedResult));
		$this->timeFactory
			->method('getTime')
			->willReturn($lastUpdateDate);

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
			->with($this->buildUpdateUrl('https://updates.nextcloud.com/updater_server/', $lastUpdateDate))
			->willReturn($updateXml);

		$this->assertSame($expectedResult, $this->updater->check());
	}

	public function testCheckWithInvalidXml(): void {
		$lastUpdateDate = time();
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
				$lastUpdateDate,
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
			->with('core', 'lastupdatedat', $lastUpdateDate);
		$this->config
			->expects($this->once())
			->method('setAppValue')
			->with('core', 'lastupdateResult', $this->isType('string'));
		$this->timeFactory
			->method('getTime')
			->willReturn($lastUpdateDate);

		$updateXml = 'Invalid XML Response!';
		$this->updater
			->expects($this->once())
			->method('getUrlContent')
			->with($this->buildUpdateUrl('https://updates.nextcloud.com/updater_server/', $lastUpdateDate))
			->willReturn($updateXml);

		$this->assertSame([], $this->updater->check());
	}

	public function testCheckWithEmptyValidXmlResponse(): void {
		$lastUpdateDate = time();
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
				$lastUpdateDate,
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
			->with('core', 'lastupdatedat', $lastUpdateDate);
		$this->config
			->expects($this->once())
			->method('setAppValue')
			->with('core', 'lastupdateResult', $this->isType('string'));
		$this->timeFactory
			->method('getTime')
			->willReturn($lastUpdateDate);

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
			->with($this->buildUpdateUrl('https://updates.nextcloud.com/updater_server/', $lastUpdateDate))
			->willReturn($updateXml);

		$this->assertSame($expectedResult, $this->updater->check());
	}

	public function testCheckWithEmptyInvalidXmlResponse(): void {
		$lastUpdateDate = time();
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
		$this->timeFactory
			->method('getTime')
			->willReturn($lastUpdateDate);

		$updateXml = '';
		$this->updater
			->expects($this->once())
			->method('getUrlContent')
			->with($this->buildUpdateUrl('https://updates.nextcloud.com/updater_server/', $lastUpdateDate))
			->willReturn($updateXml);

		$this->assertSame($expectedResult, $this->updater->check());
	}

	public function testCheckWithMissingAttributeXmlResponse(): void {
		$lastUpdateDate = time();
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
				$lastUpdateDate,
				$lastUpdateDate,
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
			->with('core', 'lastupdatedat', $lastUpdateDate);
		$this->timeFactory
			->method('getTime')
			->willReturn($lastUpdateDate);
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
			->with($this->buildUpdateUrl('https://updates.nextcloud.com/updater_server/', $lastUpdateDate))
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
