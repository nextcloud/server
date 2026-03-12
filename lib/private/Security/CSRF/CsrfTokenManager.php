<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Security\CSRF;

use OC\Security\CSRF\TokenStorage\SessionStorage;

/**
 * Class CsrfTokenManager is the manager for all CSRF token related activities.
 *
 * @package OC\Security\CSRF
 */
class CsrfTokenManager {
	private ?CsrfToken $csrfToken = null;

	public function __construct(
		private CsrfTokenGenerator $tokenGenerator,
		private SessionStorage $sessionStorage,
	) {
	}

	/**
	 * Returns the current CSRF token, if none set it will create a new one.
	 */
	public function getToken(): CsrfToken {
		if (!\is_null($this->csrfToken)) {
			return $this->csrfToken;
		}

		if ($this->sessionStorage->hasToken()) {
			$value = $this->sessionStorage->getToken();
		} else {
			$value = $this->tokenGenerator->generateToken();
			$this->sessionStorage->setToken($value);
		}

		$this->csrfToken = new CsrfToken($value);
		return $this->csrfToken;
	}

	/**
	 * Invalidates any current token and sets a new one.
	 */
	public function refreshToken(): CsrfToken {
		$value = $this->tokenGenerator->generateToken();
		$this->sessionStorage->setToken($value);
		$this->csrfToken = new CsrfToken($value);
		return $this->csrfToken;
	}

	/**
	 * Remove the current token from the storage.
	 */
	public function removeToken(): void {
		$this->csrfToken = null;
		$this->sessionStorage->removeToken();
	}

	/**
	 * Verifies whether the provided token is valid.
	 */
	public function isTokenValid(CsrfToken $token): bool {
		if (!$this->sessionStorage->hasToken()) {
			return false;
		}

		return hash_equals(
			$this->sessionStorage->getToken(),
			$token->getDecryptedValue()
		);
	}
}
