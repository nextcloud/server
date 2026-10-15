<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OC\Authentication\Token\PublicKeyToken;
use OC\Authentication\Token\TokenScopes as CoreTokenScopes;
use OC\Lockdown\LockdownManager;
use OCA\DAV\Connector\Sabre\TokenScopePlugin;
use OCA\DAV\TokenScopes;
use OCA\Files\TokenScopes as FilesTokenScopes;
use OCP\ISession;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Server;
use Sabre\DAV\SimpleCollection;
use Sabre\HTTP\Request;
use Test\TestCase;

class TokenScopePluginTest extends TestCase {
	private const FILES = [FilesTokenScopes::FILES_READ, FilesTokenScopes::FILES_WRITE];
	private const CALENDAR = [TokenScopes::CALENDAR_READ, TokenScopes::CALENDAR_WRITE];
	private const CALENDAR_READ = [TokenScopes::CALENDAR_READ];
	private const CONTACTS = [TokenScopes::CONTACTS_READ, TokenScopes::CONTACTS_WRITE];
	private const CONTACTS_READ = [TokenScopes::CONTACTS_READ];

	/**
	 * @param list<string>|null $scopes null for a token without scopes
	 */
	private function plugin(?array $scopes, string $prefix = ''): TokenScopePlugin {
		$lockdownManager = new LockdownManager(fn () => $this->createMock(ISession::class), new CoreTokenScopes());
		$token = new PublicKeyToken();
		$token->setScope($scopes === null ? [] : [CoreTokenScopes::KEY => $scopes]);
		$lockdownManager->setToken($token);
		return new TokenScopePlugin($lockdownManager, $prefix);
	}

