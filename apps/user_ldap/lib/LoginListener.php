<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023, Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
			__CLASS__ . ' – {user} postLogin',
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
					__CLASS__ . ' – group {group} could not be found (user {user})',
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
						__CLASS__ . ' – group {group} membership failed to be added (user {user})',
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
				__CLASS__ . ' – {user} added to {group}',
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
						__CLASS__ . ' – group {group} membership failed to be removed (user {user})',
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
					__CLASS__ . ' – group {group} could not be found (user {user})',
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
				'service "updateGroups" – {user} removed from {group}',
				[
					'user' => $userId,
					'group' => $groupId
				]
			);
		}
	}
}
