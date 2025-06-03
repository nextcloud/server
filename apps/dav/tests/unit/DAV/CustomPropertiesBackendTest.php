<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\DAV;

use OCA\DAV\CalDAV\Calendar;
use OCA\DAV\CalDAV\DefaultCalendarValidator;
use OCA\DAV\DAV\CustomPropertiesBackend;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\PropFind;
use Sabre\DAV\PropPatch;
use Sabre\DAV\Server;
use Sabre\DAV\Tree;
use Sabre\DAV\Xml\Property\Href;
use Sabre\DAVACL\IACL;
use Sabre\DAVACL\IPrincipal;
use Test\TestCase;

/**
 * @group DB
 */
class CustomPropertiesBackendTest extends TestCase {
	private const BASE_URI = '/remote.php/dav/';

	private Server&MockObject $server;
	private Tree&MockObject $tree;
	private IDBConnection $dbConnection;
	private IUser&MockObject $user;
	private DefaultCalendarValidator&MockObject $defaultCalendarValidator;
	private CustomPropertiesBackend $backend;

	protected function setUp(): void {
		parent::setUp();

		$this->server = $this->createMock(Server::class);
		$this->server->method('getBaseUri')
			->willReturn(self::BASE_URI);
		$this->tree = $this->createMock(Tree::class);
		$this->user = $this->createMock(IUser::class);
		$this->user->method('getUID')
			->with()
			->willReturn('dummy_user_42');
		$this->dbConnection = \OCP\Server::get(IDBConnection::class);
		$this->defaultCalendarValidator = $this->createMock(DefaultCalendarValidator::class);

		$this->backend = new CustomPropertiesBackend(
			$this->server,
			$this->tree,
			$this->dbConnection,
			$this->user,
			$this->defaultCalendarValidator,
		);
	}

	protected function tearDown(): void {
		$query = $this->dbConnection->getQueryBuilder();
		$query->delete('properties');
		$query->execute();

		parent::tearDown();
	}

	private function formatPath(string $path): string {
		if (strlen($path) > 250) {
			return sha1($path);
		} else {
			return $path;
		}
	}

	protected function insertProps(string $user, string $path, array $props): void {
		foreach ($props as $name => $value) {
			$this->insertProp($user, $path, $name, $value);
		}
	}

	protected function insertProp(string $user, string $path, string $name, mixed $value): void {
		$type = CustomPropertiesBackend::PROPERTY_TYPE_STRING;
		if ($value instanceof Href) {
			$value = $value->getHref();
			$type = CustomPropertiesBackend::PROPERTY_TYPE_HREF;
		}

		$query = $this->dbConnection->getQueryBuilder();
		$query->insert('properties')
			->values([
				'userid' => $query->createNamedParameter($user),
				'propertypath' => $query->createNamedParameter($this->formatPath($path)),
				'propertyname' => $query->createNamedParameter($name),
				'propertyvalue' => $query->createNamedParameter($value),
				'valuetype' => $query->createNamedParameter($type, IQueryBuilder::PARAM_INT)
			]);
		$query->execute();
	}

	protected function getProps(string $user, string $path): array {
		$query = $this->dbConnection->getQueryBuilder();
		$query->select('propertyname', 'propertyvalue', 'valuetype')
			->from('properties')
			->where($query->expr()->eq('userid', $query->createNamedParameter($user)))
			->andWhere($query->expr()->eq('propertypath', $query->createNamedParameter($this->formatPath($path))));

		$result = $query->execute();
		$data = [];
		while ($row = $result->fetch()) {
			$value = $row['propertyvalue'];
			if ((int)$row['valuetype'] === CustomPropertiesBackend::PROPERTY_TYPE_HREF) {
				$value = new Href($value);
			}
			$data[$row['propertyname']] = $value;
		}
		$result->closeCursor();

		return $data;
	}

