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
use OCA\User_LDAP\Group_Proxy;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
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
	private Group_Proxy $groupBackend;
	private IEventDispatcher $dispatcher;
	private IGroupManager $groupManager;
	private IUserManager $userManager;
	private LoggerInterface $logger;
	private IDBConnection $dbc;

	public function __construct(
		Group_Proxy $groupBackend,
		IEventDispatcher $dispatcher,
		IGroupManager $groupManager,
		IUserManager $userManager,
		LoggerInterface $logger,
		IDBConnection $dbc,
		IConfig $config,
		ITimeFactory $timeFactory
	) {
		parent::__construct($timeFactory);
		$this->interval = (int)$config->getAppValue('user_ldap', 'bgjRefreshInterval', '3600');
		$this->groupBackend = $groupBackend;
		$this->dispatcher = $dispatcher;
		$this->groupManager = $groupManager;
		$this->userManager = $userManager;
		$this->logger = $logger;
		$this->dbc = $dbc;
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

		/** @var string[] $knownGroups */
		$knownGroups = array_keys($this->getKnownGroups());
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
	 * @return array<string, array{owncloudusers: string, owncloudname: string}>
	 * @throws Exception
	 */
	private function getKnownGroups(): array {
		if (is_array($this->groupsFromDB)) {
			return $this->groupsFromDB;
		}
		$qb = $this->dbc->getQueryBuilder();
		$qb->select(['owncloudname', 'owncloudusers'])
			->from('ldap_group_members');

		$qResult = $qb->executeQuery();
		$result = $qResult->fetchAll();
		$qResult->closeCursor();

		$this->groupsFromDB = [];
		foreach ($result as $dataset) {
			$this->groupsFromDB[$dataset['owncloudname']] = $dataset;
		}

		return $this->groupsFromDB;
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
		$qb = $this->dbc->getQueryBuilder();
		$qb->update('ldap_group_members')
			->set('owncloudusers', $qb->createParameter('members'))
			->where($qb->expr()->eq('owncloudname', $qb->createParameter('groupId')));

		$groupsFromDB = $this->getKnownGroups();
		foreach ($groups as $group) {
			$knownUsers = unserialize($groupsFromDB[$group]['owncloudusers']);
			$actualUsers = $this->groupBackend->usersInGroup($group);
			$hasChanged = false;

			$groupObject = $this->groupManager->get($group);
			foreach (array_diff($knownUsers, $actualUsers) as $removedUser) {
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
				$hasChanged = true;
			}
			foreach (array_diff($actualUsers, $knownUsers) as $addedUser) {
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
				$hasChanged = true;
			}
			if ($hasChanged) {
				$qb->setParameters([
					'members' => serialize($actualUsers),
					'groupId' => $group
				]);
				$qb->executeStatement();
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

		$query = $this->dbc->getQueryBuilder();
		$query->insert('ldap_group_members')
			->setValue('owncloudname', $query->createParameter('owncloudname'))
			->setValue('owncloudusers', $query->createParameter('owncloudusers'));

		foreach ($createdGroups as $createdGroup) {
			$this->logger->info(
				'bgJ "updateGroups" – new group "' . $createdGroup . '" found.',
				['app' => 'user_ldap']
			);
			$users = serialize($this->groupBackend->usersInGroup($createdGroup));

			$query->setParameter('owncloudname', $createdGroup)
				->setParameter('owncloudusers', $users);
			$query->executeStatement();
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

		$query = $this->dbc->getQueryBuilder();
		$query->delete('ldap_group_members')
			->where($query->expr()->in('owncloudname', $query->createParameter('owncloudnames')));

		foreach (array_chunk($removedGroups, 1000) as $removedGroupsChunk) {
			$this->logger->info(
				'bgJ "updateGroups" – groups {removedGroups} were removed.',
				[
					'app' => 'user_ldap',
					'removedGroups' => $removedGroupsChunk
				]
			);
			$query->setParameter('owncloudnames', $removedGroupsChunk, IQueryBuilder::PARAM_STR_ARRAY);
			$query->executeStatement();
		}

		$this->logger->debug(
			'bgJ "updateGroups" – FINISHED dealing with removed groups.',
			['app' => 'user_ldap']
		);
	}
}
