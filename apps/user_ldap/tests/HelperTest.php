<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Tests;

use OCA\User_LDAP\Helper;
use OCP\IConfig;

/**
 * @group DB
 */
class HelperTest extends \Test\TestCase {

	/** @var IConfig|\PHPUnit\Framework\MockObject\MockObject */
	private $config;

	/** @var Helper */
	private $helper;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->helper = new Helper($this->config, \OC::$server->getDatabaseConnection());
	}

	public function testGetServerConfigurationPrefixes(): void {
		$this->config->method('getAppKeys')
			->with($this->equalTo('user_ldap'))
			->willReturn([
				'foo',
				'ldap_configuration_active',
				's1ldap_configuration_active',
			]);

		$result = $this->helper->getServerConfigurationPrefixes(false);

		$this->assertEquals(['', 's1'], $result);
	}

	public function testGetServerConfigurationPrefixesActive(): void {
		$this->config->method('getAppKeys')
			->with($this->equalTo('user_ldap'))
			->willReturn([
				'foo',
				'ldap_configuration_active',
				's1ldap_configuration_active',
			]);

		$this->config->method('getAppValue')
			->willReturnCallback(function ($app, $key, $default) {
				if ($app !== 'user_ldap') {
					$this->fail('wrong app');
				}
				if ($key === 's1ldap_configuration_active') {
					return '1';
				}
				return $default;
			});

		$result = $this->helper->getServerConfigurationPrefixes(true);

		$this->assertEquals(['s1'], $result);
	}

	public function testGetServerConfigurationHost(): void {
		$this->config->method('getAppKeys')
			->with($this->equalTo('user_ldap'))
			->willReturn([
				'foo',
				'ldap_host',
				's1ldap_host',
				's02ldap_host',
			]);

		$this->config->method('getAppValue')
			->willReturnCallback(function ($app, $key, $default) {
				if ($app !== 'user_ldap') {
					$this->fail('wrong app');
				}
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
