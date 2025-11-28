<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Listener;

use OC\User\LazyUser;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCP\IUser;
use OCP\IUserManager;

class CircleListenerBase {
	public function __construct(
		private readonly IUserManager $userManager,
	) {
	}


	/**
	 * @return \Iterator<string, IUser>
	 */
	protected function usersFromMember(Member $member): \Iterator {
		if ($member->getUserType() === Member::TYPE_CIRCLE) {
			$members = $member->getBasedOn()->getInheritedMembers();
		} else {
			$members = [$member];
		}

		foreach ($members as $member) {
			if ($member->getUserType() === Member::TYPE_USER) {
				yield $member->getUserId() => new LazyUser($member->getUserId(), $this->userManager);
			}
		}
	}

	/**
	 * @return \Iterator<string, IUser>
	 */
	protected function usersFromCircle(Circle $circle): \Iterator {
		foreach ($circle->getInheritedMembers() as $member) {
			if ($member->getUserType() === Member::TYPE_USER) {
				yield $member->getUserId() => new LazyUser($member->getUserId(), $this->userManager);
			} else if ($member->getUserType() === Member::TYPE_GROUP) {
				// todo
			}
		}
	}
}
