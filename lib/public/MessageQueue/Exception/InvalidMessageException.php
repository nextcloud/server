<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCP\MessageQueue\Exception;

use OCP\AppFramework\Attribute\Catchable;

/**
 * Thrown on dispatch when a message cannot be queued.
 *
 * @since 36.0.0
 */
#[Catchable(since: '36.0.0')]
final class InvalidMessageException extends \InvalidArgumentException {
}
