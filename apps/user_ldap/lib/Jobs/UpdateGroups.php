<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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
namespace OCA\User_LDAP\Jobs;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\User_LDAP\Db\GroupMembership;
use OCP\User_LDAP\Db\GroupMembershipMapper;
use OCA\User_LDAP\Group_Proxy;
use OCP\DB\Exception;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class UpdateGroups extends TimedJob {
	/** @var ?array<string, array{owncloudusers: string, owncloudname: string}>  */
	private ?array $groupsFromDB = null;

	public function __construct(
		private Group_Proxy $groupBackend,
		private IEventDispatcher $dispatcher,
		private IGroupManager $groupManager,
		private IUserManager $userManager,
		private LoggerInterface $logger,
		private IDBConnection $dbc,
		private GroupMembershipMapper $groupMembershipMapper,
		IConfig $config,
		ITimeFactory $timeFactory,
	) {
		parent::__construct($timeFactory);
		$this->interval = (int)$config->getAppValue('user_ldap', 'bgjRefreshInterval', '3600');
	}

	/**
	 * @param mixed $argument
	 * @throws Exception
	 */
	public function run($argument): void {
		$this->updateGroups();
	}

	/**
	 * @throws Exception
	 */
	public function updateGroups(): void {
		$this->logger->debug(
			'Run background job "updateGroups"',
			['app' => 'user_ldap']
		);

		$knownGroups = $this->groupMembershipMapper->getKnownGroups();
		$actualGroups = $this->groupBackend->getGroups();

		if (empty($actualGroups) && empty($knownGroups)) {
			$this->logger->info(
				'bgJ "updateGroups" – groups do not seem to be configured properly, aborting.',
				['app' => 'user_ldap']
			);
			return;
		}

		$this->handleKnownGroups(array_intersect($actualGroups, $knownGroups));
		$this->handleCreatedGroups(array_diff($actualGroups, $knownGroups));
		$this->handleRemovedGroups(array_diff($knownGroups, $actualGroups));

		$this->logger->debug(
			'bgJ "updateGroups" – Finished.',
			['app' => 'user_ldap']
		);
	}

	/**
	 * @param string[] $groups
	 * @throws Exception
	 */
	private function handleKnownGroups(array $groups): void {
		$this->logger->debug(
			'bgJ "updateGroups" – Dealing with known Groups.',
			['app' => 'user_ldap']
		);

		foreach ($groups as $group) {
			$groupMemberships = $this->groupMembershipMapper->findGroupMemberships($group);
			$knownUsers = array_map(
				fn (GroupMembership $groupMembership): string => $groupMembership->getUserid(),
				$groupMemberships
			);
			$groupMemberships = array_combine($knownUsers, $groupMemberships);
			$actualUsers = $this->groupBackend->usersInGroup($group);

			$groupObject = $this->groupManager->get($group);
			if ($groupObject === null) {
				/* We are not expecting the group to not be found since it was returned by $this->groupBackend->getGroups() */
				$this->logger->error(
					'bgJ "updateGroups" – Failed to get group {group} for update',
					[
						'app' => 'user_ldap',
						'group' => $group
					]
				);
				continue;
			}
			foreach (array_diff($knownUsers, $actualUsers) as $removedUser) {
				$this->groupMembershipMapper->delete($groupMemberships[$removedUser]);
				$userObject = $this->userManager->get($removedUser);
				if ($userObject instanceof IUser) {
					$this->dispatcher->dispatchTyped(new UserRemovedEvent($groupObject, $userObject));
				}
				$this->logger->info(
					'bgJ "updateGroups" – {user} removed from {group}',
					[
						'app' => 'user_ldap',
						'user' => $removedUser,
						'group' => $group
					]
				);
			}
			foreach (array_diff($actualUsers, $knownUsers) as $addedUser) {
				$this->groupMembershipMapper->insert(GroupMembership::fromParams(['groupid' => $group,'userid' => $addedUser]));
				$userObject = $this->userManager->get($addedUser);
				if ($userObject instanceof IUser) {
					$this->dispatcher->dispatchTyped(new UserAddedEvent($groupObject, $userObject));
				}
				$this->logger->info(
					'bgJ "updateGroups" – {user} added to {group}',
					[
						'app' => 'user_ldap',
						'user' => $addedUser,
						'group' => $group
					]
				);
			}
		}
		$this->logger->debug(
			'bgJ "updateGroups" – FINISHED dealing with known Groups.',
			['app' => 'user_ldap']
		);
	}

	/**
	 * @param string[] $createdGroups
	 * @throws Exception
	 */
	private function handleCreatedGroups(array $createdGroups): void {
		$this->logger->debug(
			'bgJ "updateGroups" – dealing with created Groups.',
			['app' => 'user_ldap']
		);

		foreach ($createdGroups as $createdGroup) {
			$this->logger->info(
				'bgJ "updateGroups" – new group "' . $createdGroup . '" found.',
				['app' => 'user_ldap']
			);

			$users = $this->groupBackend->usersInGroup($createdGroup);
			foreach ($users as $user) {
				$this->groupMembershipMapper->insert(GroupMembership::fromParams(['groupid' => $createdGroup,'userid' => $user]));
			}
			$groupObject = $this->groupManager->get($group);
			if ($groupObject instanceof IGroup) {
				$this->dispatcher->dispatchTyped(new GroupCreatedEvent($groupObject));
			}
		}
		$this->logger->debug(
			'bgJ "updateGroups" – FINISHED dealing with created Groups.',
			['app' => 'user_ldap']
		);
	}

	/**
	 * @param string[] $removedGroups
	 * @throws Exception
	 */
	private function handleRemovedGroups(array $removedGroups): void {
		$this->logger->debug(
			'bgJ "updateGroups" – dealing with removed groups.',
			['app' => 'user_ldap']
		);

		$this->groupMembershipMapper->deleteGroups($removedGroups);

		//TODO find a way to dispatch GroupDeletedEvent

		$this->logger->info(
			'bgJ "updateGroups" – groups {removedGroups} were removed.',
			[
				'app' => 'user_ldap',
				'removedGroups' => $removedGroups
			]
		);
	}
}
