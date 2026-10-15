<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Security\Signature\Exceptions;

use OCP\AppFramework\Attribute\Throwable;

/**
 * @since 33.0.0
 */
#[Throwable(since: '33.0.0')]
class SignatoryConflictException extends SignatoryException {
}
