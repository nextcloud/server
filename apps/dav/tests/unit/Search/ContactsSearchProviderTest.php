<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit;

use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\Search\ContactsSearchProvider;
use OCP\App\IAppManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use OCP\Search\SearchResultEntry;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\VObject\Reader;
use Test\TestCase;

class ContactsSearchProviderTest extends TestCase {
	private IAppManager&MockObject $appManager;
	private IL10N&MockObject $l10n;
	private IURLGenerator&MockObject $urlGenerator;
	private CardDavBackend&MockObject $backend;
	private ContactsSearchProvider $provider;

	private string $vcardTest0 = 'BEGIN:VCARD' . PHP_EOL .
		'VERSION:3.0' . PHP_EOL .
		'PRODID:-//Sabre//Sabre VObject 4.1.2//EN' . PHP_EOL .
		'UID:Test' . PHP_EOL .
		'FN:FN of Test' . PHP_EOL .
		'N:Test;;;;' . PHP_EOL .
		'EMAIL:forrestgump@example.com' . PHP_EOL .
		'END:VCARD';

	private string $vcardTest1 = 'BEGIN:VCARD' . PHP_EOL .
		'VERSION:3.0' . PHP_EOL .
		'PRODID:-//Sabre//Sabre VObject 4.1.2//EN' . PHP_EOL .
		'PHOTO;ENCODING=b;TYPE=image/jpeg:' . PHP_EOL .
		'UID:Test2' . PHP_EOL .
		'FN:FN of Test2' . PHP_EOL .
		'N:Test2;;;;' . PHP_EOL .
		'END:VCARD';

