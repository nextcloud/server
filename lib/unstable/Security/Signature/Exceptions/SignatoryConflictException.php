<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Security\Signature\Exceptions;

/**
 * @experimental 31.0.0
 * @deprecated 33.0.0 use {@see \OCP\Security\Signature\Exceptions\SignatoryConflictException}
 * @psalm-suppress DeprecatedClass
 */
class SignatoryConflictException extends SignatoryException {
}
