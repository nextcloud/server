<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\LDAP;

/**
 * Exception for a ldap search that unexpectedly returns multiple users.
 * @since 33.0.0
 */
class MultipleUsersReturnedException extends \Exception {
}
