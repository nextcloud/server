<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Connector\Sabre\Exception;

use DOMElement;
use Sabre\DAV\Exception\NotAuthenticated;
use Sabre\DAV\Server;

class PasswordLoginForbidden extends NotAuthenticated {
	public const NS_OWNCLOUD = 'http://owncloud.org/ns';

	public function getHTTPCode() {
		return 401;
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

		$error = $errorNode->ownerDocument->createElementNS('o:', 'o:hint', 'password login forbidden');
		$errorNode->appendChild($error);
	}
}
