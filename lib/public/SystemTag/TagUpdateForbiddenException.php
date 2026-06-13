<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP\SystemTag;

/**
 * Exception when a user doesn't have the right to create a tag
 *
 * @since 31.0.1
 */
class TagUpdateForbiddenException extends \RuntimeException {
}
