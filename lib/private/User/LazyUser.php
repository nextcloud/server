<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\User;

use OCP\IUser;
use OCP\IUserManager;
use OCP\UserInterface;

class LazyUser implements IUser {
	private ?IUser $user = null;

	public function __construct(
		private string $uid,
		private IUserManager $userManager,
		private ?string $displayName = null,
		private ?UserInterface $backend = null,
	) {
	}

	private function getUser(): IUser {
		if ($this->user === null) {
			if ($this->backend) {
				/** @var \OC\User\Manager $manager */
				$manager = $this->userManager;
				$this->user = $manager->getUserObject($this->uid, $this->backend);
			} else {
				$this->user = $this->userManager->get($this->uid);
			}
		}

		if ($this->user === null) {
			throw new NoUserException('User not found in backend');
		}

		return $this->user;
	}

	#[\Override]
	public function getUID(): string {
		return $this->uid;
	}

	#[\Override]
	public function getDisplayName() {
		if ($this->displayName) {
			return $this->displayName;
		}

		return $this->userManager->getDisplayName($this->uid) ?? $this->uid;
	}

	#[\Override]
	public function setDisplayName($displayName) {
		return $this->getUser()->setDisplayName($displayName);
	}

	#[\Override]
	public function getLastLogin(): int {
		return $this->getUser()->getLastLogin();
	}

	#[\Override]
	public function getFirstLogin(): int {
		return $this->getUser()->getFirstLogin();
	}

	#[\Override]
	public function updateLastLoginTimestamp(): bool {
		return $this->getUser()->updateLastLoginTimestamp();
	}

	#[\Override]
	public function delete() {
		return $this->getUser()->delete();
	}

	#[\Override]
	public function setPassword($password, $recoveryPassword = null) {
		return $this->getUser()->setPassword($password, $recoveryPassword);
	}

	#[\Override]
	public function getPasswordHash(): ?string {
		return $this->getUser()->getPasswordHash();
	}

	#[\Override]
	public function setPasswordHash(string $passwordHash): bool {
		return $this->getUser()->setPasswordHash($passwordHash);
	}

	#[\Override]
	public function getHome() {
		return $this->getUser()->getHome();
	}

	#[\Override]
	public function getBackendClassName() {
		return $this->getUser()->getBackendClassName();
	}

	#[\Override]
	public function getBackend(): ?UserInterface {
		return $this->getUser()->getBackend();
	}

	#[\Override]
	public function canChangeAvatar(): bool {
		return $this->getUser()->canChangeAvatar();
	}

	#[\Override]
	public function canChangePassword(): bool {
		return $this->getUser()->canChangePassword();
	}

	#[\Override]
	public function canChangeDisplayName(): bool {
		return $this->getUser()->canChangeDisplayName();
	}

	#[\Override]
	public function canChangeEmail(): bool {
		return $this->getUser()->canChangeEmail();
	}

	#[\Override]
	public function canEditProperty(string $property): bool {
		return $this->getUser()->canEditProperty($property);
	}

	#[\Override]
	public function isEnabled() {
		return $this->getUser()->isEnabled();
	}

	#[\Override]
	public function setEnabled(bool $enabled = true) {
		return $this->getUser()->setEnabled($enabled);
	}

	#[\Override]
	public function getEMailAddress() {
		return $this->getUser()->getEMailAddress();
	}

	#[\Override]
	public function getSystemEMailAddress(): ?string {
		return $this->getUser()->getSystemEMailAddress();
	}

	#[\Override]
	public function getPrimaryEMailAddress(): ?string {
		return $this->getUser()->getPrimaryEMailAddress();
	}

	#[\Override]
	public function getAvatarImage($size) {
		return $this->getUser()->getAvatarImage($size);
	}

	#[\Override]
	public function getCloudId() {
		return $this->getUser()->getCloudId();
	}

	#[\Override]
	public function setEMailAddress($mailAddress) {
		$this->getUser()->setEMailAddress($mailAddress);
	}

	#[\Override]
	public function setSystemEMailAddress(string $mailAddress): void {
		$this->getUser()->setSystemEMailAddress($mailAddress);
	}

	#[\Override]
	public function setPrimaryEMailAddress(string $mailAddress): void {
		$this->getUser()->setPrimaryEMailAddress($mailAddress);
	}

	#[\Override]
	public function getQuota() {
		return $this->getUser()->getQuota();
	}

	#[\Override]
	public function getQuotaBytes(): int|float {
		return $this->getUser()->getQuotaBytes();
	}

	#[\Override]
	public function setQuota($quota) {
		$this->getUser()->setQuota($quota);
	}

	#[\Override]
	public function getManagerUids(): array {
		return $this->getUser()->getManagerUids();
	}

	#[\Override]
	public function setManagerUids(array $uids): void {
		$this->getUser()->setManagerUids($uids);
	}
}
