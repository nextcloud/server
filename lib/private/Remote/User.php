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


	public function getUserId() {
		return $this->data['id'];
	}

	public function getEmail() {
		return $this->data['email'];
	}

	public function getDisplayName() {
		return $this->data['displayname'];
	}

	public function getPhone() {
		return $this->data['phone'];
	}

	public function getAddress() {
		return $this->data['address'];
	}

	public function getWebsite() {
		return $this->data['website'];
	}

	public function getTwitter() {
		return $this->data['twitter'] ?? '';
	}

	public function getGroups() {
		return $this->data['groups'];
	}

	public function getLanguage() {
		return $this->data['language'];
	}

	public function getUsedSpace() {
		return $this->data['quota']['used'];
	}

	public function getFreeSpace() {
		return $this->data['quota']['free'];
	}

	public function getTotalSpace() {
		return $this->data['quota']['total'];
	}

	public function getQuota() {
		return $this->data['quota']['quota'];
	}
}
