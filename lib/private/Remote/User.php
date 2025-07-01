<?php

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

	/** @var array */
	private $data;

	public function __construct(array $data) {
		$this->data = $data;
	}


	/**
	 * @return string
	 */
	public function getUserId() {
		return $this->data['id'];
	}

	/**
	 * @return string
	 */
	public function getEmail() {
		return $this->data['email'];
	}

	/**
	 * @return string
	 */
	public function getDisplayName() {
		return $this->data['displayname'];
	}

	/**
	 * @return string
	 */
	public function getPhone() {
		return $this->data['phone'];
	}

	/**
	 * @return string
	 */
	public function getAddress() {
		return $this->data['address'];
	}

	/**
	 * @return string
	 */
	public function getWebsite() {
		return $this->data['website'];
	}

	/**
	 * @return string
	 */
	public function getTwitter() {
		return $this->data['twitter'] ?? '';
	}

	/**
	 * @return string[]
	 */
	public function getGroups() {
		return $this->data['groups'];
	}

	/**
	 * @return string
	 */
	public function getLanguage() {
		return $this->data['language'];
	}

	/**
	 * @return int
	 */
	public function getUsedSpace() {
		return $this->data['quota']['used'];
	}

	/**
	 * @return int
	 */
	public function getFreeSpace() {
		return $this->data['quota']['free'];
	}

	/**
	 * @return int
	 */
	public function getTotalSpace() {
		return $this->data['quota']['total'];
	}

	/**
	 * @return int
	 */
	public function getQuota() {
		return $this->data['quota']['quota'];
	}
}
