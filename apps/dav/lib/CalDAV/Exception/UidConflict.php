<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\Exception;

use Sabre\DAV\Exception\Conflict;
use Sabre\DAV\Server;

/**
 * Duplicate iCalendar UID in the target calendar collection.
 *
 * Reports the RFC 4791 (5.3.2.1) CALDAV:no-uid-conflict precondition with a
 * DAV:href to the existing object, as 409 Conflict (RFC 4791 1.3: resolvable).
 */
class UidConflict extends Conflict {
	public function __construct(
		private readonly string $existingObjectUri,
	) {
		parent::__construct('Calendar object with uid already exists in this calendar collection.');
	}

	#[\Override]
	public function serialize(Server $server, \DOMElement $errorNode) {
		// The conflicting object lives in the same collection as the request.
		[$collection] = \Sabre\Uri\split($server->getRequestUri());
		$href = $server->getBaseUri() . $collection . '/' . $this->existingObjectUri;

		$document = $errorNode->ownerDocument;
		$conflict = $document->createElementNS('urn:ietf:params:xml:ns:caldav', 'cal:no-uid-conflict');
		$hrefNode = $document->createElementNS('DAV:', 'd:href');
		$hrefNode->appendChild($document->createTextNode($href));
		$conflict->appendChild($hrefNode);
		$errorNode->appendChild($conflict);
	}
}
