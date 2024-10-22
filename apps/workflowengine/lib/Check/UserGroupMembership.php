<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\WorkflowEngine\Check;

use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserSession;
use OCP\WorkflowEngine\ICheck;
use OCP\WorkflowEngine\IManager;

class UserGroupMembership implements ICheck {

	/** @var string */
	protected $cachedUser;

	/** @var string[] */
	protected $cachedGroupMemberships;

	/**
	 * @param IUserSession $userSession
	 * @param IGroupManager $groupManager
	 * @param IL10N $l
	 */
	public function __construct(
		protected IUserSession $userSession,
		protected IGroupManager $groupManager,
		protected IL10N $l,
	) {
	}

	/**
	 * @param string $operator
	 * @param string $value
	 * @return bool
	 */
	public function executeCheck($operator, $value) {
		$user = $this->userSession->getUser();

		if ($user instanceof IUser) {
			$groupIds = $this->getUserGroups($user);
			return ($operator === 'is') === in_array($value, $groupIds);
		} else {
			return $operator !== 'is';
		}
	}


	/**
	 * @param string $operator
	 * @param string $value
	 * @throws \UnexpectedValueException
	 */
	public function validateCheck($operator, $value) {
		if (!in_array($operator, ['is', '!is'])) {
			throw new \UnexpectedValueException($this->l->t('The given operator is invalid'), 1);
		}

		if (!$this->groupManager->groupExists($value)) {
			throw new \UnexpectedValueException($this->l->t('The given group does not exist'), 2);
		}
	}

	/**
	 * @param IUser $user
	 * @return string[]
	 */
	protected function getUserGroups(IUser $user) {
		$uid = $user->getUID();

		if ($this->cachedUser !== $uid) {
			$this->cachedUser = $uid;
			$this->cachedGroupMemberships = $this->groupManager->getUserGroupIds($user);
		}

		return $this->cachedGroupMemberships;
	}

	public function supportedEntities(): array {
		// universal by default
		return [];
	}

	public function isAvailableForScope(int $scope): bool {
		// admin only by default
		return $scope === IManager::SCOPE_ADMIN;
	}
}
