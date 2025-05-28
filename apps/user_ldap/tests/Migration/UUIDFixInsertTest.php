<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Tests\Migration;

use OCA\User_LDAP\Mapping\GroupMapping;
use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\Migration\UUIDFixInsert;
use OCP\BackgroundJob\IJobList;
use OCP\IConfig;
use OCP\Migration\IOutput;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class UUIDFixInsertTest extends TestCase {
	protected IConfig&MockObject $config;
	protected UserMapping&MockObject $userMapper;
	protected GroupMapping&MockObject $groupMapper;
	protected IJobList&MockObject $jobList;
	protected UUIDFixInsert $job;

	protected function setUp(): void {
		parent::setUp();

		$this->jobList = $this->createMock(IJobList::class);
		$this->config = $this->createMock(IConfig::class);
		$this->userMapper = $this->createMock(UserMapping::class);
		$this->groupMapper = $this->createMock(GroupMapping::class);
		$this->job = new UUIDFixInsert(
			$this->config,
			$this->userMapper,
			$this->groupMapper,
			$this->jobList
		);
	}

	public function testGetName(): void {
		$this->assertSame('Insert UUIDFix background job for user and group in batches', $this->job->getName());
	}

	public static function recordProvider(): array {
		$record = [
			'dn' => 'cn=somerecord,dc=somewhere',
			'name' => 'Something',
			'uuid' => 'AB12-3456-CDEF7-8GH9'
		];

		$userBatches = [
			0 => array_fill(0, 50, $record),
			1 => array_fill(0, 50, $record),
			2 => array_fill(0, 13, $record),
		];

		$groupBatches = [
			0 => array_fill(0, 7, $record),
		];

		return [
			['userBatches' => $userBatches, 'groupBatches' => $groupBatches]
		];
	}

	public static function recordProviderTooLongAndNone(): array {
		$record = [
			'dn' => 'cn=somerecord,dc=somewhere',
			'name' => 'Something',
			'uuid' => 'AB12-3456-CDEF7-8GH9'
		];

		$userBatches = [
			0 => array_fill(0, 50, $record),
			1 => array_fill(0, 40, $record),
			2 => array_fill(0, 32, $record),
			3 => array_fill(0, 32, $record),
			4 => array_fill(0, 23, $record),
		];

		$groupBatches = [0 => []];

		return [
			['userBatches' => $userBatches, 'groupBatches' => $groupBatches]
		];
	}

	/**
	 * @dataProvider recordProvider
	 */
	public function testRun(array $userBatches, array $groupBatches): void {
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('user_ldap', 'installed_version', '1.2.1')
			->willReturn('1.2.0');

		$this->userMapper->expects($this->exactly(3))
			->method('getList')
			->willReturnMap([
				[0, 50, false, $userBatches[0]],
				[50, 50, false, $userBatches[1]],
				[100, 50, false, $userBatches[2]],
			]);

		$this->groupMapper->expects($this->exactly(1))
			->method('getList')
			->with(0, 50)
			->willReturn($groupBatches[0]);

		$this->jobList->expects($this->exactly(4))
			->method('add');

		/** @var IOutput $out */
		$out = $this->createMock(IOutput::class);
		$this->job->run($out);
	}

	/**
	 * @dataProvider recordProviderTooLongAndNone
	 */
	public function testRunWithManyAndNone(array $userBatches, array $groupBatches): void {
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('user_ldap', 'installed_version', '1.2.1')
			->willReturn('1.2.0');

		$this->userMapper->expects($this->exactly(5))
			->method('getList')
			->willReturnMap([
				[0, 50, false, $userBatches[0]],
				[0, 40, false, $userBatches[1]],
				[0, 32, false, $userBatches[2]],
				[32, 32, false, $userBatches[3]],
				[64, 32, false, $userBatches[4]],
			]);

		$this->groupMapper->expects($this->once())
			->method('getList')
			->with(0, 50)
			->willReturn($groupBatches[0]);

		$this->jobList->expects($this->exactly(5))
			->method('add')
			->willReturnOnConsecutiveCalls(
				$this->throwException(new \InvalidArgumentException('Background job arguments can\'t exceed 4000 etc')),
				$this->throwException(new \InvalidArgumentException('Background job arguments can\'t exceed 4000 etc')),
				null,
				null,
				null,
			);

		/** @var IOutput $out */
		$out = $this->createMock(IOutput::class);
		$this->job->run($out);
	}

	public function testDonNotRun(): void {
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('user_ldap', 'installed_version', '1.2.1')
			->willReturn('1.2.1');
		$this->userMapper->expects($this->never())
			->method('getList');
		$this->groupMapper->expects($this->never())
			->method('getList');
		$this->jobList->expects($this->never())
			->method('add');

		/** @var IOutput $out */
		$out = $this->createMock(IOutput::class);
		$this->job->run($out);
	}
}
