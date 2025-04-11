<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\AppFramework\Middleware\Security\Exceptions;

use OCP\AppFramework\Http;

/**
 * Class NotLoggedInException is thrown when a resource has been requested by a
 * guest user that is not accessible to the public.
 *
 * @package OC\AppFramework\Middleware\Security\Exceptions
 */
class NotLoggedInException extends SecurityException {
	public function __construct() {
		parent::__construct('Current user is not logged in', Http::STATUS_UNAUTHORIZED);
	}
}
