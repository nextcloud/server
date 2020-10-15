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

namespace OCP\User\Backend;

use OCP\User\Exceptions\BackEndException;
use OCP\User\IUserBackEnd;

/**
 * A user back-end that can search for users
 *
 * @since 21.0.0
 */
interface ISearchUsers extends IUserBackEnd {

	/**
	 * Determine whether the search action can be carried out
	 *
	 * Sometimes a user back-end implements the action but at runtime there are
	 * restrictions (e.g. configuration) that make it impossible to actually
	 * perform it. So in the easy case this method returns a simple `true`.
	 *
	 * @return bool
	 *
	 * @since 21.0.0
	 */
	public function canSearchUids(): bool;

	/**
	 * Get a list of matching users
	 *
	 * @param string $pattern if empty return all users
	 * @param int|null $limit if specified only return $limit users
	 * @param int|null $offset if specified offset the results with $offset
	 *
	 * @return string[]
	 * @psalm-return non-empty-string[]
	 *
	 * @throws BackEndException for any error that can't be recovered from
	 *
	 * @since 21.0.0
	 */
	public function searchUids(string $pattern = '',
							   ?int $limit = null,
							   ?int $offset = null): array;

}
