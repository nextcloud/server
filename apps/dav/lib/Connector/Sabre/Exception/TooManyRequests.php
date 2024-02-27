<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2023 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
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
