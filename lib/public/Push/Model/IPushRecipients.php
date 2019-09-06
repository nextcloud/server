<?php
declare(strict_types=1);


/**
 * Push - Nextcloud Push Service
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2020, Maxence Lange <maxence@artificial-owl.com>
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
 * This interface extends templates used by the IPushHelper to generate IPushItem
 * It contains the base to manage:
 *  - appId and source
 *  - meta
 *  - recipients (users, groups)
 *  - blacklist and whitelist of apps
 *
 * @since 18.0.0
 *
 * @package OCP\Push
 */
interface IPushRecipients {


	/**
	 * returns the appId
	 *
	 * @return string
	 *
	 * @since 18.0.0
	 */
	public function getApp(): string;

	/**
	 * set the appId
	 *
	 * @param string $app
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function setApp(string $app): self;


	/**
	 * set the source
	 *
	 * @return string
	 *
	 * @since 18.0.0
	 */
	public function getSource(): string;

	/**
	 * returns the source
	 *
	 * @param string $source
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function setSource(string $source): self;


	/**
	 * set keyword
	 *
	 * @return string
	 *
	 * @since 18.0.0
	 */
	public function getKeyword(): string;

	/**
	 * returns keyword
	 *
	 * @param string $keyword
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function setKeyword(string $keyword): self;


	/**
	 * returns all meta about the item
	 *
	 * @return array
	 *
	 * @since 18.0.0
	 */
	public function getMeta(): array;

	/**
	 * set meta for the item
	 *
	 * @param array $meta
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function setMeta(array $meta): self;

	/**
	 * add a meta
	 *
	 * @param string $k
	 * @param string $v
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function addMeta(string $k, string $v): self;

	/**
	 * add a meta (int)
	 *
	 * @param string $k
	 * @param int $v
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function addMetaInt(string $k, int $v): self;

	/**
	 * add a meta (array)
	 *
	 * @param string $k
	 * @param array $v
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function addMetaArray(string $k, array $v): self;

	/**
	 * add an entry to an existing array within meta
	 *
	 * @param string $k
	 * @param string $v
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function addMetaArrayEntry(string $k, string $v): self;

	/**
	 * add meta (bool)
	 *
	 * @param string $k
	 * @param bool $v
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function addMetaBool(string $k, bool $v): self;


	/**
	 * add a user to the list of recipients
	 *
	 * @param string $user
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function addUser(string $user): self;

	/**
	 * add a list of users to the list of recipients
	 *
	 * @param string[] $users
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function addUsers(array $users): self;

	/**
	 * remote a user from the list of recipients
	 *
	 * @param string $user
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function removeUser(string $user): self;

	/**
	 * remote multiple users from the list of recipients
	 *
	 * @param string[] $users
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function removeUsers(array $users): self;


	/**
	 * add a group to the list of recipients
	 *
	 * @param string $group
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function addGroup(string $group): self;

	/**
	 * add multiple groups to the list of recipients
	 *
	 * @param array $groups
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function addGroups(array $groups): self;

	/**
	 * remove a group from the list of recipients
	 *
	 * @param string $group
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function removeGroup(string $group): self;

	/**
	 * remove multiple groups from the list of recipients
	 *
	 * @param string[] $groups
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function removeGroups(array $groups): self;


	/**
	 * returns all users set a recipients
	 *
	 * @return string[]
	 */
	public function getUsers(): array;

	/**
	 * returns all groups set as recipients
	 *
	 * @return string[]
	 */
	public function getGroups(): array;

	/**
	 * returns all users that should be removed from the list of recipients
	 *
	 * @return string[]
	 *
	 * @since 18.0.0
	 */
	public function getRemovedUsers(): array;

	/**
	 * returns all groups that should be removed from the list of recipients
	 *
	 * @return string[]
	 *
	 * @since 18.0.0
	 */
	public function getRemovedGroups(): array;


	/**
	 * add a single app to the list of black-listed apps
	 *
	 * @param string $app
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function filterApp(string $app): self;

	/**
	 * add multiple apps to the list of black-listed apps
	 *
	 * @param string[] $apps
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function filterApps(array $apps): self;

	/**
	 * returns black-listed apps
	 *
	 * @return string[]
	 *
	 * @since 18.0.0
	 */
	public function getFilteredApps(): array;


	/**
	 * add a single app to the list of white-listed apps
	 *
	 * @param string $app
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function limitToApp(string $app): self;

	/**
	 * add multiple apps to the list of white-listed apps
	 *
	 * @param string[] $apps
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function limitToApps(array $apps): self;

	/**
	 * returns white-listed apps
	 *
	 * @return string[]
	 *
	 * @since 18.0.0
	 */
	public function getLimitedToApps(): array;

}

