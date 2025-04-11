<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\AppFramework\Middleware\Security\Exceptions;

use OCP\AppFramework\Http;

/**
 * Class CrossSiteRequestForgeryException is thrown when a CSRF exception has
 * been encountered.
 *
 * @package OC\AppFramework\Middleware\Security\Exceptions
 */
class CrossSiteRequestForgeryException extends SecurityException {
	public function __construct() {
		parent::__construct('CSRF check failed', Http::STATUS_PRECONDITION_FAILED);
	}
}
