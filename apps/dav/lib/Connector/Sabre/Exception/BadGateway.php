<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Connector\Sabre\Exception;

/**
 * Bad Gateway
 *
 * This exception is thrown whenever the server, while acting as a gateway or proxy, received an invalid response from the upstream server.
 *
 */
class BadGateway extends \Sabre\DAV\Exception {

	/**
	 * Returns the HTTP status code for this exception
	 *
	 * @return int
	 */
	public function getHTTPCode() {
		return 502;
	}
}
