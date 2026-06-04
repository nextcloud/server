<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\LDAP\Exceptions;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Exception for a ldap search that unexpectedly returns multiple users.
 *
 * @since 34.0.0
 */
#[Consumable(since: '34.0.0')]
class MultipleUsersReturnedException extends \Exception {
}
