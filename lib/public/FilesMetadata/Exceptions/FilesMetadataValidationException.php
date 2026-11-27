<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\FilesMetadata\Exceptions;

use OCP\AppFramework\Attribute\Catchable;
use OCP\AppFramework\Attribute\Throwable;

/**
 * Thrown when a value does not match the type of its metadata field
 *
 * @since 36.0.0
 */
#[Throwable(since: '36.0.0')]
#[Catchable(since: '36.0.0')]
class FilesMetadataValidationException extends FilesMetadataException {
}
