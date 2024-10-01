<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 ownCloud GmbH.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Authentication\TwoFactorAuth;

use Exception;

/**
 * Two Factor Authentication failed
 *
 * It defines an Exception a 2FA app can
 * throw in case of an error. The 2FA Controller will catch this exception and
 * display this error.
 *
 * @since 12
 */
class TwoFactorException extends Exception {
}
