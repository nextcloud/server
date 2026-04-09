<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Exceptions;

use OC\Authentication\Token\IToken;

/**
 * @deprecated 28.0.0 use {@see \OCP\Authentication\Exceptions\ExpiredTokenException} instead
 */
class ExpiredTokenException extends \OCP\Authentication\Exceptions\ExpiredTokenException {
	public function __construct(
		IToken $token,
	) {
		parent::__construct($token);
	}

	public function getToken(): IToken {
		$token = parent::getToken();
		/** @var IToken $token We know that we passed OC interface from constructor */
		return $token;
	}
}
