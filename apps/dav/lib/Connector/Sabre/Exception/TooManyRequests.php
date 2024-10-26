<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Connector\Sabre\Exception;

use DOMElement;
use Sabre\DAV\Exception\NotAuthenticated;
use Sabre\DAV\Server;

class TooManyRequests extends NotAuthenticated {
	public const NS_OWNCLOUD = 'http://owncloud.org/ns';

	public function getHTTPCode() {
		return 429;
	}

	/**
	 * This method allows the exception to include additional information
	 * into the WebDAV error response
	 *
	 * @param Server $server
	 * @param DOMElement $errorNode
	 * @return void
	 */
	public function serialize(Server $server, DOMElement $errorNode) {

		// set ownCloud namespace
		$errorNode->setAttribute('xmlns:o', self::NS_OWNCLOUD);

		$error = $errorNode->ownerDocument->createElementNS('o:', 'o:hint', 'too many requests');
		$errorNode->appendChild($error);
	}
}
