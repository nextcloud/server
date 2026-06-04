<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Security\VerificationToken;

/** @since 23.0.0 */
class InvalidTokenException extends \Exception {
	/**
	 * @since 23.0.0
	 */
	public function __construct(int $code) {
		parent::__construct('', $code);
	}

	/**
	 * @var int
	 * @since 23.0.0
	 */
	public const USER_UNKNOWN = 1;

	/**
	 * @var int
	 * @since 23.0.0
	 */
	public const TOKEN_NOT_FOUND = 2;

	/**
	 * @var int
	 * @since 23.0.0
	 */
	public const TOKEN_DECRYPTION_ERROR = 3;

	/**
	 * @var int
	 * @since 23.0.0
	 */
	public const TOKEN_INVALID_FORMAT = 4;

	/**
	 * @var int
	 * @since 23.0.0
	 */
	public const TOKEN_EXPIRED = 5;

	/**
	 * @var int
	 * @since 23.0.0
	 */
	public const TOKEN_MISMATCH = 6;
}