	public function testPropFindNoDbCalls(): void {
		$db = $this->createMock(IDBConnection::class);
		$backend = new CustomPropertiesBackend(
			$this->server,
			$this->tree,
			$db,
			$this->user,
			$this->defaultCalendarValidator,
		);

		$propFind = $this->createMock(PropFind::class);
		$propFind->expects($this->once())
			->method('get404Properties')
			->with()
			->willReturn([
				'{http://owncloud.org/ns}permissions',
				'{http://owncloud.org/ns}downloadURL',
				'{http://owncloud.org/ns}dDC',
				'{http://owncloud.org/ns}size',
			]);

		$db->expects($this->never())
			->method($this->anything());

		$backend->propFind('foo_bar_path_1337_0', $propFind);
	}

	public function testPropFindCalendarCall(): void {
		$propFind = $this->createMock(PropFind::class);
		$propFind->method('get404Properties')
			->with()
			->willReturn([
				'{DAV:}getcontentlength',
				'{DAV:}getcontenttype',
				'{DAV:}getetag',
				'{abc}def',
			]);

		$propFind->method('getRequestedProperties')
			->with()
			->willReturn([
				'{DAV:}getcontentlength',
				'{DAV:}getcontenttype',
				'{DAV:}getetag',
				'{DAV:}displayname',
				'{urn:ietf:params:xml:ns:caldav}calendar-description',
				'{urn:ietf:params:xml:ns:caldav}calendar-timezone',
				'{abc}def',
			]);

		$props = [
			'{abc}def' => 'a',
			'{DAV:}displayname' => 'b',
			'{urn:ietf:params:xml:ns:caldav}calendar-description' => 'c',
			'{urn:ietf:params:xml:ns:caldav}calendar-timezone' => 'd',
		];

		$this->insertProps('dummy_user_42', 'calendars/foo/bar_path_1337_0', $props);

		$setProps = [];
		$propFind->method('set')
			->willReturnCallback(function ($name, $value, $status) use (&$setProps): void {
				$setProps[$name] = $value;
			});

		$this->backend->propFind('calendars/foo/bar_path_1337_0', $propFind);
		$this->assertEquals($props, $setProps);
	}

	public function testPropFindPrincipalCall(): void {
		$this->tree->method('getNodeForPath')
			->willReturnCallback(function ($uri) {
				$node = $this->createMock(Calendar::class);
				$node->method('getOwner')
					->willReturn('principals/users/dummy_user_42');
				return $node;
			});

		$propFind = $this->createMock(PropFind::class);
		$propFind->method('get404Properties')
			->with()
			->willReturn([
				'{DAV:}getcontentlength',
				'{DAV:}getcontenttype',
				'{DAV:}getetag',
				'{abc}def',
			]);

		$propFind->method('getRequestedProperties')
			->with()
			->willReturn([
				'{DAV:}getcontentlength',
				'{DAV:}getcontenttype',
				'{DAV:}getetag',
				'{abc}def',
				'{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL',
			]);

		$props = [
			'{abc}def' => 'a',
			'{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL' => new Href('calendars/admin/personal'),
		];
		$this->insertProps('dummy_user_42', 'principals/users/dummy_user_42', $props);

		$setProps = [];
		$propFind->method('set')
			->willReturnCallback(function ($name, $value, $status) use (&$setProps): void {
				$setProps[$name] = $value;
			});

		$this->backend->propFind('principals/users/dummy_user_42', $propFind);
		$this->assertEquals($props, $setProps);
	}

	public static function propFindPrincipalScheduleDefaultCalendarProviderUrlProvider(): array {
		// [ user, nodes, existingProps, requestedProps, returnedProps ]
		return [
			[ // Exists
				'dummy_user_42',
				['calendars/dummy_user_42/foo/' => Calendar::class],
				['{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL' => new Href('calendars/dummy_user_42/foo/')],
				['{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL'],
				['{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL' => new Href('calendars/dummy_user_42/foo/')],
			],
			[ // Doesn't exist
				'dummy_user_42',
				['calendars/dummy_user_42/foo/' => Calendar::class],
				['{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL' => new Href('calendars/dummy_user_42/bar/')],
				['{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL'],
				[],
			],
			[ // No privilege
				'dummy_user_42',
				['calendars/user2/baz/' => Calendar::class],
				['{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL' => new Href('calendars/user2/baz/')],
				['{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL'],
				[],
			],
			[ // Not a calendar
				'dummy_user_42',
				['foo/dummy_user_42/bar/' => IACL::class],
				['{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL' => new Href('foo/dummy_user_42/bar/')],
				['{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL'],
				[],
			],
		];

	}

