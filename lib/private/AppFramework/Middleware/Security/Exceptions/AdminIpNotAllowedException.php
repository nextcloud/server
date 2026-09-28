<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\AppFramework\Middleware\Security\Exceptions;

use OCP\AppFramework\Http;

/**
 * Class AdminIpNotAllowed is thrown when a resource has been requested by a
 * an admin user connecting from an unauthorized IP address
 * See configuration `allowed_admin_ranges`
 *
 * @package OC\AppFramework\Middleware\Security\Exceptions
 */
class AdminIpNotAllowedException extends SecurityException {
	public function __construct(string $message) {
		parent::__construct($message, Http::STATUS_FORBIDDEN);
	}
}
