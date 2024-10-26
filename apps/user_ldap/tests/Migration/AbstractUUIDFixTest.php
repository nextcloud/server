<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Tests\Migration;

use OCA\User_LDAP\Access;
use OCA\User_LDAP\Helper;
use OCA\User_LDAP\LDAP;
use OCA\User_LDAP\Mapping\AbstractMapping;
use OCA\User_LDAP\Migration\UUIDFix;
use OCA\User_LDAP\Proxy;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use Test\TestCase;

abstract class AbstractUUIDFixTest extends TestCase {
	protected Helper $helper;
	protected IConfig $config;
	protected LDAP $ldap;
	protected AbstractMapping $mapper;
	protected UUIDFix $job;
	protected Proxy $proxy;
	protected Access $access;
	protected ITimeFactory $time;
	protected bool $isUser = true;

	protected function setUp(): void {
		parent::setUp();

		$this->ldap = $this->createMock(LDAP::class);
		$this->config = $this->createMock(IConfig::class);
		$this->access = $this->createMock(Access::class);
		$this->time = $this->createMock(ITimeFactory::class);

		$this->helper = $this->createMock(Helper::class);
		$this->helper->expects($this->any())
			->method('getServerConfigurationPrefixes')
			->with(true)
			->willReturn(['s01', 's03']);
	}

	protected function instantiateJob($className) {
		$this->job = new $className($this->time, $this->mapper, $this->proxy);
		$this->proxy->expects($this->any())
			->method('getLDAPAccess')
			->willReturn($this->access);
	}

	public function testRunSingleRecord(): void {
		$args = [
			'records' => [
				0 => [
					'name' => 'Someone',
					'dn' => 'uid=Someone,dc=Somewhere',
					'uuid' => 'kaput'
				]
			]
		];
		$correctUUID = '4355-AED3-9D73-03AD';

		$this->access->expects($this->once())
			->method('getUUID')
			->with($args['records'][0]['dn'], $this->isUser)
			->willReturn($correctUUID);

		$this->mapper->expects($this->once())
			->method('setUUIDbyDN')
			->with($correctUUID, $args['records'][0]['dn']);

		$this->job->run($args);
	}

	public function testRunValidRecord(): void {
		$correctUUID = '4355-AED3-9D73-03AD';
		$args = [
			'records' => [
				0 => [
					'name' => 'Someone',
					'dn' => 'uid=Someone,dc=Somewhere',
					'uuid' => $correctUUID
				]
			]
		];

		$this->access->expects($this->once())
			->method('getUUID')
			->with($args['records'][0]['dn'], $this->isUser)
			->willReturn($correctUUID);

		$this->mapper->expects($this->never())
			->method('setUUIDbyDN');

		$this->job->run($args);
	}

	public function testRunRemovedRecord(): void {
		$args = [
			'records' => [
				0 => [
					'name' => 'Someone',
					'dn' => 'uid=Someone,dc=Somewhere',
					'uuid' => 'kaput'
				]
			]
		];

		$this->access->expects($this->once())
			->method('getUUID')
			->with($args['records'][0]['dn'], $this->isUser)
			->willReturn(false);

		$this->mapper->expects($this->never())
			->method('setUUIDbyDN');

		$this->job->run($args);
	}

	public function testRunManyRecords(): void {
		$args = [
			'records' => [
				0 => [
					'name' => 'Someone',
					'dn' => 'uid=Someone,dc=Somewhere',
					'uuid' => 'kaput'
				],
				1 => [
					'name' => 'kdslkdsaIdsal',
					'dn' => 'uid=kdslkdsaIdsal,dc=Somewhere',
					'uuid' => 'AED3-4355-03AD-9D73'
				],
				2 => [
					'name' => 'Paperboy',
					'dn' => 'uid=Paperboy,dc=Somewhere',
					'uuid' => 'kaput'
				]
			]
		];
		$correctUUIDs = ['4355-AED3-9D73-03AD', 'AED3-4355-03AD-9D73', 'AED3-9D73-4355-03AD'];

		$this->access->expects($this->exactly(3))
			->method('getUUID')
			->withConsecutive(
				[$args['records'][0]['dn'], $this->isUser],
				[$args['records'][1]['dn'], $this->isUser],
				[$args['records'][2]['dn'], $this->isUser]
			)
			->willReturnOnConsecutiveCalls($correctUUIDs[0], $correctUUIDs[1], $correctUUIDs[2]);

		$this->mapper->expects($this->exactly(2))
			->method('setUUIDbyDN')
			->withConsecutive(
				[$correctUUIDs[0], $args['records'][0]['dn']],
				[$correctUUIDs[2], $args['records'][2]['dn']]
			);

		$this->job->run($args);
	}
}
