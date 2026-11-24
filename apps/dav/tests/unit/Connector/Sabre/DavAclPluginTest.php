<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OCA\DAV\CalDAV\Calendar;
use OCA\DAV\CalDAV\CalendarHome;
use OCA\DAV\CardDAV\AddressBook;
use OCA\DAV\CardDAV\UserAddressBooks;
use OCA\DAV\Connector\Sabre\DavAclPlugin;
use PHPUnit\Framework\Attributes\DataProvider;
use Sabre\CalDAV\CalendarObject;
use Sabre\CalDAV\Xml\Request\CalendarMultiGetReport;
use Sabre\CardDAV\Card;
use Sabre\CardDAV\Xml\Request\AddressBookMultiGetReport;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\ICollection;
use Sabre\DAV\INode;
use Sabre\DAV\IProperties;
use Sabre\DAV\Server;
use Sabre\DAV\Tree;
use Sabre\HTTP\Request;
use Sabre\HTTP\Response;
use Test\TestCase;

class DavAclPluginTest extends TestCase {
	private const ALICE = 'principals/users/alice';
	private const BOB = 'principals/users/bob';

	/** @var array<string, INode> */
	private array $nodes = [];
	/** @var array<string, int> */
	private array $nodeExistsCalls = [];
	private Server $server;
	private array $defaultSupportedPrivilegeSet;

	protected function setUp(): void {
		parent::setUp();

		$defaultPlugin = new DavAclPlugin();
		(new Server())->addPlugin($defaultPlugin);
		$this->defaultSupportedPrivilegeSet = $defaultPlugin->getSupportedPrivilegeSet($this->collection(''));

		$this->nodes = [
			'' => $this->collection(''),
			'calendars' => $this->collection('calendars'),
			'calendars/alice' => $this->aclNode(CalendarHome::class, 'alice', self::ALICE),
			'calendars/alice/personal' => $this->aclNode(Calendar::class, 'personal', self::ALICE),
			'calendars/alice/personal/event.ics' => $this->aclNode(CalendarObject::class, 'event.ics', self::ALICE),
			'calendars/bob' => $this->aclNode(CalendarHome::class, 'bob', self::BOB),
			'calendars/bob/personal' => $this->aclNode(Calendar::class, 'personal', self::BOB),
			'calendars/bob/personal/event.ics' => $this->aclNode(CalendarObject::class, 'event.ics', self::BOB),
			'calendars/bob/personal/private.ics' => $this->aclNode(CalendarObject::class, 'private.ics', self::ALICE),
			'calendars/bob/hidden' => $this->aclNode(Calendar::class, 'hidden', self::ALICE),
			'calendars/bob/hidden/event.ics' => $this->aclNode(CalendarObject::class, 'event.ics', self::ALICE),
			'calendars/bob/readonly' => $this->aclNode(Calendar::class, 'readonly', self::ALICE, [self::ALICE], [self::BOB]),
			'addressbooks' => $this->collection('addressbooks'),
			'addressbooks/users' => $this->collection('users'),
			'addressbooks/users/alice' => $this->aclNode(UserAddressBooks::class, 'alice', self::ALICE),
			'addressbooks/users/alice/contacts' => $this->aclNode(AddressBook::class, 'contacts', self::ALICE),
			'addressbooks/users/alice/contacts/card.vcf' => $this->aclNode(Card::class, 'card.vcf', self::ALICE),
			'addressbooks/users/bob' => $this->aclNode(UserAddressBooks::class, 'bob', self::BOB),
			'addressbooks/users/bob/contacts' => $this->aclNode(AddressBook::class, 'contacts', self::BOB),
			'addressbooks/users/bob/contacts/private.vcf' => $this->aclNode(Card::class, 'private.vcf', self::ALICE),
			'addressbooks/system' => $this->collection('system'),
			'addressbooks/system/system' => $this->aclNode(UserAddressBooks::class, 'system', 'principals/system/system'),
			'addressbooks/system/system/system' => $this->aclNode(AddressBook::class, 'system', 'principals/system/system', ['principals/system/system', self::BOB]),
			'files' => $this->collection('files'),
		];

		$tree = $this->createMock(Tree::class);
		$tree->method('nodeExists')
			->willReturnCallback(function (string $path): bool {
				$this->nodeExistsCalls[$path] = ($this->nodeExistsCalls[$path] ?? 0) + 1;
				return isset($this->nodes[$path]);
			});
		$tree->method('getNodeForPath')
			->willReturnCallback(function (string $path): INode {
				if (!isset($this->nodes[$path])) {
					throw new NotFound('Missing ' . $path);
				}
				return $this->nodes[$path];
			});

		$this->server = new Server($tree);
		$this->server->setBaseUri('/remote.php/dav/');
	}