	/**
	 * @dataProvider propFindPrincipalScheduleDefaultCalendarProviderUrlProvider
	 */
	public function testPropFindPrincipalScheduleDefaultCalendarUrl(
		string $user,
		array $nodes,
		array $existingProps,
		array $requestedProps,
		array $returnedProps,
	): void {
		$propFind = $this->createMock(PropFind::class);
		$propFind->method('get404Properties')
			->with()
			->willReturn([
				'{DAV:}getcontentlength',
				'{DAV:}getcontenttype',
				'{DAV:}getetag',
			]);

		$propFind->method('getRequestedProperties')
			->with()
			->willReturn(array_merge([
				'{DAV:}getcontentlength',
				'{DAV:}getcontenttype',
				'{DAV:}getetag',
				'{abc}def',
			],
				$requestedProps,
			));

		$this->server->method('calculateUri')
			->willReturnCallback(function ($uri) {
				if (!str_starts_with($uri, self::BASE_URI)) {
					return trim(substr($uri, strlen(self::BASE_URI)), '/');
				}
				return null;
			});
		$this->tree->method('getNodeForPath')
			->willReturnCallback(function ($uri) use ($nodes) {
				if (str_starts_with($uri, 'principals/')) {
					return $this->createMock(IPrincipal::class);
				}
				if (array_key_exists($uri, $nodes)) {
					$owner = explode('/', $uri)[1];
					$node = $this->createMock($nodes[$uri]);
					$node->method('getOwner')
						->willReturn("principals/users/$owner");
					return $node;
				}
				throw new NotFound('Node not found');
			});

		$this->insertProps($user, "principals/users/$user", $existingProps);

		$setProps = [];
		$propFind->method('set')
			->willReturnCallback(function ($name, $value, $status) use (&$setProps): void {
				$setProps[$name] = $value;
			});

		$this->backend->propFind("principals/users/$user", $propFind);
		$this->assertEquals($returnedProps, $setProps);
	}

	/**
	 * @dataProvider propPatchProvider
	 */
	public function testPropPatch(string $path, array $existing, array $props, array $result): void {
		$this->server->method('calculateUri')
			->willReturnCallback(function ($uri) {
				if (str_starts_with($uri, self::BASE_URI)) {
					return trim(substr($uri, strlen(self::BASE_URI)), '/');
				}
				return null;
			});
		$this->tree->method('getNodeForPath')
			->willReturnCallback(function ($uri) {
				$node = $this->createMock(Calendar::class);
				$node->method('getOwner')
					->willReturn('principals/users/' . $this->user->getUID());
				return $node;
			});

		$this->insertProps($this->user->getUID(), $path, $existing);
		$propPatch = new PropPatch($props);

		$this->backend->propPatch($path, $propPatch);
		$propPatch->commit();

		$storedProps = $this->getProps($this->user->getUID(), $path);
		$this->assertEquals($result, $storedProps);
	}

	public static function propPatchProvider(): array {
		$longPath = str_repeat('long_path', 100);
		return [
			['foo_bar_path_1337', [], ['{DAV:}displayname' => 'anything'], ['{DAV:}displayname' => 'anything']],
			['foo_bar_path_1337', ['{DAV:}displayname' => 'foo'], ['{DAV:}displayname' => 'anything'], ['{DAV:}displayname' => 'anything']],
			['foo_bar_path_1337', ['{DAV:}displayname' => 'foo'], ['{DAV:}displayname' => null], []],
			[$longPath, [], ['{DAV:}displayname' => 'anything'], ['{DAV:}displayname' => 'anything']],
			['principals/users/dummy_user_42', [], ['{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL' => new Href('foo/bar/')], ['{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL' => new Href('foo/bar/')]],
			['principals/users/dummy_user_42', [], ['{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL' => new Href(self::BASE_URI . 'foo/bar/')], ['{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL' => new Href('foo/bar/')]],
		];
	}