	protected function setUp(): void {
		parent::setUp();

		$this->appManager = $this->createMock(IAppManager::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->backend = $this->createMock(CardDavBackend::class);

		$this->provider = new ContactsSearchProvider(
			$this->appManager,
			$this->l10n,
			$this->urlGenerator,
			$this->backend
		);
	}

	public function testGetId(): void {
		$this->assertEquals('contacts', $this->provider->getId());
	}

	public function testGetName(): void {
		$this->l10n->expects($this->exactly(1))
			->method('t')
			->with('Contacts')
			->willReturnArgument(0);

		$this->assertEquals('Contacts', $this->provider->getName());
	}

	public function testSearchAppDisabled(): void {
		$user = $this->createMock(IUser::class);
		$query = $this->createMock(ISearchQuery::class);
		$this->appManager->expects($this->once())
			->method('isEnabledForUser')
			->with('contacts', $user)
			->willReturn(false);
		$this->l10n->expects($this->exactly(1))
			->method('t')
			->with('Contacts')
			->willReturnArgument(0);
		$this->backend->expects($this->never())
			->method('getAddressBooksForUser');
		$this->backend->expects($this->never())
			->method('searchPrincipalUri');

		$actual = $this->provider->search($user, $query);
		$data = $actual->jsonSerialize();
		$this->assertInstanceOf(SearchResult::class, $actual);
		$this->assertEquals('Contacts', $data['name']);
		$this->assertEmpty($data['entries']);
		$this->assertFalse($data['isPaginated']);
		$this->assertNull($data['cursor']);
	}

	public function testSearch(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('john.doe');
		$query = $this->createMock(ISearchQuery::class);
		$query->method('getTerm')->willReturn('search term');
		$query->method('getLimit')->willReturn(5);
		$query->method('getCursor')->willReturn(20);
		$this->appManager->expects($this->once())
			->method('isEnabledForUser')
			->with('contacts', $user)
			->willReturn(true);
		$this->l10n->expects($this->exactly(1))
			->method('t')
			->with('Contacts')
			->willReturnArgument(0);

		$this->backend->expects($this->once())
			->method('getAddressBooksForUser')
			->with('principals/users/john.doe')
			->willReturn([
				[
					'id' => 99,
					'principaluri' => 'principals/users/john.doe',
					'uri' => 'addressbook-uri-99',
				], [
					'id' => 123,
					'principaluri' => 'principals/users/john.doe',
					'uri' => 'addressbook-uri-123',
				]
			]);
		$this->backend->expects($this->once())
			->method('searchPrincipalUri')
			->with('principals/users/john.doe', '',
				[
					'N',
					'FN',
					'NICKNAME',
					'EMAIL',
					'TEL',
					'ADR',
					'TITLE',
					'ORG',
					'NOTE',
				],
				['limit' => 5, 'offset' => 20, 'since' => null, 'until' => null,  'person' => null, 'company' => null])
			->willReturn([
				[
					'addressbookid' => 99,
					'uri' => 'vcard0.vcf',
					'carddata' => $this->vcardTest0,
				],
				[
					'addressbookid' => 123,
					'uri' => 'vcard1.vcf',
					'carddata' => $this->vcardTest1,
				],
			]);

		$provider = $this->getMockBuilder(ContactsSearchProvider::class)
			->setConstructorArgs([
				$this->appManager,
				$this->l10n,
				$this->urlGenerator,
				$this->backend,
			])
			->onlyMethods([
				'getDavUrlForContact',
				'getDeepLinkToContactsApp',
				'generateSubline',
			])
			->getMock();

		$provider->expects($this->once())
			->method('getDavUrlForContact')
			->with('principals/users/john.doe', 'addressbook-uri-123', 'vcard1.vcf')
			->willReturn('absolute-thumbnail-url');

		$provider->expects($this->exactly(2))
			->method('generateSubline')
			->willReturn('subline');
		$provider->expects($this->exactly(2))
			->method('getDeepLinkToContactsApp')
			->willReturnMap([
				['addressbook-uri-99', 'Test', 'deep-link-to-contacts'],
				['addressbook-uri-123', 'Test2', 'deep-link-to-contacts'],
			]);

		$actual = $provider->search($user, $query);
		$data = $actual->jsonSerialize();
		$this->assertInstanceOf(SearchResult::class, $actual);
		$this->assertEquals('Contacts', $data['name']);
		$this->assertCount(2, $data['entries']);
		$this->assertTrue($data['isPaginated']);
		$this->assertEquals(22, $data['cursor']);

		$result0 = $data['entries'][0];
		$result0Data = $result0->jsonSerialize();
		$result1 = $data['entries'][1];
		$result1Data = $result1->jsonSerialize();

		$this->assertInstanceOf(SearchResultEntry::class, $result0);
		$this->assertEquals('', $result0Data['thumbnailUrl']);
		$this->assertEquals('FN of Test', $result0Data['title']);
		$this->assertEquals('subline', $result0Data['subline']);
		$this->assertEquals('deep-link-to-contacts', $result0Data['resourceUrl']);
		$this->assertEquals('icon-contacts-dark', $result0Data['icon']);
		$this->assertTrue($result0Data['rounded']);

		$this->assertInstanceOf(SearchResultEntry::class, $result1);
		$this->assertEquals('absolute-thumbnail-url?photo', $result1Data['thumbnailUrl']);
		$this->assertEquals('FN of Test2', $result1Data['title']);
		$this->assertEquals('subline', $result1Data['subline']);
		$this->assertEquals('deep-link-to-contacts', $result1Data['resourceUrl']);
		$this->assertEquals('icon-contacts-dark', $result1Data['icon']);
		$this->assertTrue($result1Data['rounded']);
	}

	public function testGetDavUrlForContact(): void {
		$this->urlGenerator->expects($this->once())
			->method('linkTo')
			->with('', 'remote.php')
			->willReturn('link-to-remote.php');
		$this->urlGenerator->expects($this->once())
			->method('getAbsoluteURL')
			->with('link-to-remote.php/dav/addressbooks/users/john.doe/foo/bar.vcf')
			->willReturn('absolute-url-link-to-remote.php/dav/addressbooks/users/john.doe/foo/bar.vcf');

		$actual = self::invokePrivate($this->provider, 'getDavUrlForContact', ['principals/users/john.doe', 'foo', 'bar.vcf']);

		$this->assertEquals('absolute-url-link-to-remote.php/dav/addressbooks/users/john.doe/foo/bar.vcf', $actual);
	}

	public function testGetDeepLinkToContactsApp(): void {
		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('contacts.contacts.direct', ['contact' => 'uid123~uri-john.doe'])
			->willReturn('link-to-route-contacts.contacts.direct/direct/uid123~uri-john.doe');
		$this->urlGenerator->expects($this->once())
			->method('getAbsoluteURL')
			->with('link-to-route-contacts.contacts.direct/direct/uid123~uri-john.doe')
			->willReturn('absolute-url-link-to-route-contacts.contacts.direct/direct/uid123~uri-john.doe');

		$actual = self::invokePrivate($this->provider, 'getDeepLinkToContactsApp', ['uri-john.doe', 'uid123']);
		$this->assertEquals('absolute-url-link-to-route-contacts.contacts.direct/direct/uid123~uri-john.doe', $actual);
	}

	public function testGenerateSubline(): void {
		$vCard0 = Reader::read($this->vcardTest0);
		$vCard1 = Reader::read($this->vcardTest1);

		$actual1 = self::invokePrivate($this->provider, 'generateSubline', [$vCard0]);
		$actual2 = self::invokePrivate($this->provider, 'generateSubline', [$vCard1]);

		$this->assertEquals('forrestgump@example.com', $actual1);
		$this->assertEquals('', $actual2);
	}
}
