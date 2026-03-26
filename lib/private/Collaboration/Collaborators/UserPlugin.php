<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Collaboration\Collaborators;

use OCP\Collaboration\Collaborators\ISearchPlugin;
use OCP\Collaboration\Collaborators\ISearchResult;
use OCP\Collaboration\Collaborators\SearchResultType;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Share\IManager;
use OCP\Share\IShare;
use OCP\Teams\ITeamManager;
use OCP\UserStatus\IManager as IUserStatusManager;
use OCP\UserStatus\IUserStatus;

readonly class UserPlugin implements ISearchPlugin {
	public function __construct(
		private IManager $shareManager,
		private IUserManager $userManager,
		private IGroupManager $groupManager,
		private ITeamManager $teamManager,
		private IUserSession $userSession,
		private IUserStatusManager $userStatusManager,
		private IDBConnection $connection,
	) {
	}

	public function search($search, $limit, $offset, ISearchResult $searchResult): bool {
		/** @var IUser $currentUser */
		$currentUser = $this->userSession->getUser();

		$shareWithGroupOnlyExcludeGroupsList = $this->shareManager->shareWithGroupMembersOnlyExcludeGroupsList();
		$allowedGroups = array_diff($this->groupManager->getUserGroupIds($currentUser), $shareWithGroupOnlyExcludeGroupsList);

		/** @var array<string, array{0: 'wide'|'exact', 1: IUser}> $users */
		$users = [];
		$lowerSearch = mb_strtolower($search);

		if ($this->shareManager->allowEnumeration()) {
			$shareeEnumerationRestrictToGroup = $this->shareManager->limitEnumerationToGroups();
			$shareeEnumerationRestrictToPhone = $this->shareManager->limitEnumerationToPhone();

			if (!$shareeEnumerationRestrictToGroup && !$shareeEnumerationRestrictToPhone) {
				// No restrictions, search everything.
				$usersByDisplayName = $this->userManager->searchDisplayName($search, $limit, $offset);
				foreach ($usersByDisplayName as $user) {
					if ($user->isEnabled()) {
						$users[$user->getUID()] = ['wide', $user];
					}
				}
			} else {
				if ($shareeEnumerationRestrictToGroup) {
					foreach ($allowedGroups as $groupId) {
						$usersInGroup = $this->groupManager->displayNamesInGroup($groupId, $search, $limit, $offset);
						foreach ($usersInGroup as $userId => $displayName) {
							$userId = (string)$userId;
							$user = $this->userManager->get($userId);
							if ($user !== null && $user->isEnabled()) {
								$users[$userId] = ['wide', $user];
							}
						}
					}

					// If teams are enabled, also search in them
					if ($this->teamManager->hasTeamSupport()) {
						$teams = $this->teamManager->getTeamsForUser($currentUser->getUID());
						foreach ($teams as $team) {
							$usersInTeam = $this->teamManager->getMembersOfTeam($team->getId(), $currentUser->getUID());
							foreach ($usersInTeam as $userId => $displayName) {
								if (!str_contains(mb_strtolower($userId), $lowerSearch) && !str_contains(mb_strtolower($displayName), $lowerSearch)) {
									continue;
								}
								$user = $this->userManager->get($userId);
								if ($user !== null && $user->isEnabled()) {
									$users[$userId] = ['wide', $user];
								}
							}
						}
					}
				}

				if ($shareeEnumerationRestrictToPhone) {
					$usersInPhonebook = $this->userManager->searchKnownUsersByDisplayName($currentUser->getUID(), $search, $limit, $offset);
					foreach ($usersInPhonebook as $user) {
						if ($user->isEnabled()) {
							$users[$user->getUID()] = ['wide', $user];
						}
					}
				}
			}
		}

		// Even if normal sharee enumeration is not allowed, full matches are still allowed.
		if ($search !== '' && $this->shareManager->allowEnumerationFullMatch()) {
			// Re-use the results from earlier if possible
			$usersByDisplayName ??= $this->userManager->searchDisplayName($search, $limit, $offset);
			foreach ($usersByDisplayName as $user) {
				if ($user->isEnabled() && (mb_strtolower($user->getDisplayName()) === $lowerSearch || ($this->shareManager->ignoreSecondDisplayName() && trim(mb_strtolower(preg_replace('/ \(.*\)$/', '', $user->getDisplayName()))) === $lowerSearch))) {
					$users[$user->getUID()] = ['exact', $user];
				}
			}

			if ($this->shareManager->matchUserId()) {
				$user = $this->userManager->get($search);
				if ($user !== null) {
					$users[$user->getUID()] = ['exact', $user];
				}
			}

			if ($this->shareManager->matchEmail()) {
				$qb = $this->connection->getQueryBuilder();
				$qb
					->select('uid', 'value', 'name')
					->from('accounts_data')
					->where($qb->expr()->eq($qb->func()->lower('value'), $qb->createNamedParameter($lowerSearch)))
					->andWhere($qb->expr()->in('name', $qb->createNamedParameter(['email', 'additional_mail'], IQueryBuilder::PARAM_STR_ARRAY)));
				$result = $qb->executeQuery();
				while ($row = $result->fetch()) {
					$uid = $row['uid'];
					$email = $row['value'];
					$isAdditional = $row['name'] === 'additional_mail';
					$users[$uid] = ['exact', $this->userManager->get($uid), $isAdditional ? $email : null];
				}
				$result->closeCursor();
			}
		}

		uasort($users, static fn (array $a, array $b): int => strcasecmp($a[1]->getDisplayName(), $b[1]->getDisplayName()));

		if (isset($users[$currentUser->getUID()])) {
			unset($users[$currentUser->getUID()]);
		}

		if ($this->shareManager->shareWithGroupMembersOnly()) {
			$users = array_filter($users, fn (array $match) => array_intersect($allowedGroups, $this->groupManager->getUserGroupIds($match[1])) !== []);
		}

		$userStatuses = array_map(
			static fn (IUserStatus $userStatus) => [
				'status' => $userStatus->getStatus(),
				'message' => $userStatus->getMessage(),
				'icon' => $userStatus->getIcon(),
				'clearAt' => $userStatus->getClearAt()
					? (int)$userStatus->getClearAt()->format('U')
					: null,
			],
			$this->userStatusManager->getUserStatuses(array_keys($users)),
		);

		$result = ['wide' => [], 'exact' => []];
		foreach ($users as $match) {
			$match[2] ??= null;
			[$type, $user, $uniqueDisplayName] = $match;

			$displayName = $user->getDisplayName();
			if ($uniqueDisplayName !== null) {
				$displayName .= ' (' . $uniqueDisplayName . ')';
			}

			$status = $userStatuses[$user->getUID()] ?? [];

			$result[$type][] = [
				'label' => $displayName,
				'subline' => $status['message'] ?? '',
				'icon' => 'icon-user',
				'value' => [
					'shareType' => IShare::TYPE_USER,
					'shareWith' => $user->getUID(),
				],
				'shareWithDisplayNameUnique' => $uniqueDisplayName ?? $user->getSystemEMailAddress() ?: $user->getUID(),
				'status' => $status,
			];
		}

		$type = new SearchResultType('users');
		$searchResult->addResultSet($type, $result['wide'], $result['exact']);
		if ($result['exact'] !== []) {
			$searchResult->markExactIdMatch($type);
		}

		return count($users) < $limit;
	}
}
