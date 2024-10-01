<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Talk\Exceptions;

use RuntimeException;

/**
 * Thrown when the Talk API is accessed but there is no registered backend
 *
 * @since 24.0.0
 */
final class NoBackendException extends RuntimeException {
}
