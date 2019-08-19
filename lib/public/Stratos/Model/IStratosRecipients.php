<?php
declare(strict_types=1);


/**
 * Stratos - above your cloud
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2018, Maxence Lange <maxence@artificial-owl.com>
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


namespace OCP\Stratos\Model;


/**
 * Interface IStratosRecipients
 *
 * @since 18.0.0
 *
 * @package OCP\Stratos
 */
interface IStratosRecipients {

	/**
	 * @param string $user
	 *
	 * @return IStratosRecipients
	 */
	public function addUser(string $user): self;

	/**
	 * @param array $users
	 *
	 * @return IStratosRecipients
	 */
	public function addUsers(array $users): self;

	/**
	 * @param string $user
	 *
	 * @return IStratosRecipients
	 */
	public function removeUser(string $user): self;

	/**
	 * @param array $users
	 *
	 * @return IStratosRecipients
	 */

	public function removeUsers(array $users): self;


	/**
	 * @param string $group
	 *
	 * @return IStratosRecipients
	 */
	public function addGroup(string $group): self;

	/**
	 * @param array $groups
	 *
	 * @return IStratosRecipients
	 */
	public function addGroups(array $groups): self;

	/**
	 * @param string $group
	 *
	 * @return IStratosRecipients
	 */
	public function removeGroup(string $group): self;

	/**
	 * @param array $groups
	 *
	 * @return IStratosRecipients
	 */

	public function removeGroups(array $groups): self;


	/**
	 * @return string[]
	 */
	public function getUsers(): array;

	/**
	 * @return string[]
	 */
	public function getGroups(): array;

	/**
	 * @return string[]
	 */
	public function getRemovedUsers(): array;

	/**
	 * @return string[]
	 */
	public function getRemovedGroups(): array;


	/**
	 * @param string $app
	 *
	 * @return IStratosRecipients
	 */
	public function filterApp(string $app): self;

	/**
	 * @param string[] $apps
	 *
	 * @return IStratosRecipients
	 */
	public function filterApps(array $apps): self;

	/**
	 * @return string[]
	 */
	public function getFilteredApps(): array;


	/**
	 * @param string $app
	 *
	 * @return IStratosRecipients
	 */
	public function limitToApp(string $app): self;

	/**
	 * @param string[] $apps
	 *
	 * @return IStratosRecipients
	 */
	public function limitToApps(array $apps): self;

	/**
	 * @return string[]
	 */
	public function getLimitedToApps(): array;

}

