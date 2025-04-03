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
	private string $uid;
	private ?string $displayName;
	private IUserManager $userManager;
	private ?UserInterface $backend;

	public function __construct(string $uid, IUserManager $userManager, ?string $displayName = null, ?UserInterface $backend = null) {
		$this->uid = $uid;
		$this->userManager = $userManager;
		$this->displayName = $displayName;
		$this->backend = $backend;
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

	public function getUID() {
		return $this->uid;
	}

	public function getDisplayName() {
		if ($this->displayName) {
			return $this->displayName;
		}

		return $this->userManager->getDisplayName($this->uid) ?? $this->uid;
	}

	public function setDisplayName($displayName) {
		return $this->getUser()->setDisplayName($displayName);
	}

	public function getLastLogin(): int {
		return $this->getUser()->getLastLogin();
	}

	public function getFirstLogin(): int {
		return $this->getUser()->getFirstLogin();
	}

	public function updateLastLoginTimestamp(): bool {
		return $this->getUser()->updateLastLoginTimestamp();
	}

	public function delete() {
		return $this->getUser()->delete();
	}

	public function setPassword($password, $recoveryPassword = null) {
		return $this->getUser()->setPassword($password, $recoveryPassword);
	}

	public function getPasswordHash(): ?string {
		return $this->getUser()->getPasswordHash();
	}

	public function setPasswordHash(string $passwordHash): bool {
		return $this->getUser()->setPasswordHash($passwordHash);
	}

	public function getHome() {
		return $this->getUser()->getHome();
	}

	public function getBackendClassName() {
		return $this->getUser()->getBackendClassName();
	}

	public function getBackend(): ?UserInterface {
		return $this->getUser()->getBackend();
	}

	public function canChangeAvatar() {
		return $this->getUser()->canChangeAvatar();
	}

	public function canChangePassword() {
		return $this->getUser()->canChangePassword();
	}

	public function canChangeDisplayName() {
		return $this->getUser()->canChangeDisplayName();
	}

	public function canChangeEmail(): bool {
		return $this->getUser()->canChangeEmail();
	}

	public function isEnabled() {
		return $this->getUser()->isEnabled();
	}

	public function setEnabled(bool $enabled = true) {
		return $this->getUser()->setEnabled($enabled);
	}

	public function getEMailAddress() {
		return $this->getUser()->getEMailAddress();
	}

	public function getSystemEMailAddress(): ?string {
		return $this->getUser()->getSystemEMailAddress();
	}

	public function getPrimaryEMailAddress(): ?string {
		return $this->getUser()->getPrimaryEMailAddress();
	}

	public function getAvatarImage($size) {
		return $this->getUser()->getAvatarImage($size);
	}

	public function getCloudId() {
		return $this->getUser()->getCloudId();
	}

	public function setEMailAddress($mailAddress) {
		$this->getUser()->setEMailAddress($mailAddress);
	}

	public function setSystemEMailAddress(string $mailAddress): void {
		$this->getUser()->setSystemEMailAddress($mailAddress);
	}

	public function setPrimaryEMailAddress(string $mailAddress): void {
		$this->getUser()->setPrimaryEMailAddress($mailAddress);
	}

	public function getQuota() {
		return $this->getUser()->getQuota();
	}

	public function setQuota($quota) {
		$this->getUser()->setQuota($quota);
	}

	public function getManagerUids(): array {
		return $this->getUser()->getManagerUids();
	}

	public function setManagerUids(array $uids): void {
		$this->getUser()->setManagerUids($uids);
	}
}
