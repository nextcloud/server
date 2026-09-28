<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Exception;

use OCA\DAV\Exception\UidConflict;
use Sabre\DAV\Exception\Conflict;
use Sabre\DAV\Server;
use Sabre\HTTP\RequestInterface;
use Test\TestCase;

class UidConflictTest extends TestCase {
	public function testHttpCodeIsConflict(): void {
		// Must stay a Conflict: CalendarImpl/AddressBookImpl rely on it for the
		// OCP createFromString contract.
		self::assertInstanceOf(Conflict::class, UidConflict::forCalendar('sabredav-1234.ics'));
		self::assertSame(409, UidConflict::forCalendar('sabredav-1234.ics')->getHTTPCode());
		self::assertInstanceOf(Conflict::class, UidConflict::forAddressBook('sabredav-1234.vcf'));
		self::assertSame(409, UidConflict::forAddressBook('sabredav-1234.vcf')->getHTTPCode());
	}

	public function testSerializeReportsCalDavNoUidConflictWithExistingHref(): void {
		$request = $this->createMock(RequestInterface::class);
		$request->method('getMethod')->willReturn('PUT');
		$server = $this->createMock(Server::class);
		$server->httpRequest = $request;
		$server->method('getRequestUri')->willReturn('calendars/alice/personal/guessed.ics');
		$server->method('getBaseUri')->willReturn('/remote.php/dav/');

		$errorNode = $this->serialize(UidConflict::forCalendar('sabredav-1234.ics'), $server);

		$conflict = $errorNode->getElementsByTagNameNS('urn:ietf:params:xml:ns:caldav', 'no-uid-conflict');
		self::assertSame(1, $conflict->length);
		self::assertSame(
			'/remote.php/dav/calendars/alice/personal/sabredav-1234.ics',
			$this->hrefOf($errorNode),
		);
	}

	public function testSerializeReportsCardDavNoUidConflictWithExistingHref(): void {
		$request = $this->createMock(RequestInterface::class);
		$request->method('getMethod')->willReturn('PUT');
		$server = $this->createMock(Server::class);
		$server->httpRequest = $request;
		$server->method('getRequestUri')->willReturn('addressbooks/users/alice/contacts/guessed.vcf');
		$server->method('getBaseUri')->willReturn('/remote.php/dav/');

		$errorNode = $this->serialize(UidConflict::forAddressBook('sabredav-1234.vcf'), $server);

		$conflict = $errorNode->getElementsByTagNameNS('urn:ietf:params:xml:ns:carddav', 'no-uid-conflict');
		self::assertSame(1, $conflict->length);
		self::assertSame(
			'/remote.php/dav/addressbooks/users/alice/contacts/sabredav-1234.vcf',
			$this->hrefOf($errorNode),
		);
	}

	public function testSerializeUsesDestinationCollectionForMove(): void {
		// On MOVE the request URI is the source object; the conflicting object
		// lives in the destination collection referenced by the header.
		$request = $this->createMock(RequestInterface::class);
		$request->method('getMethod')->willReturn('MOVE');
		$request->method('getHeader')->with('Destination')
			->willReturn('https://cloud.example.com/remote.php/dav/calendars/alice/work/moved.ics');
		$server = $this->createMock(Server::class);
		$server->httpRequest = $request;
		$server->method('calculateUri')->willReturn('calendars/alice/work/moved.ics');
		$server->method('getBaseUri')->willReturn('/remote.php/dav/');

		$errorNode = $this->serialize(UidConflict::forCalendar('sabredav-1234.ics'), $server);

		self::assertSame(
			'/remote.php/dav/calendars/alice/work/sabredav-1234.ics',
			$this->hrefOf($errorNode),
		);
	}

	private function serialize(UidConflict $exception, Server $server): \DOMElement {
		$document = new \DOMDocument('1.0', 'utf-8');
		$errorNode = $document->createElementNS('DAV:', 'd:error');
		$document->appendChild($errorNode);
		$exception->serialize($server, $errorNode);
		return $errorNode;
	}

	private function hrefOf(\DOMElement $errorNode): string {
		$href = $errorNode->getElementsByTagNameNS('DAV:', 'href');
		self::assertSame(1, $href->length);
		return $href->item(0)->textContent;
	}
}
