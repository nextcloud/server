<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP;

use OCA\User_LDAP\Db\GroupMembership;
use OCA\User_LDAP\Db\GroupMembershipMapper;
use OCP\DB\Exception;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\User\Events\PostLoginEvent;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<PostLoginEvent>
 */
class LoginListener implements IEventListener {
	public function __construct(
		private IEventDispatcher $dispatcher,
		private Group_Proxy $groupBackend,
		private IGroupManager $groupManager,
		private LoggerInterface $logger,
		private GroupMembershipMapper $groupMembershipMapper,
	) {
	}

	public function handle(Event $event): void {
		if ($event instanceof PostLoginEvent) {
			$this->onPostLogin($event->getUser());
		}
	}

	public function onPostLogin(IUser $user): void {
		$this->logger->info(
			self::class . ' - {user} postLogin',
			[
				'app' => 'user_ldap',
				'user' => $user->getUID(),
			]
		);
		$this->updateGroups($user);
	}

	private function updateGroups(IUser $userObject): void {
		$userId = $userObject->getUID();
		$groupMemberships = $this->groupMembershipMapper->findGroupMembershipsForUser($userId);
		$knownGroups = array_map(
			static fn (GroupMembership $groupMembership): string => $groupMembership->getGroupid(),
			$groupMemberships
		);
		$groupMemberships = array_combine($knownGroups, $groupMemberships);
		$actualGroups = $this->groupBackend->getUserGroups($userId);

		$newGroups = array_diff($actualGroups, $knownGroups);
		$oldGroups = array_diff($knownGroups, $actualGroups);
		foreach ($newGroups as $groupId) {
			$groupObject = $this->groupManager->get($groupId);
			if ($groupObject === null) {
				$this->logger->error(
					self::class . ' - group {group} could not be found (user {user})',
					[
						'app' => 'user_ldap',
						'user' => $userId,
						'group' => $groupId
					]
				);
				continue;
			}
			try {
				$this->groupMembershipMapper->insert(GroupMembership::fromParams(['groupid' => $groupId,'userid' => $userId]));
			} catch (Exception $e) {
				if ($e->getReason() !== Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
					$this->logger->error(
						self::class . ' - group {group} membership failed to be added (user {user})',
						[
							'app' => 'user_ldap',
							'user' => $userId,
							'group' => $groupId,
							'exception' => $e,
						]
					);
				}
				/* We failed to insert the groupmembership so we do not want to advertise it */
				continue;
			}
			$this->groupBackend->addRelationshipToCaches($userId, null, $groupId);
			$this->dispatcher->dispatchTyped(new UserAddedEvent($groupObject, $userObject));
			$this->logger->info(
				self::class . ' - {user} added to {group}',
				[
					'app' => 'user_ldap',
					'user' => $userId,
					'group' => $groupId
				]
			);
		}
		foreach ($oldGroups as $groupId) {
			try {
				$this->groupMembershipMapper->delete($groupMemberships[$groupId]);
			} catch (Exception $e) {
				if ($e->getReason() !== Exception::REASON_DATABASE_OBJECT_NOT_FOUND) {
					$this->logger->error(
						self::class . ' - group {group} membership failed to be removed (user {user})',
						[
							'app' => 'user_ldap',
							'user' => $userId,
							'group' => $groupId,
							'exception' => $e,
						]
					);
				}
				/* We failed to delete the groupmembership so we do not want to advertise it */
				continue;
			}
			$groupObject = $this->groupManager->get($groupId);
			if ($groupObject === null) {
				$this->logger->error(
					self::class . ' - group {group} could not be found (user {user})',
					[
						'app' => 'user_ldap',
						'user' => $userId,
						'group' => $groupId
					]
				);
				continue;
			}
			$this->dispatcher->dispatchTyped(new UserRemovedEvent($groupObject, $userObject));
			$this->logger->info(
				'service "updateGroups" - {user} removed from {group}',
				[
					'user' => $userId,
					'group' => $groupId
				]
			);
		}
	}
}
