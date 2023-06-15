<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Artur Neumann <artur@jankaritech.com>
 *
 * @author Artur Neumann <artur@jankaritech.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCP\Authentication\Token;

/**
 * @since 26.0.3
 */
interface IProvider {
	/**
	 * invalidates all tokens of a specific user
	 * if a client name is given only tokens of that client will be invalidated
	 *
	 * @param string $uid
	 * @param string|null $clientName
	 * @since 26.0.3
	 * @return void
	 */
	public function invalidateTokensOfUser(string $uid, ?string $clientName);
}
