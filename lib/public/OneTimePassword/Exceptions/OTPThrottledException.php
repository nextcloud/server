<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\OneTimePassword\Exceptions;

/**
 * Thrown when a new one-time password is requested before the resend delay of the previous one has passed
 *
 * @since 36.0.0
 */
class OTPThrottledException extends OTPSendException {
}
