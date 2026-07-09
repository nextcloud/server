<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Tests\Core\Listener;

use OC\Share20\ShareDisableChecker;
use OCP\Cache\CappedMemoryCache;
use OCP\Files\ISetupManager;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\Interaction\Actions\ShareAction;
use OCP\Interaction\Receivers\EmailReceiver;
use OCP\Interaction\Receivers\GroupReceiver;
use OCP\Interaction\Receivers\LinkReceiver;
use OCP\Interaction\Receivers\RemoteGroupReceiver;
use OCP\Interaction\Receivers\RemoteUserReceiver;
use OCP\Interaction\Receivers\UserReceiver;
use OCP\Interaction\RestrictInteractionEvent;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;

#[Group('DB')]
final class RestrictInteractionListenerTest extends TestCase {
	private IUser $user;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$user = Server::get(IUserManager::class)->createUser('user', 'password');
		$this->assertNotFalse($user);
		$this->user = $user;

		Server::get(ISetupManager::class)->setupForUser($user);

		/** @var CappedMemoryCache $cache */
		$cache = self::invokePrivate(Server::get(ShareDisableChecker::class), 'sharingDisabledForUsersCache');
		$cache->clear();
	}

	#[\Override]
	protected function tearDown(): void {
		Server::get(ISetupManager::class)->tearDown();

		$this->assertTrue($this->user->delete());

		parent::tearDown();
	}

	/** @psalm-suppress DeprecatedMethod The configs are not migrated to IAppConfig, so using deprecated IConfig is required for now. */
	public function testShareActionApiDisabled(): void {
		$config = Server::get(IConfig::class);
		$config->setAppValue('core', 'shareapi_enabled', 'no');

		$event = new RestrictInteractionEvent($this->user->getUID(), $this->user, [], new ShareAction(), []);
		$this->assertEquals('Sharing is not allowed.', $event->isInteractionRestricted());

		$config->deleteAppValue('core', 'shareapi_enabled');
	}

	/** @psalm-suppress DeprecatedMethod The configs are not migrated to IAppConfig, so using deprecated IConfig is required for now. */
	public function testShareActionDisabledForUser(): void {
		$group = Server::get(IGroupManager::class)->createGroup('group');
		$this->assertNotNull($group);
		$group->addUser($this->user);

		$config = Server::get(IConfig::class);
		$config->setAppValue('core', 'shareapi_exclude_groups', 'yes');
		$config->setAppValue('core', 'shareapi_exclude_groups_list', json_encode([$group->getGID()], JSON_THROW_ON_ERROR));

		$event = new RestrictInteractionEvent($this->user->getUID(), $this->user, [], new ShareAction(), []);
		$this->assertEquals('Sharing is not allowed for you.', $event->isInteractionRestricted());

		$config->deleteAppValue('core', 'shareapi_exclude_groups');
		$config->deleteAppValue('core', 'shareapi_exclude_groups_list');

		$this->assertTrue($group->delete());
	}

	/** @psalm-suppress DeprecatedMethod The configs are not migrated to IAppConfig, so using deprecated IConfig is required for now. */
	public function testShareActionUserReceiverGroupMembersOnly(): void {
		$user2 = Server::get(IUserManager::class)->createUser('user2', 'password');
		$this->assertNotFalse($user2);

		$group = Server::get(IGroupManager::class)->createGroup('group');
		$this->assertNotNull($group);
		$group->addUser($this->user);

		$config = Server::get(IConfig::class);
		$config->setAppValue('core', 'shareapi_only_share_with_group_members', 'yes');

		$event = new RestrictInteractionEvent($this->user->getUID(), $this->user, [], new ShareAction(), [new UserReceiver($user2->getUID(), $user2)]);
		$this->assertEquals('Sharing is only allowed with group members.', $event->isInteractionRestricted());

		$config->deleteAppValue('core', 'shareapi_only_share_with_group_members');

		$this->assertTrue($group->delete());
		$this->assertTrue($user2->delete());
	}

	/** @psalm-suppress DeprecatedMethod The configs are not migrated to IAppConfig, so using deprecated IConfig is required for now. */
	public function testShareActionUserReceiverGroupMembersOnlyExclude(): void {
		$user2 = Server::get(IUserManager::class)->createUser('user2', 'password');
		$this->assertNotFalse($user2);

		$group = Server::get(IGroupManager::class)->createGroup('group');
		$this->assertNotNull($group);
		$group->addUser($this->user);
		$group->addUser($user2);

		$config = Server::get(IConfig::class);
		$config->setAppValue('core', 'shareapi_only_share_with_group_members', 'yes');
		$config->setAppValue('core', 'shareapi_only_share_with_group_members_exclude_group_list', json_encode([$group->getGID()], JSON_THROW_ON_ERROR));

		$event = new RestrictInteractionEvent($this->user->getUID(), $this->user, [], new ShareAction(), [new UserReceiver($user2->getUID(), $user2)]);
		$this->assertEquals('Sharing is only allowed with group members.', $event->isInteractionRestricted());

		$config->deleteAppValue('core', 'shareapi_only_share_with_group_members');
		$config->deleteAppValue('core', 'shareapi_only_share_with_group_members_exclude_group_list');

		$this->assertTrue($group->delete());
		$this->assertTrue($user2->delete());
	}

	/** @psalm-suppress DeprecatedMethod The configs are not migrated to IAppConfig, so using deprecated IConfig is required for now. */
	public function testShareActionGroupReceiverGroupMembersOnly(): void {
		$group = Server::get(IGroupManager::class)->createGroup('group');
		$this->assertNotNull($group);

		$config = Server::get(IConfig::class);
		$config->setAppValue('core', 'shareapi_only_share_with_group_members', 'yes');

		$event = new RestrictInteractionEvent($this->user->getUID(), $this->user, [], new ShareAction(), [new GroupReceiver($group->getGID(), $group)]);
		$this->assertEquals('Sharing is only allowed within your own groups.', $event->isInteractionRestricted());

		$config->deleteAppValue('core', 'shareapi_only_share_with_group_members');

		$this->assertTrue($group->delete());
	}

	/** @psalm-suppress DeprecatedMethod The configs are not migrated to IAppConfig, so using deprecated IConfig is required for now. */
	public function testShareActionGroupReceiverGroupMembersOnlyExclude(): void {
		$group = Server::get(IGroupManager::class)->createGroup('group');
		$this->assertNotNull($group);
		$group->addUser($this->user);

		$config = Server::get(IConfig::class);
		$config->setAppValue('core', 'shareapi_only_share_with_group_members', 'yes');
		$config->setAppValue('core', 'shareapi_only_share_with_group_members_exclude_group_list', json_encode([$group->getGID()], JSON_THROW_ON_ERROR));

		$event = new RestrictInteractionEvent($this->user->getUID(), $this->user, [], new ShareAction(), [new GroupReceiver($group->getGID(), $group)]);
		$this->assertEquals('Sharing is only allowed within your own groups.', $event->isInteractionRestricted());

		$config->deleteAppValue('core', 'shareapi_only_share_with_group_members');
		$config->deleteAppValue('core', 'shareapi_only_share_with_group_members_exclude_group_list');

		$this->assertTrue($group->delete());
	}

	/** @psalm-suppress DeprecatedMethod The configs are not migrated to IAppConfig, so using deprecated IConfig is required for now. */
	public function testShareActionGroupReceiverGroupSharingDisabled(): void {
		$group = Server::get(IGroupManager::class)->createGroup('group');
		$this->assertNotNull($group);
		$group->addUser($this->user);

		$config = Server::get(IConfig::class);
		$config->setAppValue('core', 'shareapi_allow_group_sharing', 'no');

		$event = new RestrictInteractionEvent($this->user->getUID(), $this->user, [], new ShareAction(), [new GroupReceiver($group->getGID(), $group)]);
		$this->assertEquals('Group sharing is not allowed.', $event->isInteractionRestricted());

		$config->deleteAppValue('core', 'shareapi_allow_group_sharing');

		$this->assertTrue($group->delete());
	}

	/** @psalm-suppress DeprecatedMethod The configs are not migrated to IAppConfig, so using deprecated IConfig is required for now. */
	public function testShareActionLinkEmailReceiverLinkSharingDisabled(): void {
		$config = Server::get(IConfig::class);
		$config->setAppValue('core', 'shareapi_allow_links', 'no');

		foreach ([
			new LinkReceiver(),
			new EmailReceiver(''),
		] as $receiver) {
			$event = new RestrictInteractionEvent($this->user->getUID(), $this->user, [], new ShareAction(), [$receiver]);
			$this->assertEquals('Public link sharing is not allowed.', $event->isInteractionRestricted());
		}

		$config->deleteAppValue('core', 'shareapi_allow_links');
	}

	/** @psalm-suppress DeprecatedMethod The configs are not migrated to IAppConfig, so using deprecated IConfig is required for now. */
	public function testShareActionRemoteUserReceiverServer2ServerSharingDisabled(): void {
		$config = Server::get(IConfig::class);
		$config->setAppValue('files_sharing', 'outgoing_server2server_share_enabled', 'no');

		$event = new RestrictInteractionEvent($this->user->getUID(), $this->user, [], new ShareAction(), [new RemoteUserReceiver('')]);
		$this->assertEquals('Sharing to remote users is not allowed.', $event->isInteractionRestricted());

		$config->deleteAppValue('files_sharing', 'outgoing_server2server_share_enabled');
	}

	/** @psalm-suppress DeprecatedMethod The configs are not migrated to IAppConfig, so using deprecated IConfig is required for now. */
	public function testShareActionRemoteGroupReceiverServer2ServerGroupSharingDisabled(): void {
		$config = Server::get(IConfig::class);
		$config->setAppValue('files_sharing', 'outgoing_server2server_group_share_enabled', 'no');

		$event = new RestrictInteractionEvent($this->user->getUID(), $this->user, [], new ShareAction(), [new RemoteGroupReceiver('')]);
		$this->assertEquals('Sharing to remote groups is not allowed.', $event->isInteractionRestricted());

		$config->deleteAppValue('files_sharing', 'outgoing_server2server_group_share_enabled');
	}
}
