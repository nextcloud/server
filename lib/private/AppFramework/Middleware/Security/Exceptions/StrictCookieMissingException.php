<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\AppFramework\Middleware\Security\Exceptions;

use OCP\AppFramework\Http;

/**
 * Class StrictCookieMissingException is thrown when the strict cookie has not
 * been sent with the request but is required.
 *
 * @package OC\AppFramework\Middleware\Security\Exceptions
 */
class StrictCookieMissingException extends SecurityException {
	public function __construct() {
		parent::__construct('Strict Cookie has not been found in request.', Http::STATUS_PRECONDITION_FAILED);
	}
}
