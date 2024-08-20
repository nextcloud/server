<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\User_LDAP\Service;

use OCA\User_LDAP\Db\GroupMembership;
use OCA\User_LDAP\Db\GroupMembershipMapper;
use OCA\User_LDAP\Group_Proxy;
use OCP\DB\Exception;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class UpdateGroupsService {
	public function __construct(
		private Group_Proxy $groupBackend,
		private IEventDispatcher $dispatcher,
		private IGroupManager $groupManager,
		private IUserManager $userManager,
		private LoggerInterface $logger,
		private GroupMembershipMapper $groupMembershipMapper,
	) {
	}

	/**
	 * @throws Exception
	 */
	public function updateGroups(): void {
		$knownGroups = $this->groupMembershipMapper->getKnownGroups();
		$actualGroups = $this->groupBackend->getGroups();

		if (empty($actualGroups) && empty($knownGroups)) {
			$this->logger->info(
				'service "updateGroups" – groups do not seem to be configured properly, aborting.',
			);
			return;
		}

		$this->handleKnownGroups(array_intersect($actualGroups, $knownGroups));
		$this->handleCreatedGroups(array_diff($actualGroups, $knownGroups));
		$this->handleRemovedGroups(array_diff($knownGroups, $actualGroups));

		$this->logger->debug('service "updateGroups" – Finished.');
	}

	/**
	 * @param string[] $groups
	 * @throws Exception
	 */
	public function handleKnownGroups(array $groups): void {
		$this->logger->debug('service "updateGroups" – Dealing with known Groups.');

		foreach ($groups as $group) {
			$this->logger->debug('service "updateGroups" – Dealing with {group}.', ['group' => $group]);
			$groupMemberships = $this->groupMembershipMapper->findGroupMemberships($group);
			$knownUsers = array_map(
				static fn (GroupMembership $groupMembership): string => $groupMembership->getUserid(),
				$groupMemberships
			);
			$groupMemberships = array_combine($knownUsers, $groupMemberships);
			$actualUsers = $this->groupBackend->usersInGroup($group);

			$groupObject = $this->groupManager->get($group);
			if ($groupObject === null) {
				/* We are not expecting the group to not be found since it was returned by $this->groupBackend->getGroups() */
				$this->logger->error(
					'service "updateGroups" – Failed to get group {group} for update',
					[
						'group' => $group
					]
				);
				continue;
			}
			foreach (array_diff($knownUsers, $actualUsers) as $removedUser) {
				try {
					$this->groupMembershipMapper->delete($groupMemberships[$removedUser]);
				} catch (Exception $e) {
					if ($e->getReason() !== Exception::REASON_DATABASE_OBJECT_NOT_FOUND) {
						/* If reason is not found something else removed the membership, that’s fine */
						$this->logger->error(
							__CLASS__ . ' – group {group} membership failed to be removed (user {user})',
							[
								'app' => 'user_ldap',
								'user' => $removedUser,
								'group' => $group,
								'exception' => $e,
							]
						);
					}
					/* We failed to delete the groupmembership so we do not want to advertise it */
					continue;
				}
				$userObject = $this->userManager->get($removedUser);
				if ($userObject instanceof IUser) {
					$this->dispatcher->dispatchTyped(new UserRemovedEvent($groupObject, $userObject));
				}
				$this->logger->info(
					'service "updateGroups" – {user} removed from {group}',
					[
						'user' => $removedUser,
						'group' => $group
					]
				);
			}
			foreach (array_diff($actualUsers, $knownUsers) as $addedUser) {
				try {
					$this->groupMembershipMapper->insert(GroupMembership::fromParams(['groupid' => $group,'userid' => $addedUser]));
				} catch (Exception $e) {
					if ($e->getReason() !== Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
						/* If reason is unique constraint something else added the membership, that’s fine */
						$this->logger->error(
							__CLASS__ . ' – group {group} membership failed to be added (user {user})',
							[
								'app' => 'user_ldap',
								'user' => $addedUser,
								'group' => $group,
								'exception' => $e,
							]
						);
					}
					/* We failed to insert the groupmembership so we do not want to advertise it */
					continue;
				}
				$userObject = $this->userManager->get($addedUser);
				if ($userObject instanceof IUser) {
					$this->dispatcher->dispatchTyped(new UserAddedEvent($groupObject, $userObject));
				}
				$this->logger->info(
					'service "updateGroups" – {user} added to {group}',
					[
						'user' => $addedUser,
						'group' => $group
					]
				);
			}
		}
		$this->logger->debug('service "updateGroups" – FINISHED dealing with known Groups.');
	}

	/**
	 * @param string[] $createdGroups
	 * @throws Exception
	 */
	public function handleCreatedGroups(array $createdGroups): void {
		$this->logger->debug('service "updateGroups" – dealing with created Groups.');

		foreach ($createdGroups as $createdGroup) {
			$this->logger->info('service "updateGroups" – new group "' . $createdGroup . '" found.');

			$users = $this->groupBackend->usersInGroup($createdGroup);
			$groupObject = $this->groupManager->get($createdGroup);
			foreach ($users as $user) {
				try {
					$this->groupMembershipMapper->insert(GroupMembership::fromParams(['groupid' => $createdGroup,'userid' => $user]));
				} catch (Exception $e) {
					if ($e->getReason() !== Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
						$this->logger->error(
							__CLASS__ . ' – group {group} membership failed to be added (user {user})',
							[
								'app' => 'user_ldap',
								'user' => $user,
								'group' => $createdGroup,
								'exception' => $e,
							]
						);
					}
					/* We failed to insert the groupmembership so we do not want to advertise it */
					continue;
				}
				if ($groupObject instanceof IGroup) {
					$userObject = $this->userManager->get($user);
					if ($userObject instanceof IUser) {
						$this->dispatcher->dispatchTyped(new UserAddedEvent($groupObject, $userObject));
					}
				}
			}
		}
		$this->logger->debug('service "updateGroups" – FINISHED dealing with created Groups.');
	}

	/**
	 * @param string[] $removedGroups
	 * @throws Exception
	 */
	public function handleRemovedGroups(array $removedGroups): void {
		$this->logger->debug('service "updateGroups" – dealing with removed groups.');

		$this->groupMembershipMapper->deleteGroups($removedGroups);
		foreach ($removedGroups as $group) {
			$groupObject = $this->groupManager->get($group);
			if ($groupObject instanceof IGroup) {
				$groupMemberships = $this->groupMembershipMapper->findGroupMemberships($group);
				foreach ($groupMemberships as $groupMembership) {
					$userObject = $this->userManager->get($groupMembership->getUserid());
					if ($userObject instanceof IUser) {
						$this->dispatcher->dispatchTyped(new UserRemovedEvent($groupObject, $userObject));
					}
				}
			}
		}

		$this->logger->info(
			'service "updateGroups" – groups {removedGroups} were removed.',
			[
				'removedGroups' => $removedGroups
			]
		);
	}
}
