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

namespace OCP\User\Backend\Actions;

use OCP\User\Exceptions\BackEndException;
use OCP\User\IUserBackEnd;

/**
 * A user back-end that can delete a user
 *
 * @since 21.0.0
 */
interface IDeleteUser extends IUserBackEnd {

	/**
	 * Determine whether the delete action can be carried out
	 *
	 * Sometimes a user back-end implements the action but at runtime there are
	 * restrictions (e.g. configuration) that make it impossible to actually
	 * perform it. So in the easy case this method returns a simple `true`.
	 *
	 * @return bool
	 */
	public function canDeleteUser(): bool;

	/**
	 * Delete a user
	 *
	 * @param string $uid UID of the user to delete
	 *
	 * @throws BackEndException for any error that can't be recovered from
	 *
	 * @since 21.0.0
	 */
	public function deleteUser($uid): void;

}
