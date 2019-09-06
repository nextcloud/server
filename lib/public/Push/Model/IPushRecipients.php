<?php
declare(strict_types=1);


/**
 * Push - Nextcloud Push Service
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


namespace OCP\Push\Model;


/**
 * Interface IPushRecipients
 *
 * @since 18.0.0
 *
 * @package OCP\Push
 */
interface IPushRecipients {


	/**
	 * @return string
	 */
	public function getApp(): string;

	/**
	 * @param string $app
	 *
	 * @return IPushRecipients
	 */
	public function setApp(string $app): self;


	/**
	 * @return string
	 */
	public function getSource(): string;

	/**
	 * @param string $source
	 *
	 * @return IPushRecipients
	 */
	public function setSource(string $source): self;


	/**
	 * @return string
	 */
	public function getKeyword(): string;

	/**
	 * @param string $keyword
	 *
	 * @return IPushRecipients
	 */
	public function setKeyword(string $keyword): self;


	/**
	 * @return array
	 */
	public function getMeta(): array;

	/**
	 * @param array $meta
	 *
	 * @return IPushRecipients
	 */
	public function setMeta(array $meta): self;

	/**
	 * @param string $k
	 * @param string $v
	 *
	 * @return IPushRecipients
	 */
	public function addMeta(string $k, string $v): self;

	/**
	 * @param string $k
	 * @param int $v
	 *
	 * @return IPushRecipients
	 */
	public function addMetaInt(string $k, int $v): self;

	/**
	 * @param string $k
	 * @param array $v
	 *
	 * @return IPushRecipients
	 */
	public function addMetaArray(string $k, array $v): self;

	/**
	 * @param string $k
	 * @param string $v
	 *
	 * @return IPushRecipients
	 */
	public function addMetaArrayEntry(string $k, string $v): self;

	/**
	 * @param string $k
	 * @param bool $v
	 *
	 * @return IPushRecipients
	 */
	public function addMetaBool(string $k, bool $v): self;


	/**
	 * @param string $user
	 *
	 * @return IPushRecipients
	 */
	public function addUser(string $user): self;

	/**
	 * @param array $users
	 *
	 * @return IPushRecipients
	 */
	public function addUsers(array $users): self;

	/**
	 * @param string $user
	 *
	 * @return IPushRecipients
	 */
	public function removeUser(string $user): self;

	/**
	 * @param array $users
	 *
	 * @return IPushRecipients
	 */

	public function removeUsers(array $users): self;


	/**
	 * @param string $group
	 *
	 * @return IPushRecipients
	 */
	public function addGroup(string $group): self;

	/**
	 * @param array $groups
	 *
	 * @return IPushRecipients
	 */
	public function addGroups(array $groups): self;

	/**
	 * @param string $group
	 *
	 * @return IPushRecipients
	 */
	public function removeGroup(string $group): self;

	/**
	 * @param array $groups
	 *
	 * @return IPushRecipients
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
	 * @return IPushRecipients
	 */
	public function filterApp(string $app): self;

	/**
	 * @param string[] $apps
	 *
	 * @return IPushRecipients
	 */
	public function filterApps(array $apps): self;

	/**
	 * @return string[]
	 */
	public function getFilteredApps(): array;


	/**
	 * @param string $app
	 *
	 * @return IPushRecipients
	 */
	public function limitToApp(string $app): self;

	/**
	 * @param string[] $apps
	 *
	 * @return IPushRecipients
	 */
	public function limitToApps(array $apps): self;

	/**
	 * @return string[]
	 */
	public function getLimitedToApps(): array;

}

