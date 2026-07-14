<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Exception;

use Sabre\CalDAV\Plugin as CalDAVPlugin;
use Sabre\CardDAV\Plugin as CardDAVPlugin;
use Sabre\DAV\Exception\Conflict;
use Sabre\DAV\Server;

/**
 * Duplicate iCalendar or vCard UID in the target collection.
 *
 * Reports the no-uid-conflict precondition with a DAV:href to the existing
 * object, as 409 Conflict (resolvable by the client):
 * - CALDAV:no-uid-conflict for calendar collections (RFC 4791 5.3.2.1)
 * - CARDDAV:no-uid-conflict for address book collections (RFC 6352 6.3.2.1)
 */
class UidConflict extends Conflict {
	private function __construct(
		private readonly string $namespace,
		private readonly string $prefix,
		private readonly string $existingObjectUri,
		string $message,
	) {
		parent::__construct($message);
	}

	/**
	 * RFC 4791 CALDAV:no-uid-conflict for a calendar object collection.
	 */
	public static function forCalendar(string $existingObjectUri): self {
		return new self(
			CalDAVPlugin::NS_CALDAV,
			'cal',
			$existingObjectUri,
			'Calendar object with uid already exists in this calendar collection.',
		);
	}

	/**
	 * RFC 6352 CARDDAV:no-uid-conflict for an address book collection.
	 */
	public static function forAddressBook(string $existingObjectUri): self {
		return new self(
			CardDAVPlugin::NS_CARDDAV,
			'card',
			$existingObjectUri,
			'VCard object with uid already exists in this addressbook collection.',
		);
	}

	#[\Override]
	public function serialize(Server $server, \DOMElement $errorNode) {
		// The conflicting object lives in the collection the resource is written
		// to. For PUT that is the request collection; for COPY and MOVE it is the
		// collection referenced by the Destination header.
		$method = $server->httpRequest->getMethod();
		if (($method === 'COPY' || $method === 'MOVE')
			&& $server->httpRequest->getHeader('Destination') !== null) {
			$targetPath = $server->calculateUri($server->httpRequest->getHeader('Destination'));
		} else {
			$targetPath = $server->getRequestUri();
		}
		[$collection] = \Sabre\Uri\split($targetPath);
		$href = $server->getBaseUri() . $collection . '/' . $this->existingObjectUri;

		$document = $errorNode->ownerDocument;
		$conflict = $document->createElementNS($this->namespace, $this->prefix . ':no-uid-conflict');
		$hrefNode = $document->createElementNS('DAV:', 'd:href');
		$hrefNode->appendChild($document->createTextNode($href));
		$conflict->appendChild($hrefNode);
		$errorNode->appendChild($conflict);
	}
}
