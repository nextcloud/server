<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\OneTimePassword\Exceptions;

/**
 * @since 35.0.0
 */
class OTPNotFoundException extends \InvalidArgumentException {
	/**
	 * @since 35.0.0
	 */
	public function __construct(
		protected int $otpId,
	) {
		parent::__construct('No OTP found for id ' . $otpId);
	}
}