	public static function dataAllows(): array {
		return [
			'root, any key' => ['', 'PROPFIND', [], true],
			'root write' => ['', 'PROPPATCH', self::FILES, false],
			'files' => ['files/user0/a.txt', 'GET', self::FILES, true],
			'files write, mask guards it' => ['files/user0/a.txt', 'PUT', self::FILES, true],
			'files without files' => ['files/user0', 'PROPFIND', self::CALENDAR, false],
			'uploads' => ['uploads/user0/1', 'MKCOL', self::FILES, true],
			'bulk upload' => ['bulk', 'POST', self::FILES, true],
			'bulk upload without files' => ['bulk', 'POST', self::CALENDAR, false],
			'groupfolder trash without files' => ['trashbin/user0/trash', 'PROPFIND', self::CALENDAR, false],
			'groupfolders' => ['groupfolders/user0', 'PROPFIND', self::FILES, true],
			'versions' => ['versions/user0/versions/1', 'PROPFIND', self::FILES, true],
			'tag definitions read' => ['systemtags/1', 'PROPFIND', self::FILES, true],
			'tag definitions write' => ['systemtags/1', 'PROPPATCH', self::FILES, true],
			'tag definitions without files' => ['systemtags/1', 'PROPFIND', self::CALENDAR, false],
			'tag assignment' => ['systemtags-relations/files/1/2', 'PUT', self::FILES, true],
			'assigned tags' => ['systemtags-assigned/files', 'PROPFIND', self::FILES, true],
			'comments' => ['comments/files/1', 'PROPFIND', self::FILES, false],
			'calendar read' => ['calendars/user0/personal', 'REPORT', self::CALENDAR_READ, true],
			'calendar write without write' => ['calendars/user0/personal/a.ics', 'PUT', self::CALENDAR_READ, false],
			'calendar write' => ['calendars/user0/personal/a.ics', 'PUT', self::CALENDAR, true],
			'calendar without calendar' => ['calendars/user0/personal', 'PROPFIND', self::FILES, false],
			'calendar sharing' => ['calendars/user0/personal', 'POST', self::CALENDAR, false],
			'scheduling outbox' => ['calendars/user0/outbox', 'POST', self::CALENDAR, true],
			'scheduling outbox, doubled slashes' => ['calendars//user0//outbox', 'POST', self::CALENDAR, true],
			'scheduling outbox without write' => ['calendars/user0/outbox', 'POST', self::CALENDAR_READ, false],
			'room calendars' => ['system-calendars/calendar-rooms/room1', 'REPORT', self::CALENDAR, true],
			'address book read' => ['addressbooks/users/user0/contacts', 'PROPFIND', self::CONTACTS_READ, true],
			'address book write without write' => ['addressbooks/users/user0/contacts/a.vcf', 'PUT', self::CONTACTS_READ, false],
			'address book write' => ['addressbooks/users/user0/contacts/a.vcf', 'PUT', self::CONTACTS, true],
			'address book sharing' => ['addressbooks/users/user0/contacts', 'POST', self::CONTACTS, false],
			'principals without groupware' => ['principals/users/user0', 'PROPFIND', self::FILES, false],
			'principals with contacts' => ['principals/users/user0', 'PROPFIND', self::CONTACTS_READ, true],
			'principals with calendar' => ['principals/users/user0', 'PROPFIND', self::CALENDAR_READ, true],
			'default calendar with contacts' => ['principals/users/user0', 'PROPPATCH', self::CONTACTS, false],
			'default calendar with calendar' => ['principals/users/user0', 'PROPPATCH', self::CALENDAR, true],
			'proxy write' => ['principals/users/user0/calendar-proxy-write', 'PROPPATCH', self::CALENDAR, false],
			'proxy read' => ['principals/users/user0/calendar-proxy-read/', 'PROPPATCH', self::CALENDAR, false],
			'proxy listing' => ['principals/users/user0/calendar-proxy-write', 'PROPFIND', self::CALENDAR_READ, true],
			'avatar' => ['avatars/user0/96.png', 'GET', [], true],
			'avatar listing' => ['avatars/user0', 'PROPFIND', [], false],
			'provisioning' => ['provisioning/apple-provisioning.mobileconfig', 'GET', self::CALENDAR, false],
			'public calendars' => ['public-calendars/abc', 'GET', self::CALENDAR, false],
			'remote calendars' => ['remote-calendars/abc', 'PROPFIND', self::CALENDAR, false],
			'app collection' => ['photos/user0/albums', 'PROPFIND', self::FILES, false],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataAllows')]
	public function testAllows(string $path, string $method, array $scopes, bool $expected): void {
		$this->assertSame($expected, $this->plugin($scopes)->allows($path, $method));
	}

	public function testLegacyWebdavIsFiles(): void {
		$this->assertTrue($this->plugin(self::FILES, 'files/')->allows('Documents', 'PROPFIND'));
		$this->assertFalse($this->plugin(self::CALENDAR, 'files/')->allows('Documents', 'PROPFIND'));
	}

	private function initialize(TokenScopePlugin $plugin): Server {
		$server = new Server(new SimpleCollection('root', [
			new SimpleCollection('calendars', [new SimpleCollection('user0', [new SimpleCollection('personal')])]),
			new SimpleCollection('comments', [new SimpleCollection('files')]),
		]));
		$server->setBaseUri('/');
		$plugin->initialize($server);
		return $server;
	}

	public function testRefusesRequest(): void {
		$server = $this->initialize($this->plugin(self::FILES));

		$this->expectException(Forbidden::class);
		$server->emit('beforeMethod:PROPFIND', [new Request('PROPFIND', '/calendars/user0/personal'), $server->httpResponse]);
	}

	public function testRefusesEncodedSegment(): void {
		$server = $this->initialize($this->plugin(self::FILES));

		$this->expectException(Forbidden::class);
		$server->emit('beforeMethod:GET', [new Request('GET', '/%63alendars/user0/personal'), $server->httpResponse]);
	}

	public function testRefusesCopyIntoCalendar(): void {
		$server = $this->initialize($this->plugin(self::FILES));
		$request = new Request('COPY', '/files/user0/a.ics', ['Destination' => '/calendars/user0/personal/a.ics']);

		$this->expectException(Forbidden::class);
		$server->emit('beforeMethod:COPY', [$request, $server->httpResponse]);
	}

	public function testCopyOnlyReadsItsSource(): void {
		$server = $this->initialize($this->plugin([...self::CALENDAR_READ, ...self::FILES]));
		$request = new Request('COPY', '/calendars/user0/personal/a.ics', ['Destination' => '/files/user0/a.ics']);

		$this->assertTrue($server->emit('beforeMethod:COPY', [$request, $server->httpResponse]));
	}

	public function testHidesNodesInMultistatus(): void {
		$server = $this->initialize($this->plugin(self::CALENDAR));
		$server->emit('beforeMethod:REPORT', [new Request('REPORT', '/calendars/user0/personal'), $server->httpResponse]);

		$result = $server->getPropertiesForMultiplePaths(['calendars/user0/personal', 'comments/files'], ['{DAV:}resourcetype']);

		$this->assertSame(['calendars/user0/personal'], array_keys($result));
	}

	public function testUnscopedTokenPasses(): void {
		$server = $this->initialize($this->plugin(null));

		$this->assertTrue($server->emit('beforeMethod:PROPFIND', [new Request('PROPFIND', '/comments/files'), $server->httpResponse]));
		$result = $server->getPropertiesForMultiplePaths(['calendars/user0/personal', 'comments/files'], ['{DAV:}resourcetype']);
		$this->assertCount(2, $result);
	}
}
