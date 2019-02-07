<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2018 Joas Schilling <coding@schilljs.com>
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

namespace OCP\Collaboration\Resources;

use OCP\IUser;

/**
 * @since 16.0.0
 */
interface IProvider {

	/**
	 * Get the type of a resource
	 *
	 * @param IResource $resource
	 * @return string
	 * @since 16.0.0
	 */
	public function getType(): string;

	/**
	 * Get the display name of a resource
	 *
	 * @param IResource $resource
	 * @return string
	 * @since 16.0.0
	 */
	public function getName(IResource $resource): string;

	/**
	 * Get the icon class of a resource
	 *
	 * @param IResource $resource
	 * @return string
	 * @since 16.0.0
	 */
	public function getIconClass(IResource $resource): string;

	/**
	 * Get the link to a resource
	 *
	 * @param IResource $resource
	 * @return string
	 * @since 16.0.0
	 */
	public function getLink(IResource $resource): string;

	/**
	 * Can a user/guest access the collection
	 *
	 * @param IResource $resource
	 * @param IUser $user
	 * @return bool
	 * @since 16.0.0
	 */
	public function canAccess(IResource $resource, IUser $user = null): bool;

}
