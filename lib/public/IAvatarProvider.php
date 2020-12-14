<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Daniel Calviño Sánchez (danxuliu@gmail.com)
 *
 * @author Daniel Calviño Sánchez <danxuliu@gmail.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCP;

/**
 * This class acts as a factory for avatar instances
 *
 * @since 21.0.0
 */
interface IAvatarProvider {

	/**
	 * Returns an IAvatar instance for the given id
	 *
	 * @param string $id the identifier of the avatar
	 * @return IAvatar the avatar instance
	 * @throws \Exception if an error occurred while getting the avatar
	 * @since 21.0.0
	 */
	public function getAvatar(string $id): IAvatar;
}
