<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Exceptions;

use OC\Authentication\Token\IToken;

/**
 * @deprecated 28.0.0 use {@see \OCP\Authentication\Exceptions\WipeTokenException} instead
 */
class WipeTokenException extends \OCP\Authentication\Exceptions\WipeTokenException {
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
