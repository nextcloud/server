<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Robin Appelman <robin@icewind.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\User;

use OCP\IUser;
use OCP\IUserManager;
use OCP\UserInterface;

class LazyUser implements IUser {
	private ?IUser $user = null;
	private string $uid;
	private IUserManager $userManager;

	public function __construct(string $uid, IUserManager $userManager) {
		$this->uid = $uid;
		$this->userManager = $userManager;
	}

	private function getUser(): IUser {
		if ($this->user === null) {
			$this->user = $this->userManager->get($this->uid);
		}
		/** @var IUser */
		$user = $this->user;
		return $user;
	}

	public function getUID() {
		return $this->uid;
	}

	public function getDisplayName() {
		return $this->userManager->getDisplayName($this->uid) ?? $this->uid;
	}

	public function setDisplayName($displayName) {
		return $this->getUser()->setDisplayName($displayName);
	}

	public function getLastLogin() {
		return $this->getUser()->getLastLogin();
	}

	public function updateLastLoginTimestamp() {
		return $this->getUser()->updateLastLoginTimestamp();
	}

	public function delete() {
		return $this->getUser()->delete();
	}

	public function setPassword($password, $recoveryPassword = null) {
		return $this->getUser()->setPassword($password, $recoveryPassword);
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
}
