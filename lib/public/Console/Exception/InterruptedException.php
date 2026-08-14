<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCP\Console\Exception;

/**
 * Exception for when the user hit ctrl-c (or the command received SIGTERM)
 *
 * @since 35.0.0
 */
class InterruptedException extends \Exception {
}