	private function collection(string $name): ICollection {
		$node = $this->createMock(ICollection::class);
		$node->method('getName')->willReturn($name);
		return $node;
	}

	/**
	 * @param class-string $class
	 * @param list<string>|null $allPrincipals principals with all privileges, defaults to the owner
	 * @param list<string> $readPrincipals principals with read privileges
	 */
	private function aclNode(string $class, string $name, string $owner, ?array $allPrincipals = null, array $readPrincipals = []): INode {
		$acl = [];
		foreach ($allPrincipals ?? [$owner] as $principal) {
			$acl[] = ['privilege' => '{DAV:}all', 'principal' => $principal, 'protected' => true];
		}
		foreach ($readPrincipals as $principal) {
			$acl[] = ['privilege' => '{DAV:}read', 'principal' => $principal, 'protected' => true];
		}

		$node = $this->createMock($class);
		$node->method('getName')->willReturn($name);
		$node->method('getOwner')->willReturn($owner);
		$node->method('getACL')->willReturn($acl);
		$node->method('getSupportedPrivilegeSet')->willReturn($this->defaultSupportedPrivilegeSet);
		if ($node instanceof IProperties) {
			$node->method('getProperties')->willReturn([]);
		}
		return $node;
	}

	private function createPlugin(string $principal): DavAclPlugin {
		$plugin = new class($principal) extends DavAclPlugin {
			public function __construct(
				private string $principal,
			) {
				parent::__construct();
			}

			#[\Override]
			public function getCurrentUserPrincipal() {
				return $this->principal;
			}

			#[\Override]
			public function getPrincipalMembership($mainPrincipal) {
				return [];
			}
		};
		$this->server->addPlugin($plugin);
		return $plugin;
	}

	private function request(string $method, string $path, array $headers = []): Request {
		$request = new Request($method, '/remote.php/dav/' . $path, $headers);
		$request->setBaseUrl('/remote.php/dav/');
		return $request;
	}

	private function assertNotFound(DavAclPlugin $plugin, Request $request, string $message): void {
		try {
			$plugin->beforeMethod($request, new Response());
			$this->fail('Expected NotFound for ' . $request->getMethod() . ' ' . $request->getPath());
		} catch (NotFound $e) {
			$this->assertSame($message, $e->getMessage(), $request->getMethod() . ' ' . $request->getPath());
		}
	}

	public static function dataOtherUsersPaths(): array {
		return [
			// Calendars in another user's home answer like CalendarHome::getChild()
			['calendars/alice/personal', "Node with name 'personal' could not be found"],
			['calendars/alice/missing', "Node with name 'missing' could not be found"],
			['calendars/alice/personal/event.ics', "Node with name 'personal' could not be found"],
			['calendars/alice/personal/missing.ics', "Node with name 'personal' could not be found"],
			['calendars/alice/missing/event.ics', "Node with name 'missing' could not be found"],
			['calendars/alice/personal_shared_by_admin', "Node with name 'personal_shared_by_admin' could not be found"],
			// Address books in another user's home answer like AddressBookHome::getChild()
			['addressbooks/users/alice/contacts', "Addressbook with name 'contacts' could not be found"],
			['addressbooks/users/alice/missing', "Addressbook with name 'missing' could not be found"],
			['addressbooks/users/alice/contacts/card.vcf', "Addressbook with name 'contacts' could not be found"],
			['addressbooks/users/alice/contacts/missing.vcf', "Addressbook with name 'contacts' could not be found"],
			['addressbooks/users/alice/missing/card.vcf', "Addressbook with name 'missing' could not be found"],
			['addressbooks/users/alice/contacts_shared_by_admin/x.vcf', "Addressbook with name 'contacts_shared_by_admin' could not be found"],
			// The other user's home itself
			['calendars/alice', "Node with name 'alice' could not be found"],
			['addressbooks/users/alice', "Node with name 'alice' could not be found"],
		];
	}

