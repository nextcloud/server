<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Files;

/**
 * Exception for lost connection with the
 * @since 25.0.11
 */
class ConnectionLostException extends \RuntimeException {
}
