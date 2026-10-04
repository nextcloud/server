<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCP\MessageQueue\Exception;

use OCP\AppFramework\Attribute\Throwable;

/**
 * Thrown by a handler to always retry a message, even when maxRetries is reached,
 * e.g. when a remote service is temporarily unavailable.
 *
 * @since 36.0.0
 */
#[Throwable(since: '36.0.0')]
final class RecoverableMessageException extends \RuntimeException {
}