	#[DataProvider('dataOtherUsersPaths')]
	public function testOtherUsersPathsLookMissing(string $path, string $message): void {
		$plugin = $this->createPlugin(self::BOB);

		foreach (['GET', 'PROPFIND', 'PROPPATCH', 'PUT', 'DELETE', 'OPTIONS', 'POST', 'LOCK', 'ACL', 'REPORT', 'MKCOL', 'MKCALENDAR'] as $method) {
			$this->assertNotFound($plugin, $this->request($method, $path), $message);
		}
	}

	public static function dataUnreadableNodeInReadableCollection(): array {
		return [
			// Answered like the parent's getChild() for a missing child
			['calendars/bob/hidden', "Node with name 'hidden' could not be found"],
			['calendars/bob/hidden/event.ics', "Node with name 'hidden' could not be found"],
			['calendars/bob/hidden/missing.ics', "Node with name 'hidden' could not be found"],
			['calendars/bob/personal/private.ics', 'Calendar object not found'],
			['addressbooks/users/bob/contacts/private.vcf', 'Card not found'],
		];
	}

	#[DataProvider('dataUnreadableNodeInReadableCollection')]
	public function testUnreadableNodeInReadableCollection(string $path, string $message): void {
		$plugin = $this->createPlugin(self::BOB);

		$this->assertNotFound($plugin, $this->request('GET', $path), $message);
	}

	public static function dataAccessiblePaths(): array {
		return [
			['calendars/bob/personal'],
			['calendars/bob/personal/event.ics'],
			// Genuinely missing nodes in readable collections are left to the tree
			['calendars/bob/missing'],
			['calendars/bob/missing/event.ics'],
			['calendars/bob/personal/missing.ics'],
			['addressbooks/users/bob/contacts/missing.vcf'],
			// Read-only share
			['calendars/bob/readonly'],
			['calendars/bob/readonly/missing.ics'],
			// Readable address book in a home that is not readable
			['addressbooks/system/system/system'],
			['addressbooks/system/system/system/missing.vcf'],
		];
	}

	#[DataProvider('dataAccessiblePaths')]
	public function testAccessiblePaths(string $path): void {
		$plugin = $this->createPlugin(self::BOB);

		$plugin->beforeMethod($this->request('GET', $path), new Response());
		$this->addToAssertionCount(1);
	}

	public function testOwnerIsNotAffected(): void {
		$plugin = $this->createPlugin(self::ALICE);

		foreach (['calendars/alice/personal', 'calendars/alice/missing/event.ics', 'addressbooks/users/alice/contacts/missing.vcf', 'calendars/bob/hidden/event.ics'] as $path) {
			$plugin->beforeMethod($this->request('GET', $path), new Response());
		}
		$this->addToAssertionCount(1);
	}

	public function testOwnerGetsForbidden(): void {
		$this->nodes['calendars/alice/personal'] = $this->aclNode(Calendar::class, 'personal', self::ALICE, [], [self::ALICE]);
		$plugin = $this->createPlugin(self::ALICE);

		$this->expectException(Forbidden::class);
		$plugin->checkPrivileges('calendars/alice/personal', '{DAV:}write');
	}

	public function testCheckPrivilegesWritingToReadOnlyShare(): void {
		$plugin = $this->createPlugin(self::BOB);

		$this->assertTrue($plugin->checkPrivileges('calendars/bob/readonly', '{DAV:}read'));
		$this->expectException(NotFound::class);
		$this->expectExceptionMessage("Node with name 'readonly' could not be found");
		$plugin->checkPrivileges('calendars/bob/readonly', '{DAV:}write');
	}

