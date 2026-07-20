<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\AppFramework\Middleware\Security\Exceptions;

use OCP\AppFramework\Http;

/**
 * Class StrictCookieMissingException is thrown when a required strict cookie 
 * is missing from the request.
 *
 * @package OC\AppFramework\Middleware\Security\Exceptions
 */
class StrictCookieMissingException extends SecurityException {
	public function __construct() {
		parent::__construct(
			'Required strict cookie is missing from the request.',
			Http::STATUS_PRECONDITION_FAILED,
		);
	}
}
