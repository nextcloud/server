<?php
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
