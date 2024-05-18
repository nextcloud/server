<?php
/**
 * @copyright Copyright (c) 2017, Georg Ehrke <oc.list@georgehrke.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\DAV\Tests\DAV;

use OCA\DAV\DAV\CustomPropertiesBackend;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUser;
use Sabre\CalDAV\ICalendar;
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

	/** @var Server | \PHPUnit\Framework\MockObject\MockObject */
	private $server;

	/** @var Tree | \PHPUnit\Framework\MockObject\MockObject */
	private $tree;

	/** @var  IDBConnection */
	private $dbConnection;

	/** @var IUser | \PHPUnit\Framework\MockObject\MockObject */
	private $user;

	/** @var CustomPropertiesBackend | \PHPUnit\Framework\MockObject\MockObject */
	private $backend;

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
		$this->dbConnection = \OC::$server->getDatabaseConnection();

		$this->backend = new CustomPropertiesBackend(
			$this->server,
			$this->tree,
			$this->dbConnection,
			$this->user,
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

	protected function insertProps(string $user, string $path, array $props) {
		foreach ($props as $name => $value) {
			$this->insertProp($user, $path, $name, $value);
		}
	}

	protected function insertProp(string $user, string $path, string $name, mixed $value) {
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

	protected function getProps(string $user, string $path) {
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
				$node = $this->createMock(ICalendar::class);
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

	public function propFindPrincipalScheduleDefaultCalendarProviderUrlProvider(): array {
		// [ user, nodes, existingProps, requestedProps, returnedProps ]
		return [
			[ // Exists
				'dummy_user_42',
				['calendars/dummy_user_42/foo/' => ICalendar::class],
				['{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL' => new Href('calendars/dummy_user_42/foo/')],
				['{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL'],
				['{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL' => new Href('calendars/dummy_user_42/foo/')],
			],
			[ // Doesn't exist
				'dummy_user_42',
				['calendars/dummy_user_42/foo/' => ICalendar::class],
				['{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL' => new Href('calendars/dummy_user_42/bar/')],
				['{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL'],
				[],
			],
			[ // No privilege
				'dummy_user_42',
				['calendars/user2/baz/' => ICalendar::class],
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
				$node = $this->createMock(ICalendar::class);
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

	public function propPatchProvider() {
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

	/**
	 * @dataProvider deleteProvider
	 */
	public function testDelete(string $path): void {
		$this->insertProps('dummy_user_42', $path, ['foo' => 'bar']);
		$this->backend->delete($path);
		$this->assertEquals([], $this->getProps('dummy_user_42', $path));
	}

	public function deleteProvider() {
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

	public function moveProvider() {
		return [
			['foo_bar_path_1337', 'foo_bar_path_7333'],
			[str_repeat('long_path1', 100), str_repeat('long_path2', 100)]
		];
	}
}
