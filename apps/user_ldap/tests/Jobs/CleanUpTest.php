<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP\Tests\Jobs;

use Exception;
use OCA\User_LDAP\Helper;
use OCA\User_LDAP\Jobs\CleanUp;
use OCA\User_LDAP\User\DeletedUsersIndex;
use OCA\User_LDAP\User_Proxy;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use OCP\IDBConnection;
use Test\TestCase;

class CleanUpTest extends TestCase {
	protected CleanUp $bgJob;
	protected array $mocks;

	public function setUp(): void {
		parent::setUp();
		$this->createMocks();
		$this->bgJob = new CleanUp($this->mocks['timeFactory'], $this->mocks['userBackend'], $this->mocks['deletedUsersIndex']);
		$this->bgJob->setArguments($this->mocks);
	}

	protected function createMocks(): void {
		$this->mocks = [];
		$this->mocks['userBackend'] = $this->createMock(User_Proxy::class);
		$this->mocks['deletedUsersIndex'] = $this->createMock(DeletedUsersIndex::class);
		$this->mocks['ocConfig'] = $this->createMock(IConfig::class);
		$this->mocks['db'] = $this->createMock(IDBConnection::class);
		$this->mocks['helper'] = $this->createMock(Helper::class);
		$this->mocks['timeFactory'] = $this->createMock(ITimeFactory::class);
	}

	/**
	 * clean up job must not run when there are disabled configurations
	 */
	public function test_runNotAllowedByDisabledConfigurations(): void {
		$this->mocks['helper']->expects($this->once())
			->method('haveDisabledConfigurations')
			->willReturn(true);

		$this->mocks['ocConfig']->expects($this->never())
			->method('getSystemValue');

		$result = $this->bgJob->isCleanUpAllowed();
		$this->assertSame(false, $result);
	}

	/**
	 * clean up job must not run when LDAP Helper is broken i.e.
	 * returning unexpected results
	 */
	public function test_runNotAllowedByBrokenHelper(): void {
		$this->mocks['helper']->expects($this->once())
			->method('haveDisabledConfigurations')
			->will($this->throwException(new Exception()));

		$this->mocks['ocConfig']->expects($this->never())
			->method('getSystemValue');

		$result = $this->bgJob->isCleanUpAllowed();
		$this->assertSame(false, $result);
	}

	/**
	 * clean up job must not run when it is not enabled
	 */
	public function test_runNotAllowedBySysConfig(): void {
		$this->mocks['helper']->expects($this->once())
			->method('haveDisabledConfigurations')
			->willReturn(false);

		$this->mocks['ocConfig']->expects($this->once())
			->method('getSystemValue')
			->willReturn(false);

		$result = $this->bgJob->isCleanUpAllowed();
		$this->assertSame(false, $result);
	}

	/**
	 * clean up job is allowed to run
	 */
	public function test_runIsAllowed(): void {
		$this->mocks['helper']->expects($this->once())
			->method('haveDisabledConfigurations')
			->willReturn(false);

		$this->mocks['ocConfig']->expects($this->once())
			->method('getSystemValue')
			->willReturn(true);

		$result = $this->bgJob->isCleanUpAllowed();
		$this->assertSame(true, $result);
	}

	/**
	 * check whether offset will be reset when it needs to
	 */
	public function test_OffsetResetIsNecessary(): void {
		$result = $this->bgJob->isOffsetResetNecessary($this->bgJob->getChunkSize() - 1);
		$this->assertSame(true, $result);
	}

	/**
	 * make sure offset is not reset when it is not due
	 */
	public function test_OffsetResetIsNotNecessary(): void {
		$result = $this->bgJob->isOffsetResetNecessary($this->bgJob->getChunkSize());
		$this->assertSame(false, $result);
	}
}
