<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Authentication\Login;

use OCP\IRequest;
use OCP\IUser;

class LoginData {
	/** @var IUser|false|null */
	private $user = null;

	public function __construct(
		private IRequest $request,
		private string $username,
		private ?string $password,
		private bool $rememberLogin = true,
		private ?string $redirectUrl = null,
		private string $timeZone = '',
		private string $timeZoneOffset = '',
	) {
	}

	public function getRequest(): IRequest {
		return $this->request;
	}

	public function setUsername(string $username): void {
		$this->username = $username;
	}

	public function getUsername(): string {
		return $this->username;
	}

	public function getPassword(): ?string {
		return $this->password;
	}

	public function getRedirectUrl(): ?string {
		return $this->redirectUrl;
	}

	public function getTimeZone(): string {
		return $this->timeZone;
	}

	public function getTimeZoneOffset(): string {
		return $this->timeZoneOffset;
	}

	/**
	 * @param IUser|false|null $user
	 */
	public function setUser($user): void {
		$this->user = $user;
	}

	/**
	 * @return false|IUser|null
	 */
	public function getUser() {
		return $this->user;
	}

	public function setRememberLogin(bool $rememberLogin): void {
		$this->rememberLogin = $rememberLogin;
	}

	public function isRememberLogin(): bool {
		return $this->rememberLogin;
	}
}
