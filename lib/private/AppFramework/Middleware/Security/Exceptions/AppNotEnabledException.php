<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\AppFramework\Middleware\Security\Exceptions;

use OCP\AppFramework\Http;

/**
 * Class AppNotEnabledException is thrown when a resource for an application is
 * requested that is not enabled.
 *
 * @package OC\AppFramework\Middleware\Security\Exceptions
 */
class AppNotEnabledException extends SecurityException {
	public function __construct() {
		parent::__construct('App is not enabled', Http::STATUS_PRECONDITION_FAILED);
	}
}
