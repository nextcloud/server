<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\AppFramework\Middleware\Security\Exceptions;

use OCP\AppFramework\Http;

/**
 * Class NotConfirmedException is thrown when a resource has been requested by a
 * user that has not confirmed their password in the last 30 minutes.
 *
 * @package OC\AppFramework\Middleware\Security\Exceptions
 */
class NotConfirmedException extends SecurityException {
	public function __construct(string $message = 'Password confirmation is required') {
		parent::__construct($message, Http::STATUS_FORBIDDEN);
	}
}
