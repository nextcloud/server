<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Login;

use OC\Core\Controller\LoginController;

class LoginResult {
	private ?string $redirectUrl = null;
	private ?string $errorMessage = null;

	private function __construct(
		private readonly bool $success,
	) {
	}

	private function setRedirectUrl(string $url): void {
		$this->redirectUrl = $url;
	}

	private function setErrorMessage(string $msg): void {
		$this->errorMessage = $msg;
	}

	public static function success(?string $redirectUrl = null): self {
		$result = new static(true);
		if ($redirectUrl !== null) {
			$result->setRedirectUrl($redirectUrl);
		}
		return $result;
	}

	/**
	 * @param LoginController::LOGIN_MSG_*|null $msg
	 */
	public static function failure(?string $msg = null): self {
		$result = new static(false);
		if ($msg !== null) {
			$result->setErrorMessage($msg);
		}
		return $result;
	}

	public function isSuccess(): bool {
		return $this->success;
	}

	public function getRedirectUrl(): ?string {
		return $this->redirectUrl;
	}

	public function getErrorMessage(): ?string {
		return $this->errorMessage;
	}
}
