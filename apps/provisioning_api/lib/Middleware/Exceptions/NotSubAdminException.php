<?php

namespace OCA\Provisioning_API\Middleware\Exceptions;

use OCP\AppFramework\Http;

class NotSubAdminException extends \Exception {
	public function __construct() {
		parent::__construct('Logged in user must be at least a sub admin', Http::STATUS_FORBIDDEN);
	}
}