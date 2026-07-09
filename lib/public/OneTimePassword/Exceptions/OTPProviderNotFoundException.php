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
class OTPProviderNotFoundException extends \InvalidArgumentException {
	/**
	 * @since 35.0.0
	 */
	public function __construct(
		protected ?string $providerId,
	) {
		parent::__construct($this->providerId ? 'No OTP provider found for id ' . $this->providerId : 'No OTP providers configured');
	}
}
