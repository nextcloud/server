<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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


	/**
	 * @return string
	 */
	#[\Override]
	public function getUserId() {
		return $this->data['id'];
	}

	/**
	 * @return string
	 */
	#[\Override]
	public function getEmail() {
		return $this->data['email'];
	}

	/**
	 * @return string
	 */
	#[\Override]
	public function getDisplayName() {
		return $this->data['displayname'];
	}

	/**
	 * @return string
	 */
	#[\Override]
	public function getPhone() {
		return $this->data['phone'];
	}

	/**
	 * @return string
	 */
	#[\Override]
	public function getAddress() {
		return $this->data['address'];
	}

	/**
	 * @return string
	 */
	#[\Override]
	public function getWebsite() {
		return $this->data['website'];
	}

	/**
	 * @return string
	 */
	#[\Override]
	public function getTwitter() {
		return $this->data['twitter'] ?? '';
	}

	/**
	 * @return string[]
	 */
	#[\Override]
	public function getGroups() {
		return $this->data['groups'];
	}

	/**
	 * @return string
	 */
	#[\Override]
	public function getLanguage() {
		return $this->data['language'];
	}

	/**
	 * @return int
	 */
	#[\Override]
	public function getUsedSpace() {
		return $this->data['quota']['used'];
	}

	/**
	 * @return int
	 */
	#[\Override]
	public function getFreeSpace() {
		return $this->data['quota']['free'];
	}

	/**
	 * @return int
	 */
	#[\Override]
	public function getTotalSpace() {
		return $this->data['quota']['total'];
	}

	/**
	 * @return int
	 */
	#[\Override]
	public function getQuota() {
		return $this->data['quota']['quota'];
	}
}
