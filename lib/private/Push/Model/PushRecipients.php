<?php
declare(strict_types=1);


/**
 * Push - Nextcloud Push Service
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2019, Maxence Lange <maxence@artificial-owl.com>
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


namespace OC\Push\Model;


use JsonSerializable;
use OCP\Push\Model\IPushRecipients;


/**
 * Class PushRecipients
 *
 * @since 18.0.0
 *
 * @package OC\Push\Model\Helper
 */
class PushRecipients implements IPushRecipients, JsonSerializable {


	/** @var string */
	private $app = '';

	/** @var string */
	private $source = '';

	/** @var string */
	private $keyword = '';

	/** @var array */
	private $meta = [];

	/** @var array */
	private $users = [];

	/** @var array */
	private $removedUsers = [];

	/** @var array */
	private $groups = [];

	/** @var array */
	private $removedGroups = [];

	/** @var array */
	private $filteredApps = [];

	/** @var array */
	private $limitToApps = [];


	/**
	 * @return string
	 *
	 * @since 18.0.0
	 */
	public function getApp(): string {
		return $this->app;
	}

	/**
	 * @param string $app
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function setApp(string $app): IPushRecipients {
		$this->app = $app;

		return $this;
	}


	/**
	 * @return string
	 *
	 * @since 18.0.0
	 */
	public function getSource(): string {
		return $this->source;
	}

	/**
	 * @param string $source
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function setSource(string $source): IPushRecipients {
		$this->source = $source;

		return $this;
	}


	/**
	 * @return string
	 *
	 * @since 18.0.0
	 */
	public function getKeyword(): string {
		return $this->keyword;
	}

	/**
	 * @param string $keyword
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function setKeyword(string $keyword): IPushRecipients {
		$this->keyword = $keyword;

		return $this;
	}


	/**
	 * @return array
	 *
	 * @since 18.0.0
	 */
	public function getMeta(): array {
		return $this->meta;
	}

	/**
	 * @param array $meta
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function setMeta(array $meta): IPushRecipients {
		$this->meta = $meta;

		return $this;
	}

	/**
	 * @param string $k
	 * @param string $v
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function addMeta(string $k, string $v): IPushRecipients {
		$this->meta[$k] = $v;

		return $this;
	}

	/**
	 * @param string $k
	 * @param int $v
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function addMetaInt(string $k, int $v): IPushRecipients {
		$this->meta[$k] = $v;

		return $this;
	}

	/**
	 * @param string $k
	 * @param array $v
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function addMetaArray(string $k, array $v): IPushRecipients {
		$this->meta[$k] = $v;

		return $this;
	}

	/**
	 * @param string $k
	 * @param string $v
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function addMetaArrayEntry(string $k, string $v): IPushRecipients {
		if (!array_key_exists($k, $this->meta)) {
			$this->meta[$k] = [];
		}

		if (!in_array($v, $this->meta[$k])) {
			$this->meta[$k][] = $v;
		}

		return $this;
	}

	/**
	 * @param string $k
	 * @param bool $v
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function addMetaBool(string $k, bool $v): IPushRecipients {
		$this->meta[$k] = $v;

		return $this;
	}


	/**
	 * @param string $user
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function addUser(string $user): IPushRecipients {
		array_push($this->users, $user);

		return $this;
	}

	/**
	 * @param string[] $users
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function addUsers(array $users): IPushRecipients {
		$this->users = array_merge($this->users, $users);

		return $this;
	}

	/**
	 * @param string $user
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function removeUser(string $user): IPushRecipients {
		return $this->removeUsers([$user]);
	}

	/**
	 * @param string[] $users
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function removeUsers(array $users): IPushRecipients {
		$this->removedUsers = array_merge($this->removedUsers, $users);


		return $this;
	}


	/**
	 * @param string $group
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function addGroup(string $group): IPushRecipients {
		array_push($this->groups, $group);

		return $this;
	}

	/**
	 * @param string[] $groups
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function addGroups(array $groups): IPushRecipients {
		$this->groups = array_merge($this->groups, $groups);

		return $this;
	}

	/**
	 * @param string $group
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function removeGroup(string $group): IPushRecipients {
		return $this->removeGroups([$group]);
	}

	/**
	 * @param string[] $groups
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function removeGroups(array $groups): IPushRecipients {
		$this->removedGroups = array_merge($this->removedGroups, $groups);

		return $this;
	}


	/**
	 * @return string[]
	 *
	 * @since 18.0.0
	 */
	public function getUsers(): array {
		return $this->users;
	}

	/**
	 * @return string[]
	 *
	 * @since 18.0.0
	 */
	public function getGroups(): array {
		return $this->groups;
	}


	/**
	 * @return string[]
	 *
	 * @since 18.0.0
	 */
	public function getRemovedUsers(): array {
		return $this->removedUsers;
	}

	/**
	 * @return string[]
	 *
	 * @since 18.0.0
	 */
	public function getRemovedGroups(): array {
		return $this->removedGroups;
	}


	/**
	 * @param string $app
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function filterApp(string $app): IPushRecipients {
		return $this->filterApps([$app]);
	}

	/**
	 * @param string[] $apps
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function filterApps(array $apps): IPushRecipients {
		$this->filteredApps = array_merge($this->filteredApps, $apps);

		return $this;
	}

	/**
	 * @return string[]
	 *
	 * @since 18.0.0
	 */
	public function getFilteredApps(): array {
		return $this->filteredApps;
	}


	/**
	 * @param string $app
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function limitToApp(string $app): IPushRecipients {
		return $this->limitToApps([$app]);
	}

	/**
	 * @param string[] $apps
	 *
	 * @return IPushRecipients
	 *
	 * @since 18.0.0
	 */
	public function limitToApps(array $apps): IPushRecipients {
		$this->limitToApps = array_merge($this->limitToApps, $apps);

		return $this;
	}

	/**
	 * @return string[]
	 *
	 * @since 18.0.0
	 */
	public function getLimitedToApps(): array {
		return $this->limitToApps;
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'_users'         => $this->getUsers(),
			'_groups'        => $this->getGroups(),
			'_removedUsers'  => $this->getRemovedUsers(),
			'_removedGroups' => $this->getRemovedGroups(),
			'_filteredApps'  => $this->getFilteredApps(),
			'_limitToApps'   => $this->getLimitedToApps()
		];
	}

}

