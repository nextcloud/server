<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CalDAV\Exception;

use OCA\DAV\CalDAV\Exception\UidConflict;
use Sabre\DAV\Exception\Conflict;
use Sabre\DAV\Server;
use Test\TestCase;

class UidConflictTest extends TestCase {
	public function testHttpCodeIsConflict(): void {
		$exception = new UidConflict('sabredav-1234.ics');

		// Must stay a Conflict: CalendarImpl catches it for the OCP createFromString contract.
		self::assertInstanceOf(Conflict::class, $exception);
		self::assertSame(409, $exception->getHTTPCode());
	}

	public function testSerializeReportsNoUidConflictWithExistingHref(): void {
		$server = $this->createMock(Server::class);
		$server->method('getRequestUri')->willReturn('calendars/alice/personal/guessed.ics');
		$server->method('getBaseUri')->willReturn('/remote.php/dav/');

		$document = new \DOMDocument('1.0', 'utf-8');
		$errorNode = $document->createElementNS('DAV:', 'd:error');
		$document->appendChild($errorNode);

		$exception = new UidConflict('sabredav-1234.ics');
		$exception->serialize($server, $errorNode);

		$conflict = $errorNode->getElementsByTagNameNS('urn:ietf:params:xml:ns:caldav', 'no-uid-conflict');
		self::assertSame(1, $conflict->length);

		$href = $errorNode->getElementsByTagNameNS('DAV:', 'href');
		self::assertSame(1, $href->length);
		self::assertSame(
			'/remote.php/dav/calendars/alice/personal/sabredav-1234.ics',
			$href->item(0)->textContent,
		);
	}
}
