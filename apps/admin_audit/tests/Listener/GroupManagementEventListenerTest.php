<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Tests\Listener;

use OCA\AdminAudit\IAuditLogger;
use OCA\AdminAudit\Listener\GroupManagementEventListener;
use OCP\Group\Events\GroupChangedEvent;
use OCP\Group\Events\GroupCreatedEvent;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\Group\Events\SubAdminAddedEvent;
use OCP\Group\Events\SubAdminRemovedEvent;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\IGroup;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class GroupManagementEventListenerTest extends TestCase {
	private IAuditLogger&MockObject $logger;
	private GroupManagementEventListener $listener;
	private IGroup&MockObject $group;
	private IUser&MockObject $user;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(IAuditLogger::class);
		$this->listener = new GroupManagementEventListener($this->logger);

		$this->group = $this->createMock(IGroup::class);
		$this->group->method('getGID')->willReturn('admins');
		$this->user = $this->createMock(IUser::class);
		$this->user->method('getUID')->willReturn('alice');
	}

	private function expectInfo(string $message, string $operation): void {
		$this->logger->expects($this->once())
			->method('info')
			->with($message, ['app' => 'admin_audit', 'operation' => $operation]);
	}

	public function testUserAdded(): void {
		$this->expectInfo('User "alice" added to group "admins"', 'groups.member.added');
		$this->listener->handle(new UserAddedEvent($this->group, $this->user));
	}

	public function testUserRemoved(): void {
		$this->expectInfo('User "alice" removed from group "admins"', 'groups.member.removed');
		$this->listener->handle(new UserRemovedEvent($this->group, $this->user));
	}

	public function testGroupCreated(): void {
		$this->expectInfo('Group created: "admins"', 'groups.group.created');
		$this->listener->handle(new GroupCreatedEvent($this->group));
	}

	public function testGroupDeleted(): void {
		$this->expectInfo('Group deleted: "admins"', 'groups.group.deleted');
		$this->listener->handle(new GroupDeletedEvent($this->group));
	}

	public function testGroupDisplayNameChanged(): void {
		$this->logger->expects($this->once())
			->method('info')
			->with(
				'Display name of group "admins" was changed',
				$this->callback(fn (array $context): bool => $context['app'] === 'admin_audit' && $context['operation'] === 'groups.group.display_name_changed'),
			);

		$this->listener->handle(new GroupChangedEvent($this->group, 'displayName', 'Admins', 'Old admins'));
	}

	public function testGroupChangedOtherFeatureIsIgnored(): void {
		$this->logger->expects($this->never())->method($this->anything());

		$this->listener->handle(new GroupChangedEvent($this->group, 'unsupported', 'value'));
	}

	public function testSubAdminAdded(): void {
		$this->logger->expects($this->once())
			->method('info')
			->with(
				'User "alice" was added as subadmin of group "admins"',
				$this->callback(fn (array $context): bool => $context['app'] === 'admin_audit' && $context['operation'] === 'groups.subadmin.added'),
			);

		$this->listener->handle(new SubAdminAddedEvent($this->group, $this->user));
	}

	public function testSubAdminRemoved(): void {
		$this->logger->expects($this->once())
			->method('info')
			->with(
				'User "alice" was removed as subadmin of group "admins"',
				$this->callback(fn (array $context): bool => $context['app'] === 'admin_audit' && $context['operation'] === 'groups.subadmin.removed'),
			);

		$this->listener->handle(new SubAdminRemovedEvent($this->group, $this->user));
	}
}
