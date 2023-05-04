<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
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
