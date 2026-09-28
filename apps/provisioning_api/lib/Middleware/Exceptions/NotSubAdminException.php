<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Provisioning_API\Middleware\Exceptions;

use OCP\AppFramework\Http;

class NotSubAdminException extends \Exception {
	public function __construct() {
		parent::__construct('Logged in account must be at least a sub admin', Http::STATUS_FORBIDDEN);
	}
}
