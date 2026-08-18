<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\Calendar;
use OCA\DAV\CardDAV\AddressBook;
use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\Connector\Sabre\DavAclPlugin;
use OCP\IConfig;
use OCP\IL10N;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\INode;
use Sabre\DAV\Server;
use Sabre\DAV\Tree;
use Sabre\DAVACL\Exception\NeedPrivileges;
use Test\TestCase;

class DavAclPluginTest extends TestCase {
	private Tree&MockObject $tree;

	protected function setUp(): void {
		parent::setUp();
		$this->tree = $this->createMock(Tree::class);
	}

	/**
	 * Run every case against a real Calendar and a real AddressBook node: the single ACL
	 * override guards CalDAV and CardDAV alike, and real nodes make get_class() hit both
	 * arms of the type switch.
	 *
	 * @return array<string, array{string, string}> flavour => [flavour, node uri]
	 */
	public static function sharedNodeProvider(): array {
		return [
			'calendar' => ['calendar', 'personal_shared_by_alice'],
			'addressbook' => ['addressbook', 'contacts_shared_by_alice'],
		];
	}

	#[DataProvider('sharedNodeProvider')]
	public function testReadableNodeWithoutWriteReturns403(string $flavour, string $uri): void {
		$this->tree->method('getNodeForPath')
			->willReturn($this->sharedNode($flavour, $uri, 'principals/users/alice'));
		$plugin = $this->pluginFor('principals/users/bob', ['{DAV:}read']);

		$this->expectException(NeedPrivileges::class);
		$plugin->checkPrivileges("calendars/bob/$uri/object", '{DAV:}write');
	}

	#[DataProvider('sharedNodeProvider')]
	public function testUnreadableNodeReturns404(string $flavour, string $uri): void {
		$this->tree->method('getNodeForPath')
			->willReturn($this->sharedNode($flavour, $uri, 'principals/users/alice'));
		$plugin = $this->pluginFor('principals/users/bob', []);

		$type = $flavour === 'calendar' ? 'Calendar' : 'Addressbook';
		$this->expectException(NotFound::class);
		$this->expectExceptionMessage("$type with name '$uri' could not be found");
		$plugin->checkPrivileges("calendars/bob/$uri/object", '{DAV:}write');
	}

	#[DataProvider('sharedNodeProvider')]
	public function testOwnerWithoutPrivilegeReturnsForbidden(string $flavour, string $uri): void {
		$this->tree->method('getNodeForPath')
			->willReturn($this->sharedNode($flavour, $uri, 'principals/users/alice'));
		$plugin = $this->pluginFor('principals/users/alice', []);

		$this->expectException(Forbidden::class);
		$this->expectExceptionMessage('Access denied');
		$plugin->checkPrivileges("calendars/alice/$uri/object", '{DAV:}write');
	}

	private function pluginFor(string $currentPrincipal, array $privilegeSet): DavAclPlugin&MockObject {
		$plugin = $this->getMockBuilder(DavAclPlugin::class)
			->onlyMethods(['getCurrentUserPrincipal', 'getCurrentUserPrivilegeSet'])
			->getMock();
		$plugin->method('getCurrentUserPrincipal')->willReturn($currentPrincipal);
		$plugin->method('getCurrentUserPrivilegeSet')->willReturn($privilegeSet);
		$plugin->initialize(new Server($this->tree));
		return $plugin;
	}

	private function sharedNode(string $flavour, string $uri, string $owner): INode {
		$info = [
			'id' => 1,
			'uri' => $uri,
			'principaluri' => 'principals/users/bob',
			'{DAV:}displayname' => 'Shared',
			'{http://owncloud.org/ns}owner-principal' => $owner,
		];
		if ($flavour === 'calendar') {
			return new Calendar(
				$this->createMock(CalDavBackend::class),
				$info,
				$this->createMock(IL10N::class),
				$this->createMock(IConfig::class),
				$this->createMock(LoggerInterface::class),
			);
		}
		return new AddressBook(
			$this->createMock(CardDavBackend::class),
			$info,
			$this->createMock(IL10N::class),
		);
	}
}
