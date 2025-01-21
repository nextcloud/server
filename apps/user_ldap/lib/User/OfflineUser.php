<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP\User;

use OCA\User_LDAP\Mapping\UserMapping;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Share\IManager;
use OCP\Share\IShare;

class OfflineUser {
	/**
	 * @var string $dn
	 */
	protected $dn;
	/**
	 * @var string $uid the UID as provided by LDAP
	 */
	protected $uid;
	/**
	 * @var string $displayName
	 */
	protected $displayName;
	/**
	 * @var string $homePath
	 */
	protected $homePath;
	/**
	 * @var string $lastLogin the timestamp of the last login
	 */
	protected $lastLogin;
	/**
	 * @var string $foundDeleted the timestamp when the user was detected as unavailable
	 */
	protected $foundDeleted;
	protected ?string $extStorageHome = null;
	/**
	 * @var string $email
	 */
	protected $email;
	/**
	 * @var bool $hasActiveShares
	 */
	protected $hasActiveShares;
	/**
	 * @var IDBConnection $db
	 */
	protected $db;

	/**
	 * @param string $ocName
	 */
	public function __construct(
		protected $ocName,
		protected IConfig $config,
		protected UserMapping $mapping,
		private IManager $shareManager,
	) {
	}

	/**
	 * remove the Delete-flag from the user.
	 */
	public function unmark() {
		$this->config->deleteUserValue($this->ocName, 'user_ldap', 'isDeleted');
		$this->config->deleteUserValue($this->ocName, 'user_ldap', 'foundDeleted');
	}

	/**
	 * exports the user details in an assoc array
	 * @return array
	 */
	public function export() {
		$data = [];
		$data['ocName'] = $this->getOCName();
		$data['dn'] = $this->getDN();
		$data['uid'] = $this->getUID();
		$data['displayName'] = $this->getDisplayName();
		$data['homePath'] = $this->getHomePath();
		$data['lastLogin'] = $this->getLastLogin();
		$data['email'] = $this->getEmail();
		$data['hasActiveShares'] = $this->getHasActiveShares();

		return $data;
	}

	/**
	 * getter for Nextcloud internal name
	 * @return string
	 */
	public function getOCName() {
		return $this->ocName;
	}

	/**
	 * getter for LDAP uid
	 * @return string
	 */
	public function getUID() {
		if ($this->uid === null) {
			$this->fetchDetails();
		}
		return $this->uid;
	}

	/**
	 * getter for LDAP DN
	 * @return string
	 */
	public function getDN() {
		if ($this->dn === null) {
			$dn = $this->mapping->getDNByName($this->ocName);
			$this->dn = ($dn !== false) ? $dn : '';
		}
		return $this->dn;
	}

	/**
	 * getter for display name
	 * @return string
	 */
	public function getDisplayName() {
		if ($this->displayName === null) {
			$this->fetchDetails();
		}
		return $this->displayName;
	}

	/**
	 * getter for email
	 * @return string
	 */
	public function getEmail() {
		if ($this->email === null) {
			$this->fetchDetails();
		}
		return $this->email;
	}

	/**
	 * getter for home directory path
	 * @return string
	 */
	public function getHomePath() {
		if ($this->homePath === null) {
			$this->fetchDetails();
		}
		return $this->homePath;
	}

	/**
	 * getter for the last login timestamp
	 * @return int
	 */
	public function getLastLogin() {
		if ($this->lastLogin === null) {
			$this->fetchDetails();
		}
		return (int)$this->lastLogin;
	}

	/**
	 * getter for the detection timestamp
	 * @return int
	 */
	public function getDetectedOn() {
		if ($this->foundDeleted === null) {
			$this->fetchDetails();
		}
		return (int)$this->foundDeleted;
	}

	public function getExtStorageHome(): string {
		if ($this->extStorageHome === null) {
			$this->fetchDetails();
		}
		return (string)$this->extStorageHome;
	}

	/**
	 * getter for having active shares
	 * @return bool
	 */
	public function getHasActiveShares() {
		if ($this->hasActiveShares === null) {
			$this->determineShares();
		}
		return $this->hasActiveShares;
	}

	/**
	 * reads the user details
	 */
	protected function fetchDetails() {
		$properties = [
			'displayName' => 'user_ldap',
			'uid' => 'user_ldap',
			'homePath' => 'user_ldap',
			'foundDeleted' => 'user_ldap',
			'extStorageHome' => 'user_ldap',
			'email' => 'settings',
			'lastLogin' => 'login',
		];
		foreach ($properties as $property => $app) {
			$this->$property = $this->config->getUserValue($this->ocName, $app, $property, '');
		}
	}

	/**
	 * finds out whether the user has active shares. The result is stored in
	 * $this->hasActiveShares
	 */
	protected function determineShares() {
		$shareInterface = new \ReflectionClass(IShare::class);
		$shareConstants = $shareInterface->getConstants();

		foreach ($shareConstants as $constantName => $constantValue) {
			if (!str_starts_with($constantName, 'TYPE_')
				|| $constantValue === IShare::TYPE_USERGROUP
			) {
				continue;
			}
			$shares = $this->shareManager->getSharesBy(
				$this->ocName,
				$constantValue,
				null,
				false,
				1
			);
			if (!empty($shares)) {
				$this->hasActiveShares = true;
				return;
			}
		}

		$this->hasActiveShares = false;
	}
}
