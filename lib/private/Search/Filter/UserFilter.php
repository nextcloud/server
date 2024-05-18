<?php

declare(strict_types=1);

/**
 * @copyright 2023 Benjamin Gaussorgues <benjamin.gaussorgues@nextcloud.com>
 *
 * @author Benjamin Gaussorgues <benjamin.gaussorgues@nextcloud.com>
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
			throw new InvalidArgumentException('User '.$value.' not found');
		}
		$this->user = $user;
	}

	public function get(): IUser {
		return $this->user;
	}
}
