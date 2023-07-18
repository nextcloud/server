<?php
/**
 * @copyright Copyright (c) 2017 Robin Appelman <robin@icewind.nl>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Robin Appelman <robin@icewind.nl>
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
namespace OC\Remote;

use OCP\Remote\IUser;

class User implements IUser {
	public const EXPECTED_KEYS = [
		'id',
		'email',
		'displayname',
		'phone',
		'address',
		'website',
		'groups',
		'language',
		'quota'
	];

	public function __construct(
		private array $data,
	) {
	}


	public function getUserId(): string {
		return $this->data['id'];
	}

	public function getEmail(): string {
		return $this->data['email'];
	}

	/**
	 * @return string
	 */
	public function getDisplayName(): string {
		return $this->data['displayname'];
	}

	/**
	 * @return string
	 */
	public function getPhone(): string {
		return $this->data['phone'];
	}

	/**
	 * @return string
	 */
	public function getAddress(): string {
		return $this->data['address'];
	}

	/**
	 * @return string
	 */
	public function getWebsite(): string {
		return $this->data['website'];
	}

	/**
	 * @return string
	 */
	public function getTwitter(): string {
		return isset($this->data['twitter']) ? $this->data['twitter'] : '';
	}

	/**
	 * @return string[]
	 */
	public function getGroups(): array {
		return $this->data['groups'];
	}

	/**
	 * @return string
	 */
	public function getLanguage(): string {
		return $this->data['language'];
	}

	/**
	 * @return int|float
	 */
	public function getUsedSpace(): int|float {
		return $this->data['quota']['used'];
	}

	/**
	 * @return int|float
	 */
	public function getFreeSpace(): int|float {
		return $this->data['quota']['free'];
	}

	/**
	 * @return int|float
	 */
	public function getTotalSpace(): int|float {
		return $this->data['quota']['total'];
	}

	/**
	 * @return int|float
	 */
	public function getQuota(): int|float {
		return $this->data['quota']['quota'];
	}
}
