<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\TwoFactorAuth;

use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUser;

class MandatoryTwoFactor {
	/** @var IConfig */
	private $config;

	/** @var IGroupManager */
	private $groupManager;

	public function __construct(IConfig $config, IGroupManager $groupManager) {
		$this->config = $config;
		$this->groupManager = $groupManager;
	}

	/**
	 * Get the state of enforced two-factor auth
	 */
	public function getState(): EnforcementState {
		return new EnforcementState(
			$this->config->getSystemValue('twofactor_enforced', 'false') === 'true',
			$this->config->getSystemValue('twofactor_enforced_groups', []),
			$this->config->getSystemValue('twofactor_enforced_excluded_groups', [])
		);
	}

	/**
	 * Set the state of enforced two-factor auth
	 */
	public function setState(EnforcementState $state) {
		$this->config->setSystemValue('twofactor_enforced', $state->isEnforced() ? 'true' : 'false');
		$this->config->setSystemValue('twofactor_enforced_groups', $state->getEnforcedGroups());
		$this->config->setSystemValue('twofactor_enforced_excluded_groups', $state->getExcludedGroups());
	}

	/**
	 * Check if two-factor auth is enforced for a specific user
	 *
	 * The admin(s) can enforce two-factor auth system-wide, for certain groups only
	 * and also have the option to exclude users of certain groups. This method will
	 * check their membership of those groups.
	 *
	 * @param IUser $user
	 *
	 * @return bool
	 */
	public function isEnforcedFor(IUser $user): bool {
		$state = $this->getState();
		if (!$state->isEnforced()) {
			return false;
		}
		$uid = $user->getUID();

		/*
		 * If there is a list of enforced groups, we only enforce 2FA for members of those groups.
		 * For all the other users it is not enforced (overruling the excluded groups list).
		 */
		if (!empty($state->getEnforcedGroups())) {
			foreach ($state->getEnforcedGroups() as $group) {
				if ($this->groupManager->isInGroup($uid, $group)) {
					return true;
				}
			}
			// Not a member of any of these groups -> no 2FA enforced
			return false;
		}

		/**
		 * If the user is member of an excluded group, 2FA won't be enforced.
		 */
		foreach ($state->getExcludedGroups() as $group) {
			if ($this->groupManager->isInGroup($uid, $group)) {
				return false;
			}
		}

		/**
		 * No enforced groups configured and user not member of an excluded groups,
		 * so 2FA is enforced.
		 */
		return true;
	}
}
