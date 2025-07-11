<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Tests;

use OCA\User_LDAP\Helper;
use OCP\IAppConfig;
use OCP\IDBConnection;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @group DB
 */
class HelperTest extends \Test\TestCase {
	private IAppConfig&MockObject $appConfig;

	private Helper $helper;

	protected function setUp(): void {
		parent::setUp();

		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->helper = new Helper(
			$this->appConfig,
			Server::get(IDBConnection::class)
		);
	}

	public function testGetServerConfigurationPrefixes(): void {
		$this->appConfig->method('getKeys')
			->with('user_ldap')
			->willReturn([
				'foo',
				'ldap_configuration_active',
				's1ldap_configuration_active',
			]);

		$this->appConfig->method('getValueArray')
			->with('user_ldap', 'configuration_prefixes')
			-> willReturnArgument(2);

		$result = $this->helper->getServerConfigurationPrefixes(false);

		$this->assertEquals(['', 's1'], $result);
	}

	public function testGetServerConfigurationPrefixesActive(): void {
		$this->appConfig->method('getKeys')
			->with('user_ldap')
			->willReturn([
				'foo',
				'ldap_configuration_active',
				's1ldap_configuration_active',
			]);

		$this->appConfig->method('getValueArray')
			->with('user_ldap', 'configuration_prefixes')
			-> willReturnArgument(2);

		$this->appConfig->method('getValueString')
			->willReturnCallback(function ($app, $key, $default) {
				if ($key === 's1ldap_configuration_active') {
					return '1';
				}
				return $default;
			});

		$result = $this->helper->getServerConfigurationPrefixes(true);

		$this->assertEquals(['s1'], $result);
	}

	public function testGetServerConfigurationHostFromAppKeys(): void {
		$this->appConfig->method('getKeys')
			->with('user_ldap')
			->willReturn([
				'foo',
				'ldap_host',
				's1ldap_host',
				's02ldap_host',
				'ldap_configuration_active',
				's1ldap_configuration_active',
				's02ldap_configuration_active',
			]);

		$this->appConfig->method('getValueArray')
			->with('user_ldap', 'configuration_prefixes')
			-> willReturnArgument(2);

		$this->appConfig->method('getValueString')
			->willReturnCallback(function ($app, $key, $default) {
				if ($key === 'ldap_host') {
					return 'example.com';
				}
				if ($key === 's1ldap_host') {
					return 'foo.bar.com';
				}
				return $default;
			});

		$result = $this->helper->getServerConfigurationHosts();

		$this->assertEquals([
			'' => 'example.com',
			's1' => 'foo.bar.com',
			's02' => '',
		], $result);
	}

	public function testGetServerConfigurationHost(): void {
		$this->appConfig
			->expects(self::never())
			->method('getKeys');

		$this->appConfig->method('getValueArray')
			->with('user_ldap', 'configuration_prefixes')
			-> willReturn([
				'',
				's1',
				's02',
			]);

		$this->appConfig->method('getValueString')
			->willReturnCallback(function ($app, $key, $default) {
				if ($key === 'ldap_host') {
					return 'example.com';
				}
				if ($key === 's1ldap_host') {
					return 'foo.bar.com';
				}
				return $default;
			});

		$result = $this->helper->getServerConfigurationHosts();

		$this->assertEquals([
			'' => 'example.com',
			's1' => 'foo.bar.com',
			's02' => '',
		], $result);
	}
}
