<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\User;

use OC\Files\FileInfo;
use OCP\IImage;
use OCP\IUser;
use OCP\UserInterface;
use OCP\Util;

class FakeUser implements IUser {

	private ?string $displayName = null;
	private int $firstLoginTimestamp = 0;
	private int $lastLoginTimestamp = 0;
	private string $passwordHash = '';
	private bool $enabled = true;
	private ?string $email = null;
	private string $quota = 'none';
	/** @var array<string> */
	private array $managers = [];

	public function __construct(
		private readonly string $uid,
		string $password,
		private readonly FakeUserManager $userManager,
		private readonly ?UserInterface $backend = null,
	) {
		$this->passwordHash = md5($password);
	}

	public function getUID(): string {
		return $this->uid;
	}

	public function getDisplayName(): string {
		return $this->displayName ?? $this->getUID();
	}

	public function setDisplayName(string $displayName): bool {
		$this->displayName = $displayName;
		return true;
	}

	public function getLastLogin(): int {
		return $this->lastLoginTimestamp;
	}

	public function getFirstLogin(): int {
		return $this->firstLoginTimestamp;
	}

	public function updateLastLoginTimestamp(): bool {
		$this->lastLoginTimestamp = time();
		return true;
	}

	public function delete(): bool {
		return $this->userManager->deleteUser($this);
	}

	public function setPassword($password, $recoveryPassword = null): bool {
		$this->passwordHash = md5($password);
		return true;
	}

	public function getPasswordHash(): ?string {
		return $this->passwordHash;
	}

	public function setPasswordHash(string $passwordHash): bool {
		$this->passwordHash = $passwordHash;
		return true;
	}

	public function getHome(): string {
		return '/files/' . $this->getUID();
	}

	public function getBackendClassName(): string {
		return get_class($this->backend);
	}

	public function getBackend(): ?UserInterface {
		return $this->backend;
	}

	public function canChangeAvatar(): bool {
		return true;
	}

	public function canChangePassword(): bool {
		return true;
	}

	public function canChangeDisplayName(): bool {
		return true;
	}

	public function canChangeEmail(): bool {
		return true;
	}

	public function canEditProperty(string $property): bool {
		return true;
	}

	public function isEnabled(): bool {
		return $this->enabled;
	}

	public function setEnabled(bool $enabled = true): void {
		$this->enabled = $enabled;
	}

	public function getEMailAddress(): ?string {
		return $this->email;
	}

	public function getSystemEMailAddress(): ?string {
		return $this->email;
	}

	public function getPrimaryEMailAddress(): ?string {
		return $this->email;
	}

	public function getAvatarImage($size): ?IImage {
		return null;
	}

	public function getCloudId(): string {
		return 'cloudid:' . $this->getUID();
	}

	public function setEMailAddress($mailAddress): void {
		$this->email = $mailAddress;
	}

	public function setSystemEMailAddress(string $mailAddress): void {
		$this->email = $mailAddress;
	}

	public function setPrimaryEMailAddress(string $mailAddress): void {
		$this->email = $mailAddress;
	}

	public function getQuota(): string {
		return $this->quota;
	}

	public function getQuotaBytes(): int|float {
		$quota = $this->getQuota();
		if ($quota === 'none') {
			return FileInfo::SPACE_UNLIMITED;
		}

		$bytes = Util::computerFileSize($quota);
		if ($bytes === false) {
			return FileInfo::SPACE_UNKNOWN;
		}
		return $bytes;
	}

	public function setQuota($quota): void {
		$this->quota = $quota;
	}

	public function getManagerUids(): array {
		return $this->managers;
	}

	public function setManagerUids(array $uids): void {
		$this->managers = $uids;
	}
}
