<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\AppFramework\Middleware\Security\Exceptions;

use OCP\AppFramework\Http;

/**
 * Class NotAdminException is thrown when a resource has been requested by a
 * non-admin user that is not accessible to non-admin users.
 *
 * @package OC\AppFramework\Middleware\Security\Exceptions
 */
class NotAdminException extends SecurityException {
	public function __construct(string $message) {
		parent::__construct($message, Http::STATUS_FORBIDDEN);
	}
}
