<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP\User;

use OCA\User_LDAP\Mapping\AbstractMapping;
use OCP\Config\IUserConfig;
use OCP\Share\IManager;
use OCP\Share\IShare;

class OfflineUser {
	protected ?string $dn = null;
	/** @var ?string $uid the UID as provided by LDAP */
	protected ?string $uid = null;
	protected ?string $displayName = null;
	protected ?string $homePath = null;
	/** @var ?int $lastLogin the timestamp of the last login */
	protected ?int $lastLogin = null;
	/** @var ?int $foundDeleted the timestamp when the user was detected as unavailable */
	protected ?int $foundDeleted = null;
	protected ?string $extStorageHome = null;
	protected ?string $email = null;
	protected ?bool $hasActiveShares = null;

	public function __construct(
		protected string $ocName,
		protected IUserConfig $userConfig,
		protected AbstractMapping $mapping,
		private IManager $shareManager,
	) {
	}

	/**
	 * Remove the Delete-flag from the user.
	 */
	public function unmark(): void {
		$this->userConfig->deleteUserConfig($this->ocName, 'user_ldap', 'isDeleted');
		$this->userConfig->deleteUserConfig($this->ocName, 'user_ldap', 'foundDeleted');
	}

	/**
	 * Exports the user details in an assoc array.
	 */
	public function export(): array {
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
	 * Getter for Nextcloud internal name.
	 */
	public function getOCName(): string {
		return $this->ocName;
	}

	/**
	 * Getter for LDAP uid.
	 */
	public function getUID(): string {
		if ($this->uid === null) {
			$this->fetchDetails();
		}
		return $this->uid ?? '';
	}

	/**
	 * Getter for LDAP DN.
	 */
	public function getDN(): string {
		if ($this->dn === null) {
			$dn = $this->mapping->getDNByName($this->ocName);
			$this->dn = ($dn !== false) ? $dn : '';
		}
		return $this->dn;
	}

	/**
	 * Getter for display name.
	 */
	public function getDisplayName(): string {
		if ($this->displayName === null) {
			$this->fetchDetails();
		}
		return $this->displayName ?? '';
	}

	/**
	 * Getter for email.
	 */
	public function getEmail(): string {
		if ($this->email === null) {
			$this->fetchDetails();
		}
		return $this->email ?? '';
	}

	/**
	 * Getter for home directory path.
	 */
	public function getHomePath(): string {
		if ($this->homePath === null) {
			$this->fetchDetails();
		}
		return $this->homePath ?? '';
	}

	/**
	 * Getter for the last login timestamp.
	 */
	public function getLastLogin(): int {
		if ($this->lastLogin === null) {
			$this->fetchDetails();
		}
		return $this->lastLogin ?? -1;
	}

	/**
	 * Getter for the detection timestamp.
	 */
	public function getDetectedOn(): int {
		if ($this->foundDeleted === null) {
			$this->fetchDetails();
		}
		return $this->foundDeleted ?? -1;
	}

	public function getExtStorageHome(): string {
		if ($this->extStorageHome === null) {
			$this->fetchDetails();
		}
		return $this->extStorageHome ?? '';
	}

	/**
	 * Getter for having active shares.
	 */
	public function getHasActiveShares(): bool {
		if ($this->hasActiveShares === null) {
			$this->determineShares();
		}
		return $this->hasActiveShares ?? false;
	}

	/**
	 * Reads the user details.
	 */
	protected function fetchDetails(): void {
		$this->displayName = $this->userConfig->getValueString($this->ocName, 'user_ldap', 'displayName');
		$this->uid = $this->userConfig->getValueString($this->ocName, 'user_ldap', 'uid');
		$this->homePath = $this->userConfig->getValueString($this->ocName, 'user_ldap', 'homePath');
		$this->foundDeleted = $this->userConfig->getValueInt($this->ocName, 'user_ldap', 'foundDeleted');
		$this->extStorageHome = $this->userConfig->getValueString($this->ocName, 'user_ldap', 'extStorageHome');
		$this->email = $this->userConfig->getValueString($this->ocName, 'user_ldap', 'email');
		$this->lastLogin = $this->userConfig->getValueInt($this->ocName, 'user_ldap', 'email');
	}

	/**
	 * Finds out whether the user has active shares. The result is stored in
	 * $this->hasActiveShares.
	 */
	protected function determineShares(): void {
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
