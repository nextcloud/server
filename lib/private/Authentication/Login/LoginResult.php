<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Login;

use OC\Core\Controller\LoginController;

class LoginResult {
	/** @var bool */
	private $success;

	/** @var LoginData */
	private $loginData;

	/** @var string|null */
	private $redirectUrl;

	/** @var string|null */
	private $errorMessage;

	private function __construct(bool $success, LoginData $loginData) {
		$this->success = $success;
		$this->loginData = $loginData;
	}

	private function setRedirectUrl(string $url) {
		$this->redirectUrl = $url;
	}

	private function setErrorMessage(string $msg) {
		$this->errorMessage = $msg;
	}

	public static function success(LoginData $data, ?string $redirectUrl = null) {
		$result = new static(true, $data);
		if ($redirectUrl !== null) {
			$result->setRedirectUrl($redirectUrl);
		}
		return $result;
	}

	/**
	 * @param LoginController::LOGIN_MSG_*|null $msg
	 */
	public static function failure(LoginData $data, ?string $msg = null): LoginResult {
		$result = new static(false, $data);
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
