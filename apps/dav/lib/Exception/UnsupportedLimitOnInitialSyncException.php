<?php

/**
 * @copyright Copyright (c) 2019 Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\Exception;

use Sabre\DAV\Exception\InsufficientStorage;
use Sabre\DAV\Server;

/**
 * Class UnsupportedLimitOnInitialSyncException
 *
 * @package OCA\DAV\Exception
 */
class UnsupportedLimitOnInitialSyncException extends InsufficientStorage {

	/**
	 * @inheritDoc
	 */
	public function serialize(Server $server, \DOMElement $errorNode) {
		$errorNode->appendChild($errorNode->ownerDocument->createElementNS('DAV:', 'd:number-of-matches-within-limits'));
	}
}
