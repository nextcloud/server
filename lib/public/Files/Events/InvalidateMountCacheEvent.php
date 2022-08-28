<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Robin Appelman <robin@icewind.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCP\Files\Events;

use OCP\EventDispatcher\Event;
use OCP\IUser;

/**
 * Used to notify the filesystem setup manager that the available mounts for a user have changed
 *
 * @since 24.0.0
 */
class InvalidateMountCacheEvent extends Event {
	private ?IUser $user;

	/**
	 * @param IUser|null $user user
	 *
	 * @since 24.0.0
	 */
	public function __construct(?IUser $user) {
		parent::__construct();
		$this->user = $user;
	}

	/**
	 * @return IUser|null user
	 *
	 * @since 24.0.0
	 */
	public function getUser(): ?IUser {
		return $this->user;
	}
}
