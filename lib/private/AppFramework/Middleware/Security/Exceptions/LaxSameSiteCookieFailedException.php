<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\AppFramework\Middleware\Security\Exceptions;

use OCP\AppFramework\Http;

/**
 * Class LaxSameSiteCookieFailedException is thrown when a request doesn't pass
 * the required LaxCookie check on index.php
 *
 * @package OC\AppFramework\Middleware\Security\Exceptions
 */
class LaxSameSiteCookieFailedException extends SecurityException {
	public function __construct() {
		parent::__construct('Lax Same Site Cookie is invalid in request.', Http::STATUS_PRECONDITION_FAILED);
	}
}
