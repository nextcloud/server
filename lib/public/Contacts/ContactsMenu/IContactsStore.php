<?php
/**
 * @copyright Copyright (c) 2016 Tobia De Koninck <tobia@ledfan.be>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Tobia De Koninck <tobia@ledfan.be>
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
namespace OCP\Contacts\ContactsMenu;

use OCP\IUser;

/**
 * @since 13.0.0
 */
interface IContactsStore {
	/**
	 * @param IUser $user
	 * @param string|null $filter
	 * @param int|null $limit added 19.0.2
	 * @param int|null $offset added 19.0.2
	 * @return IEntry[]
	 * @since 13.0.0
	 */
	public function getContacts(IUser $user, ?string $filter, ?int $limit = null, ?int $offset = null): array;

	/**
	 * @brief finds a contact by specifying the property to search on ($shareType) and the value ($shareWith)
	 * @since 13.0.0
	 */
	public function findOne(IUser $user, int $shareType, string $shareWith): ?IEntry;
}