	public function testPropPatchWithUnsuitableCalendar(): void {
		$path = 'principals/users/' . $this->user->getUID();

		$node = $this->createMock(Calendar::class);
		$node->expects(self::once())
			->method('getOwner')
			->willReturn($path);

		$this->defaultCalendarValidator->expects(self::once())
			->method('validateScheduleDefaultCalendar')
			->with($node)
			->willThrowException(new \Sabre\DAV\Exception('Invalid calendar'));

		$this->server->method('calculateUri')
			->willReturnCallback(function ($uri) {
				if (str_starts_with($uri, self::BASE_URI)) {
					return trim(substr($uri, strlen(self::BASE_URI)), '/');
				}
				return null;
			});
		$this->tree->expects(self::once())
			->method('getNodeForPath')
			->with('foo/bar/')
			->willReturn($node);

		$storedProps = $this->getProps($this->user->getUID(), $path);
		$this->assertEquals([], $storedProps);

		$propPatch = new PropPatch([
			'{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL' => new Href('foo/bar/'),
		]);
		$this->backend->propPatch($path, $propPatch);
		try {
			$propPatch->commit();
		} catch (\Throwable $e) {
			$this->assertInstanceOf(\Sabre\DAV\Exception::class, $e);
		}

		$storedProps = $this->getProps($this->user->getUID(), $path);
		$this->assertEquals([], $storedProps);
	}

	/**
	 * @dataProvider deleteProvider
	 */
	public function testDelete(string $path): void {
		$this->insertProps('dummy_user_42', $path, ['foo' => 'bar']);
		$this->backend->delete($path);
		$this->assertEquals([], $this->getProps('dummy_user_42', $path));
	}

	public static function deleteProvider(): array {
		return [
			['foo_bar_path_1337'],
			[str_repeat('long_path', 100)]
		];
	}

	/**
	 * @dataProvider moveProvider
	 */
	public function testMove(string $source, string $target): void {
		$this->insertProps('dummy_user_42', $source, ['foo' => 'bar']);
		$this->backend->move($source, $target);
		$this->assertEquals([], $this->getProps('dummy_user_42', $source));
		$this->assertEquals(['foo' => 'bar'], $this->getProps('dummy_user_42', $target));
	}

	public static function moveProvider(): array {
		return [
			['foo_bar_path_1337', 'foo_bar_path_7333'],
			[str_repeat('long_path1', 100), str_repeat('long_path2', 100)]
		];
	}

	public function testDecodeValueFromDatabaseObjectCurrent(): void {
		$propertyValue = 'O:48:"Sabre\CalDAV\Xml\Property\ScheduleCalendarTransp":1:{s:8:"\x00*\x00value";s:6:"opaque";}';
		$propertyType = 3;
		$decodeValue = $this->invokePrivate($this->backend, 'decodeValueFromDatabase', [$propertyValue, $propertyType]);
		$this->assertInstanceOf(\Sabre\CalDAV\Xml\Property\ScheduleCalendarTransp::class, $decodeValue);
		$this->assertEquals('opaque', $decodeValue->getValue());
	}

	public function testDecodeValueFromDatabaseObjectLegacy(): void {
		$propertyValue = 'O:48:"Sabre\CalDAV\Xml\Property\ScheduleCalendarTransp":1:{s:8:"' . chr(0) . '*' . chr(0) . 'value";s:6:"opaque";}';
		$propertyType = 3;
		$decodeValue = $this->invokePrivate($this->backend, 'decodeValueFromDatabase', [$propertyValue, $propertyType]);
		$this->assertInstanceOf(\Sabre\CalDAV\Xml\Property\ScheduleCalendarTransp::class, $decodeValue);
		$this->assertEquals('opaque', $decodeValue->getValue());
	}
}