	public static function dataCheckPrivilegesMessage(): array {
		return [
			['calendars/alice/personal', "Node with name 'personal' could not be found"],
			['calendars/alice/personal/event.ics', "Node with name 'personal' could not be found"],
			['addressbooks/users/alice/contacts', "Addressbook with name 'contacts' could not be found"],
			['addressbooks/users/alice/contacts/card.vcf', "Addressbook with name 'contacts' could not be found"],
			['calendars/alice', "Node with name 'alice' could not be found"],
			// Unreadable node in a readable collection
			['calendars/bob/personal/private.ics', 'Calendar object not found'],
			['addressbooks/users/bob/contacts/private.vcf', 'Card not found'],
		];
	}

	#[DataProvider('dataCheckPrivilegesMessage')]
	public function testCheckPrivilegesMessage(string $path, string $message): void {
		$plugin = $this->createPlugin(self::BOB);

		$this->assertFalse($plugin->checkPrivileges($path, '{DAV:}read', DavAclPlugin::R_PARENT, false));
		$this->expectException(NotFound::class);
		$this->expectExceptionMessage($message);
		$plugin->checkPrivileges($path, '{DAV:}read');
	}

	public static function dataCopyMoveDestination(): array {
		return [
			['COPY', 'calendars/alice/personal/new.ics', "Node with name 'personal' could not be found"],
			['MOVE', 'calendars/alice/personal/event.ics', "Node with name 'personal' could not be found"],
			['MOVE', 'calendars/alice/missing/new.ics', "Node with name 'missing' could not be found"],
			['MOVE', 'addressbooks/users/alice/contacts/new.vcf', "Addressbook with name 'contacts' could not be found"],
			['COPY', 'addressbooks/users/alice/missing/new.vcf', "Addressbook with name 'missing' could not be found"],
			['COPY', 'addressbooks/users/alice/contacts/card.vcf', "Addressbook with name 'contacts' could not be found"],
		];
	}

	#[DataProvider('dataCopyMoveDestination')]
	public function testCopyMoveDestination(string $method, string $destination, string $message): void {
		$plugin = $this->createPlugin(self::BOB);

		foreach (['calendars/bob/personal/event.ics', 'files/bob/x'] as $source) {
			foreach (['T', 'F'] as $overwrite) {
				$request = $this->request($method, $source, [
					'Destination' => 'https://cloud.example.com/remote.php/dav/' . $destination,
					'Overwrite' => $overwrite,
				]);
				$this->assertNotFound($plugin, $request, $message);
			}
		}
	}

	public function testCopyMoveToOwnCollection(): void {
		$plugin = $this->createPlugin(self::BOB);

		foreach (['calendars/bob/personal/new.ics', 'calendars/bob/missing/new.ics', 'files/bob/x', '/outside/base'] as $destination) {
			$request = $this->request('MOVE', 'calendars/bob/personal/event.ics', [
				'Destination' => $destination[0] === '/' ? $destination : '/remote.php/dav/' . $destination,
			]);
			$plugin->beforeMethod($request, new Response());
		}
		$this->addToAssertionCount(1);
	}

	public static function dataOtherUsersHrefs(): array {
		return [
			['calendars/alice/personal/event.ics', "Node with name 'personal' could not be found"],
			['calendars/alice/personal/missing.ics', "Node with name 'personal' could not be found"],
			['calendars/alice/missing/event.ics', "Node with name 'missing' could not be found"],
			['addressbooks/users/alice/contacts/card.vcf', "Addressbook with name 'contacts' could not be found"],
			['addressbooks/users/alice/missing/card.vcf', "Addressbook with name 'missing' could not be found"],
		];
	}

	#[DataProvider('dataOtherUsersHrefs')]
	public function testIfHeaderUris(string $path, string $message): void {
		$plugin = $this->createPlugin(self::BOB);

		foreach (['calendars/bob/personal/event.ics', 'files/bob/x'] as $requestPath) {
			$request = $this->request('PUT', $requestPath, [
				'If' => '<https://cloud.example.com/remote.php/dav/' . $path . '> (["etag"])',
			]);
			$this->assertNotFound($plugin, $request, $message);
		}
	}

