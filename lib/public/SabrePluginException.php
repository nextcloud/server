<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP;

use Sabre\DAV\Exception;

/**
 * @since 8.2.0
 */
class SabrePluginException extends Exception {
	/**
	 * Returns the HTTP statuscode for this exception
	 *
	 * @return int
	 * @since 8.2.0
	 */
	public function getHTTPCode() {
		return $this->code;
	}
}
