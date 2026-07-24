<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

declare(strict_types=1);

namespace OCA\Federation\Tests\User;

use OC\User\Backend as Base;
use OCA\Federation\User\Backend;
use OCP\Server;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class BackendTest extends TestCase {
	private Backend $backend;
	private string $userId;

	protected function setUp(): void {
		parent::setUp();

		$this->backend = Server::get(Backend::class);
		$this->userId = 'foo@example.com';
	}

	protected function tearDown(): void {
		parent::tearDown();
	}

	public function testGetBackendName(): void {
		$this->assertEquals($this->backend->getBackendName(), 'federation');
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataTestImplementsActions')]
	public function testImplementsActions(int $action, bool $implemented): void {
		$this->assertEquals($this->backend->implementsActions($action), $implemented);
	}

	public static function dataTestImplementsActions(): array {
		return [
			[Base::CREATE_USER, false],
			[Base::SET_PASSWORD, false],
			[Base::CHECK_PASSWORD, false],
			[Base::GET_HOME, false],
			[Base::GET_DISPLAYNAME, true],
			[Base::SET_DISPLAYNAME, false],
			[Base::PROVIDE_AVATAR, false],
			[Base::COUNT_USERS, false],
		];
	}

	public function testDeleteUser(): void {
		$this->assertFalse($this->backend->deleteUser($this->userId));
	}

	public function testGetUsers(): void {
		$this->assertEquals($this->backend->getUsers(), []);
	}

	public function testUserExists(): void {
		$this->assertTrue($this->backend->userExists($this->userId));
		$this->assertFalse($this->backend->userExists('foo'));
	}

	public function testGetDisplayName(): void {
		$this->assertEquals($this->backend->getDisplayName($this->userId), $this->userId);

		$this->expectException(\InvalidArgumentException::class);
		$this->backend->getDisplayName('foo');
	}

	public function testGetDisplayNames(): void {
		$this->assertEquals($this->backend->getDisplayNames(), []);
	}

	public function testHasUserListings(): void {
		$this->assertFalse($this->backend->hasUserListings());
	}
}
