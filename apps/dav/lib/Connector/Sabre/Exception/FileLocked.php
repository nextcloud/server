<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Connector\Sabre\Exception;

use Exception;
use OCP\Files\LockNotAcquiredException;

class FileLocked extends \Sabre\DAV\Exception {
	/**
	 * @param string $message
	 * @param int $code
	 */
	public function __construct($message = '', $code = 0, ?Exception $previous = null) {
		if ($previous instanceof LockNotAcquiredException) {
			$message = sprintf('Target file %s is locked by another process.', $previous->path);
		}
		parent::__construct($message, $code, $previous);
	}

	/**
	 * Returns the HTTP status code for this exception
	 *
	 * @return int
	 */
	public function getHTTPCode() {
		return 423;
	}
}
