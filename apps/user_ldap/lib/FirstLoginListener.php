<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022, Côme Chilliet <come.chilliet@nextcloud.com>
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

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\UserAddedEvent;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\User\Events\PostLoginEvent;
use Psr\Log\LoggerInterface;

class FirstLoginListener implements IEventListener {
	/** @var array<string,array<string, int>> */
	private $eventHappened = [];

	/** @var Group_Proxy */
	private $groupBackend;
	/** @var IEventDispatcher */
	private $dispatcher;
	/** @var IGroupManager */
	private $groupManager;
	/** @var IUserManager */
	private $userManager;
	/** @var LoggerInterface */
	private $logger;
	/** @var IDBConnection */
	private $dbc;

	public function __construct(
		Group_Proxy $groupBackend,
		IEventDispatcher $dispatcher,
		IGroupManager $groupManager,
		IUserManager $userManager,
		LoggerInterface $logger,
		IDBConnection $dbc
	) {
		$this->groupBackend = $groupBackend;
		$this->dispatcher = $dispatcher;
		$this->groupManager = $groupManager;
		$this->userManager = $userManager;
		$this->logger = $logger;
		$this->dbc = $dbc;
	}

	public function handle(Event $event): void {
		if ($event instanceof PostLoginEvent) {
			$this->onPostLogin($event->getUser()->getUID());
		}
	}

	public function onAssignedId(string $username): void {
		$this->logger->info(
			__CLASS__ . ' – {user} assignedId',
			[
				'app' => 'user_ldap',
				'user' => $username,
			]
		);
		$this->eventHappened[$username]['id'] = 1;
		$this->triggerUpdateGroups($username);
	}

	public function onPostLogin(string $username): void {
		$this->logger->info(
			__CLASS__ . ' – {user} postLogin',
			[
				'app' => 'user_ldap',
				'user' => $username,
			]
		);
		$this->eventHappened[$username]['login'] = 1;
		$this->triggerUpdateGroups($username);
	}

	private function triggerUpdateGroups(string $username): void {
		if (array_sum($this->eventHappened[$username] ?? []) >= 2) {
			$this->updateGroups($username);
		}
	}

	private function updateGroups(string $username): void {
		$this->logger->info(
			__CLASS__ . ' – {user} updateGroups',
			[
				'app' => 'user_ldap',
				'user' => $username,
			]
		);
		$groups = $this->groupBackend->getUserGroups($username);

		$qb = $this->dbc->getQueryBuilder();
		$qb->select(['owncloudusers'])
			->from('ldap_group_members')
			->where($qb->expr()->eq('owncloudname', $qb->createParameter('groupId')));

		$qbUpdate = $this->dbc->getQueryBuilder();
		$qbUpdate->update('ldap_group_members')
			->set('owncloudusers', $qb->createParameter('members'))
			->where($qb->expr()->eq('owncloudname', $qb->createParameter('groupId')));

		foreach ($groups as $group) {
			$qb->setParameters([
				'groupId' => $group
			]);

			$qResult = $qb->executeQuery();
			$data = $qResult->fetchOne();
			$qResult->closeCursor();

			$knownUsers = unserialize($data['owncloudusers']);
			$hasChanged = false;

			$groupObject = $this->groupManager->get($group);
			if ($groupObject === null) {
				$this->logger->error(
					__CLASS__ . ' – group {group} could not be found (user {user})',
					[
						'app' => 'user_ldap',
						'user' => $username,
						'group' => $group
					]
				);
				continue;
			}
			if (!in_array($username, $knownUsers)) {
				$userObject = $this->userManager->get($username);
				if ($userObject instanceof IUser) {
					$this->dispatcher->dispatchTyped(new UserAddedEvent($groupObject, $userObject));
					$this->logger->info(
						__CLASS__ . ' – {user} added to {group}',
						[
							'app' => 'user_ldap',
							'user' => $username,
							'group' => $group
						]
					);
					$qbUpdate->setParameters([
						'members' => serialize(array_merge($knownUsers, [$username])),
						'groupId' => $group
					]);
					$qbUpdate->executeStatement();
				}
			}
		}
	}
}
