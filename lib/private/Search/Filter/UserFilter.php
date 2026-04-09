<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Search\Filter;

use InvalidArgumentException;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Search\IFilter;

class UserFilter implements IFilter {
	private IUser $user;

	public function __construct(
		string $value,
		IUserManager $userManager,
	) {
		$user = $userManager->get($value);
		if ($user === null) {
			throw new InvalidArgumentException('User ' . $value . ' not found');
		}
		$this->user = $user;
	}

	public function get(): IUser {
		return $this->user;
	}
}
