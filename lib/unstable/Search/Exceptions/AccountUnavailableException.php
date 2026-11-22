<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Search\Exceptions;

/**
 * The account does not exist or its data cannot be read at all.
 *
 * @experimental 36.0.0
 */
final class AccountUnavailableException extends \RuntimeException {
}
