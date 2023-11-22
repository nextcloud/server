<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
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

namespace Test\Updater;

use OC\Updater\VersionCheck;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\Support\Subscription\IRegistry;
use OCP\Util;
use Psr\Log\LoggerInterface;

class VersionCheckTest extends \Test\TestCase {
	/** @var IConfig| \PHPUnit\Framework\MockObject\MockObject */
	private $config;
	/** @var VersionCheck | \PHPUnit\Framework\MockObject\MockObject*/
	private $updater;
	/** @var IRegistry | \PHPUnit\Framework\Mo2ckObject\MockObject*/
	private $registry;
	/** @var LoggerInterface | \PHPUnit\Framework\Mo2ckObject\MockObject*/
	private $logger;

	protected function setUp(): void {
		parent::setUp();
		$this->config = $this->getMockBuilder(IConfig::class)
			->disableOriginalConstructor()
			->getMock();
		$clientService = $this->getMockBuilder(IClientService::class)
			->disableOriginalConstructor()
			->getMock();

		$this->registry = $this->createMock(IRegistry::class);
		$this->registry
			->method('delegateHasValidSubscription')
			->willReturn(false);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->updater = $this->getMockBuilder(VersionCheck::class)
			->setMethods(['getUrlContent'])
			->setConstructorArgs([
				$clientService,
				$this->config,
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
		return $baseUrl . '?version='.implode('x', Util::getVersion()).'xinstalledatxlastupdatedatx'.\OC_Util::getChannel().'xxx'.PHP_MAJOR_VERSION.'x'.PHP_MINOR_VERSION.'x'.PHP_RELEASE_VERSION.'x0x0';
	}

	public function testCheckInCache() {
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
		$this->config
			->expects($this->exactly(2))
			->method('getAppValue')
			->withConsecutive(
				['core', 'lastupdatedat'],
				['core', 'lastupdateResult']
			)
			->willReturnOnConsecutiveCalls(
				time(),
				json_encode($expectedResult)
			);

		$this->assertSame($expectedResult, $this->updater->check());
	}

	public function testCheckWithoutUpdateUrl() {
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
		$this->config
			->expects($this->exactly(4))
			->method('getAppValue')
			->withConsecutive(
				['core', 'lastupdatedat'],
				['core', 'installedat'],
				['core', 'installedat'],
				['core', 'lastupdatedat'],
			)
			->willReturnOnConsecutiveCalls(
				'0',
				'installedat',
				'installedat',
				'lastupdatedat',
			);
		$this->config
			->expects($this->once())
			->method('getSystemValueString')
			->with('updater.server.url', 'https://updates.nextcloud.com/updater_server/')
			->willReturnArgument(1);
		$this->config
			->expects($this->exactly(2))
			->method('setAppValue')
			->withConsecutive(
				['core', 'lastupdatedat', $this->isType('string')],
				['core', 'lastupdateResult', json_encode($expectedResult)]
			);

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

	public function testCheckWithInvalidXml() {
		$this->config
			->expects($this->once())
			->method('getSystemValueBool')
			->with('has_internet_connection', true)
			->willReturn(true);
		$this->config
			->expects($this->exactly(4))
			->method('getAppValue')
			->withConsecutive(
				['core', 'lastupdatedat'],
				['core', 'installedat'],
				['core', 'installedat'],
				['core', 'lastupdatedat'],
			)
			->willReturnOnConsecutiveCalls(
				'0',
				'installedat',
				'installedat',
				'lastupdatedat',
			);
		$this->config
			->expects($this->once())
			->method('getSystemValueString')
			->with('updater.server.url', 'https://updates.nextcloud.com/updater_server/')
			->willReturnArgument(1);
		$this->config
			->expects($this->exactly(2))
			->method('setAppValue')
			->withConsecutive(
				['core', 'lastupdatedat', $this->isType('string')],
				['core', 'lastupdateResult', '[]']
			);

		$updateXml = 'Invalid XML Response!';
		$this->updater
			->expects($this->once())
			->method('getUrlContent')
			->with($this->buildUpdateUrl('https://updates.nextcloud.com/updater_server/'))
			->willReturn($updateXml);

		$this->assertSame([], $this->updater->check());
	}

	public function testCheckWithEmptyValidXmlResponse() {
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
		$this->config
			->expects($this->exactly(4))
			->method('getAppValue')
			->withConsecutive(
				['core', 'lastupdatedat'],
				['core', 'installedat'],
				['core', 'installedat'],
				['core', 'lastupdatedat'],
			)
			->willReturnOnConsecutiveCalls(
				'0',
				'installedat',
				'installedat',
				'lastupdatedat',
			);
		$this->config
			->expects($this->once())
			->method('getSystemValueString')
			->with('updater.server.url', 'https://updates.nextcloud.com/updater_server/')
			->willReturnArgument(1);
		$this->config
			->expects($this->exactly(2))
			->method('setAppValue')
			->withConsecutive(
				['core', 'lastupdatedat', $this->isType('string')],
				['core', 'lastupdateResult', $this->isType('string')]
			);

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

	public function testCheckWithEmptyInvalidXmlResponse() {
		$expectedResult = [];

		$this->config
			->expects($this->once())
			->method('getSystemValueBool')
			->with('has_internet_connection', true)
			->willReturn(true);
		$this->config
			->expects($this->exactly(4))
			->method('getAppValue')
			->withConsecutive(
				['core', 'lastupdatedat'],
				['core', 'installedat'],
				['core', 'installedat'],
				['core', 'lastupdatedat'],
			)
			->willReturnOnConsecutiveCalls(
				'0',
				'installedat',
				'installedat',
				'lastupdatedat',
			);
		$this->config
			->expects($this->once())
			->method('getSystemValueString')
			->with('updater.server.url', 'https://updates.nextcloud.com/updater_server/')
			->willReturnArgument(1);
		$this->config
			->expects($this->exactly(2))
			->method('setAppValue')
			->withConsecutive(
				['core', 'lastupdatedat', $this->isType('string')],
				['core', 'lastupdateResult', json_encode($expectedResult)]
			);

		$updateXml = '';
		$this->updater
			->expects($this->once())
			->method('getUrlContent')
			->with($this->buildUpdateUrl('https://updates.nextcloud.com/updater_server/'))
			->willReturn($updateXml);

		$this->assertSame($expectedResult, $this->updater->check());
	}

	public function testCheckWithMissingAttributeXmlResponse() {
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
		$this->config
			->expects($this->exactly(4))
			->method('getAppValue')
			->withConsecutive(
				['core', 'lastupdatedat'],
				['core', 'installedat'],
				['core', 'installedat'],
				['core', 'lastupdatedat'],
			)
			->willReturnOnConsecutiveCalls(
				'0',
				'installedat',
				'installedat',
				'lastupdatedat',
			);
		$this->config
			->expects($this->once())
			->method('getSystemValueString')
			->with('updater.server.url', 'https://updates.nextcloud.com/updater_server/')
			->willReturnArgument(1);
		$this->config
			->expects($this->exactly(2))
			->method('setAppValue')
			->withConsecutive(
				['core', 'lastupdatedat', $this->isType('string')],
				['core', 'lastupdateResult', $this->isType('string')]
			);

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

	public function testNoInternet() {
		$this->config
			->expects($this->once())
			->method('getSystemValueBool')
			->with('has_internet_connection', true)
			->willReturn(false);

		$this->assertFalse($this->updater->check());
	}
}
