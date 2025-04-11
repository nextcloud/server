<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Authentication\Exceptions;

use OCP\Authentication\Token\IToken;

/**
 * @since 28.0.0
 */
class ExpiredTokenException extends InvalidTokenException {
	/**
	 * @since 28.0.0
	 */
	public function __construct(
		private IToken $token,
	) {
		parent::__construct();
	}

	/**
	 * @since 28.0.0
	 */
	public function getToken(): IToken {
		return $this->token;
	}
}
