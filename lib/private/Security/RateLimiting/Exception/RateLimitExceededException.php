<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Security\RateLimiting\Exception;

use OC\AppFramework\Middleware\Security\Exceptions\SecurityException;
use OCP\AppFramework\Http;
use OCP\Security\RateLimiting\IRateLimitExceededException;

class RateLimitExceededException extends SecurityException implements IRateLimitExceededException {
	public function __construct() {
		parent::__construct('Rate limit exceeded', Http::STATUS_TOO_MANY_REQUESTS);
	}
}
