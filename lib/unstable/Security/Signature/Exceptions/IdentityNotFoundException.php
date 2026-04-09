<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Security\Signature\Exceptions;

/**
 * @experimental 31.0.0
 * @deprecated 33.0.0 use {@see \OCP\Security\Signature\Exceptions\IdentityNotFoundException}
 * @psalm-suppress DeprecatedClass
 */
class IdentityNotFoundException extends SignatureException {
}
