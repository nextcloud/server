<?php

declare(strict_types=1);

/*
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 */

namespace OCP\User;

/**
 * Bare interface of any Nextcloud user back-end implementation
 *
 * This is the minimum API a user back-end for Nextcloud has to provide. Any
 * other actions are specified via additionally implmented interfaces from this
 * namespace.
 *
 * @since 21.0.0
 */
interface IUserBackEnd extends \OCP\IUserBackend {

	/**
	 * Backend name to be shown in user management
	 *
	 * @return string the name of the backend to be shown
	 * @psalm-return non-empty-string
	 *
	 * @since 21.0.0
	 */
	public function getBackendName(): string;

	/**
	 * Does a user with this UID exist?
	 *
	 * @param string $uid the UID
	 * @psalm-param non-empty-string
	 *
	 * @return bool
	 *
	 * @since 21.0.0
	 */
	public function has($uid): bool;
}
