<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\AppFramework\Middleware\Security\Exceptions;

use OCP\AppFramework\Http;

/**
 * Class ExAppRequiredException is thrown when an endpoint can only be called by an ExApp but the caller is not an ExApp.
 */
class ExAppRequiredException extends SecurityException {
	public function __construct() {
		parent::__construct('ExApp required', Http::STATUS_PRECONDITION_FAILED);
	}
}