	public function testIfHeaderOwnUris(): void {
		$plugin = $this->createPlugin(self::BOB);

		foreach (['calendars/bob/personal/event.ics', 'calendars/bob/missing/x.ics', 'files/bob/x'] as $path) {
			$request = $this->request('PUT', 'calendars/bob/personal/event.ics', [
				'If' => '(["etag"]) <https://cloud.example.com/remote.php/dav/' . $path . '> (["etag"])',
			]);
			$plugin->beforeMethod($request, new Response());
		}
		$plugin->beforeMethod($this->request('PUT', 'calendars/bob/personal/event.ics', ['If' => '</outside/base> (["etag"])']), new Response());
		$this->addToAssertionCount(1);
	}

	#[DataProvider('dataOtherUsersHrefs')]
	public function testMultiGetHrefs(string $path, string $message): void {
		$this->createPlugin(self::BOB);

		$report = str_starts_with($path, 'calendars/') ? new CalendarMultiGetReport() : new AddressBookMultiGetReport();
		$report->hrefs = ['/remote.php/dav/calendars/bob/personal/event.ics', '/remote.php/dav/' . $path];

		$this->expectException(NotFound::class);
		$this->expectExceptionMessage($message);
		$this->server->emit('report', ['{urn:ietf:params:xml:ns:caldav}calendar-multiget', $report, 'calendars/bob/personal']);
	}

	#[DataProvider('dataOtherUsersHrefs')]
	public function testExpandPropertyHrefs(string $path, string $message): void {
		$plugin = $this->createPlugin(self::BOB);
		$expandProperties = new \ReflectionMethod($plugin, 'expandProperties');

		$this->expectException(NotFound::class);
		$this->expectExceptionMessage($message);
		$expandProperties->invoke($plugin, '/' . $path, ['{DAV:}displayname' => []], 0);
	}

	public function testExpandPropertyOwnHrefs(): void {
		$plugin = $this->createPlugin(self::BOB);
		$expandProperties = new \ReflectionMethod($plugin, 'expandProperties');

		foreach (['calendars/bob/personal/event.ics', 'calendars/bob/readonly'] as $path) {
			$this->assertCount(1, $expandProperties->invoke($plugin, '/' . $path, ['{DAV:}displayname' => []], 0));
		}
	}

	public function testMultiGetOwnHrefs(): void {
		$this->createPlugin(self::BOB);

		$report = new CalendarMultiGetReport();
		$report->hrefs = [
			'/remote.php/dav/calendars/bob/personal/event.ics',
			'/remote.php/dav/calendars/bob/personal/missing.ics',
			'/remote.php/dav/calendars/bob/readonly/x.ics',
			'/outside/base',
		];
		$this->server->emit('report', ['{urn:ietf:params:xml:ns:caldav}calendar-multiget', $report, 'calendars/bob/personal']);
		$this->addToAssertionCount(1);
	}

	public function testMultiGetChecksEachReadableParentOnce(): void {
		$this->createPlugin(self::BOB);

		$report = new CalendarMultiGetReport();
		for ($i = 0; $i < 10; $i++) {
			$report->hrefs[] = '/remote.php/dav/calendars/bob/personal/event' . $i . '.ics';
		}
		$this->server->emit('report', ['{urn:ietf:params:xml:ns:caldav}calendar-multiget', $report, 'calendars/bob/personal']);

		$this->assertSame(['calendars/bob/personal' => 1], $this->nodeExistsCalls);
	}

	public function testFilesAreSkipped(): void {
		$tree = $this->createMock(Tree::class);
		$tree->expects($this->never())->method('nodeExists');
		$tree->expects($this->never())->method('getNodeForPath');
		$this->server = new Server($tree);
		$plugin = $this->createPlugin(self::BOB);

		$plugin->beforeMethod($this->request('GET', 'files/alice/secret.txt'), new Response());
	}
}
