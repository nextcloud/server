<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV\ResourceBooking;

use OCA\DAV\CalDAV\Proxy\Proxy;
use OCA\DAV\CalDAV\Proxy\ProxyMapper;
use OCA\DAV\CalDAV\ResourceBooking\ResourcePrincipalBackend;
use OCA\DAV\CalDAV\ResourceBooking\RoomPrincipalBackend;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Sabre\DAV\PropPatch;
use Test\TestCase;

abstract class AbstractPrincipalBackendTest extends TestCase {
	/** @var ResourcePrincipalBackend|RoomPrincipalBackend */
	protected $principalBackend;

	/** @var IUserSession|\PHPUnit\Framework\MockObject\MockObject */
	protected $userSession;

	/** @var IGroupManager|\PHPUnit\Framework\MockObject\MockObject */
	protected $groupManager;

	/** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
	protected $logger;

	/** @var ProxyMapper|\PHPUnit\Framework\MockObject\MockObject */
	protected $proxyMapper;

	/** @var string */
	protected $mainDbTable;

	/** @var string */
	protected $metadataDbTable;

	/** @var string */
	protected $foreignKey;

	/** @var string */
	protected $principalPrefix;

	/** @var string */
	protected $expectedCUType;

	protected function setUp(): void {
		parent::setUp();

		$this->userSession = $this->createMock(IUserSession::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->proxyMapper = $this->createMock(ProxyMapper::class);
	}

	protected function tearDown(): void {
		$query = self::$realDatabase->getQueryBuilder();

		$query->delete('calendar_resources')->execute();
		$query->delete('calendar_resources_md')->execute();
		$query->delete('calendar_rooms')->execute();
		$query->delete('calendar_rooms_md')->execute();
	}

	public function testGetPrincipalsByPrefix(): void {
		$actual = $this->principalBackend->getPrincipalsByPrefix($this->principalPrefix);

		$this->assertEquals([
			[
				'uri' => $this->principalPrefix . '/backend1-res1',
				'{DAV:}displayname' => 'Beamer1',
				'{http://sabredav.org/ns}email-address' => 'res1@foo.bar',
				'{urn:ietf:params:xml:ns:caldav}calendar-user-type' => $this->expectedCUType,
			],
			[
				'uri' => $this->principalPrefix . '/backend1-res2',
				'{DAV:}displayname' => 'TV1',
				'{http://sabredav.org/ns}email-address' => 'res2@foo.bar',
				'{urn:ietf:params:xml:ns:caldav}calendar-user-type' => $this->expectedCUType,
			],
			[
				'uri' => $this->principalPrefix . '/backend2-res3',
				'{DAV:}displayname' => 'Beamer2',
				'{http://sabredav.org/ns}email-address' => 'res3@foo.bar',
				'{urn:ietf:params:xml:ns:caldav}calendar-user-type' => $this->expectedCUType,
				'{http://nextcloud.com/ns}foo' => 'value1',
				'{http://nextcloud.com/ns}meta2' => 'value2',
			],
			[
				'uri' => $this->principalPrefix . '/backend2-res4',
				'{DAV:}displayname' => 'TV2',
				'{http://sabredav.org/ns}email-address' => 'res4@foo.bar',
				'{urn:ietf:params:xml:ns:caldav}calendar-user-type' => $this->expectedCUType,
				'{http://nextcloud.com/ns}meta1' => 'value1',
				'{http://nextcloud.com/ns}meta3' => 'value3-old',
			],
			[
				'uri' => $this->principalPrefix . '/backend3-res5',
				'{DAV:}displayname' => 'Beamer3',
				'{http://sabredav.org/ns}email-address' => 'res5@foo.bar',
				'{urn:ietf:params:xml:ns:caldav}calendar-user-type' => $this->expectedCUType,
			],
			[
				'uri' => $this->principalPrefix . '/backend3-res6',
				'{DAV:}displayname' => 'Pointer',
				'{http://sabredav.org/ns}email-address' => 'res6@foo.bar',
				'{urn:ietf:params:xml:ns:caldav}calendar-user-type' => $this->expectedCUType,
				'{http://nextcloud.com/ns}meta99' => 'value99'
			]
		], $actual);
	}

	public function testGetNoPrincipalsByPrefixForWrongPrincipalPrefix(): void {
		$actual = $this->principalBackend->getPrincipalsByPrefix('principals/users');
		$this->assertEquals([], $actual);
	}

	public function testGetPrincipalByPath(): void {
		$actual = $this->principalBackend->getPrincipalByPath($this->principalPrefix . '/backend2-res3');
		$this->assertEquals([
			'uri' => $this->principalPrefix . '/backend2-res3',
			'{DAV:}displayname' => 'Beamer2',
			'{http://sabredav.org/ns}email-address' => 'res3@foo.bar',
			'{urn:ietf:params:xml:ns:caldav}calendar-user-type' => $this->expectedCUType,
			'{http://nextcloud.com/ns}foo' => 'value1',
			'{http://nextcloud.com/ns}meta2' => 'value2',
		], $actual);
	}

	public function testGetPrincipalByPathNotFound(): void {
		$actual = $this->principalBackend->getPrincipalByPath($this->principalPrefix . '/db-123');
		$this->assertEquals(null, $actual);
	}

	public function testGetPrincipalByPathWrongPrefix(): void {
		$actual = $this->principalBackend->getPrincipalByPath('principals/users/foo-bar');
		$this->assertEquals(null, $actual);
	}

	public function testGetGroupMemberSet(): void {
		$actual = $this->principalBackend->getGroupMemberSet($this->principalPrefix . '/backend1-res1');
		$this->assertEquals([], $actual);
	}

	public function testGetGroupMemberSetProxyRead(): void {
		$proxy1 = new Proxy();
		$proxy1->setProxyId('proxyId1');
		$proxy1->setPermissions(1);

		$proxy2 = new Proxy();
		$proxy2->setProxyId('proxyId2');
		$proxy2->setPermissions(3);

		$proxy3 = new Proxy();
		$proxy3->setProxyId('proxyId3');
		$proxy3->setPermissions(3);

		$this->proxyMapper->expects($this->once())
			->method('getProxiesOf')
			->with($this->principalPrefix . '/backend1-res1')
			->willReturn([$proxy1, $proxy2, $proxy3]);

		$actual = $this->principalBackend->getGroupMemberSet($this->principalPrefix . '/backend1-res1/calendar-proxy-read');
		$this->assertEquals(['proxyId1'], $actual);
	}

	public function testGetGroupMemberSetProxyWrite(): void {
		$proxy1 = new Proxy();
		$proxy1->setProxyId('proxyId1');
		$proxy1->setPermissions(1);

		$proxy2 = new Proxy();
		$proxy2->setProxyId('proxyId2');
		$proxy2->setPermissions(3);

		$proxy3 = new Proxy();
		$proxy3->setProxyId('proxyId3');
		$proxy3->setPermissions(3);

		$this->proxyMapper->expects($this->once())
			->method('getProxiesOf')
			->with($this->principalPrefix . '/backend1-res1')
			->willReturn([$proxy1, $proxy2, $proxy3]);

		$actual = $this->principalBackend->getGroupMemberSet($this->principalPrefix . '/backend1-res1/calendar-proxy-write');
		$this->assertEquals(['proxyId2', 'proxyId3'], $actual);
	}

	public function testGetGroupMembership(): void {
		$proxy1 = new Proxy();
		$proxy1->setOwnerId('proxyId1');
		$proxy1->setPermissions(1);

		$proxy2 = new Proxy();
		$proxy2->setOwnerId('proxyId2');
		$proxy2->setPermissions(3);

		$this->proxyMapper->expects($this->once())
			->method('getProxiesFor')
			->with($this->principalPrefix . '/backend1-res1')
			->willReturn([$proxy1, $proxy2]);

		$actual = $this->principalBackend->getGroupMembership($this->principalPrefix . '/backend1-res1');

		$this->assertEquals(['proxyId1/calendar-proxy-read', 'proxyId2/calendar-proxy-write'], $actual);
	}

	public function testSetGroupMemberSet(): void {
		$this->proxyMapper->expects($this->once())
			->method('getProxiesOf')
			->with($this->principalPrefix . '/backend1-res1')
			->willReturn([]);

		$this->proxyMapper->expects($this->exactly(2))
			->method('insert')
			->withConsecutive(
				[$this->callback(function ($proxy) {
					/** @var Proxy $proxy */
					if ($proxy->getOwnerId() !== $this->principalPrefix . '/backend1-res1') {
						return false;
					}
					if ($proxy->getProxyId() !== $this->principalPrefix . '/backend1-res2') {
						return false;
					}
					if ($proxy->getPermissions() !== 3) {
						return false;
					}

					return true;
				})],
				[$this->callback(function ($proxy) {
					/** @var Proxy $proxy */
					if ($proxy->getOwnerId() !== $this->principalPrefix . '/backend1-res1') {
						return false;
					}
					if ($proxy->getProxyId() !== $this->principalPrefix . '/backend2-res3') {
						return false;
					}
					if ($proxy->getPermissions() !== 3) {
						return false;
					}

					return true;
				})],
			);

		$this->principalBackend->setGroupMemberSet($this->principalPrefix . '/backend1-res1/calendar-proxy-write', [$this->principalPrefix . '/backend1-res2', $this->principalPrefix . '/backend2-res3']);
	}

	public function testUpdatePrincipal(): void {
		$propPatch = $this->createMock(PropPatch::class);
		$actual = $this->principalBackend->updatePrincipal($this->principalPrefix . '/foo-bar', $propPatch);

		$this->assertEquals(0, $actual);
	}

	/**
	 * @dataProvider dataSearchPrincipals
	 */
	public function testSearchPrincipals($expected, $test): void {
		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->once())
			->method('getUser')
			->with()
			->willReturn($user);
		$this->groupManager->expects($this->once())
			->method('getUserGroupIds')
			->with($user)
			->willReturn(['group1', 'group2']);

		$actual = $this->principalBackend->searchPrincipals($this->principalPrefix, [
			'{http://sabredav.org/ns}email-address' => 'foo',
			'{DAV:}displayname' => 'Beamer',
		], $test);

		$this->assertEquals(
			str_replace('%prefix%', $this->principalPrefix, $expected),
			$actual);
	}

	public function dataSearchPrincipals() {
		// data providers are called before we subclass
		// this class, $this->principalPrefix is null
		// at that point, so we need this hack
		return [
			[[
				'%prefix%/backend1-res1',
				'%prefix%/backend2-res3',
			], 'allof'],
			[[
				'%prefix%/backend1-res1',
				'%prefix%/backend1-res2',
				'%prefix%/backend2-res3',
				'%prefix%/backend2-res4',
				'%prefix%/backend3-res6',
			], 'anyof'],
		];
	}

	public function testSearchPrincipalsByMetadataKey(): void {
		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->once())
			->method('getUser')
			->with()
			->willReturn($user);
		$this->groupManager->expects($this->once())
			->method('getUserGroupIds')
			->with($user)
			->willReturn(['group1', 'group2']);

		$actual = $this->principalBackend->searchPrincipals($this->principalPrefix, [
			'{http://nextcloud.com/ns}meta3' => 'value',
		]);

		$this->assertEquals([
			$this->principalPrefix . '/backend2-res4',
		], $actual);
	}

	public function testSearchPrincipalsByCalendarUserAddressSet(): void {
		$user = $this->createMock(IUser::class);
		$this->userSession->method('getUser')
			->with()
			->willReturn($user);
		$this->groupManager->method('getUserGroupIds')
			->with($user)
			->willReturn(['group1', 'group2']);

		$actual = $this->principalBackend->searchPrincipals($this->principalPrefix, [
			'{urn:ietf:params:xml:ns:caldav}calendar-user-address-set' => 'res2@foo.bar',
		]);

		$this->assertEquals(
			str_replace('%prefix%', $this->principalPrefix, [
				'%prefix%/backend1-res2',
			]),
			$actual);
	}

	public function testSearchPrincipalsEmptySearchProperties(): void {
		$this->userSession->expects($this->never())
			->method('getUser');
		$this->groupManager->expects($this->never())
			->method('getUserGroupIds');

		$this->principalBackend->searchPrincipals($this->principalPrefix, []);
	}

	public function testSearchPrincipalsWrongPrincipalPrefix(): void {
		$this->userSession->expects($this->never())
			->method('getUser');
		$this->groupManager->expects($this->never())
			->method('getUserGroupIds');

		$this->principalBackend->searchPrincipals('principals/users', [
			'{http://sabredav.org/ns}email-address' => 'foo'
		]);
	}

	public function testFindByUriByEmail(): void {
		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->once())
			->method('getUser')
			->with()
			->willReturn($user);
		$this->groupManager->expects($this->once())
			->method('getUserGroupIds')
			->with($user)
			->willReturn(['group1', 'group2']);

		$actual = $this->principalBackend->findByUri('mailto:res1@foo.bar', $this->principalPrefix);
		$this->assertEquals($this->principalPrefix . '/backend1-res1', $actual);
	}

	public function testFindByUriByEmailForbiddenResource(): void {
		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->once())
			->method('getUser')
			->with()
			->willReturn($user);
		$this->groupManager->expects($this->once())
			->method('getUserGroupIds')
			->with($user)
			->willReturn(['group1', 'group2']);

		$actual = $this->principalBackend->findByUri('mailto:res5@foo.bar', $this->principalPrefix);
		$this->assertEquals(null, $actual);
	}

	public function testFindByUriByEmailNotFound(): void {
		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->once())
			->method('getUser')
			->with()
			->willReturn($user);
		$this->groupManager->expects($this->once())
			->method('getUserGroupIds')
			->with($user)
			->willReturn(['group1', 'group2']);

		$actual = $this->principalBackend->findByUri('mailto:res99@foo.bar', $this->principalPrefix);
		$this->assertEquals(null, $actual);
	}

	public function testFindByUriByPrincipal(): void {
		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->once())
			->method('getUser')
			->with()
			->willReturn($user);
		$this->groupManager->expects($this->once())
			->method('getUserGroupIds')
			->with($user)
			->willReturn(['group1', 'group2']);

		$actual = $this->principalBackend->findByUri('mailto:res6@foo.bar', $this->principalPrefix);
		$this->assertEquals($this->principalPrefix . '/backend3-res6', $actual);
	}

	public function testFindByUriByPrincipalForbiddenResource(): void {
		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->once())
			->method('getUser')
			->with()
			->willReturn($user);
		$this->groupManager->expects($this->once())
			->method('getUserGroupIds')
			->with($user)
			->willReturn(['group1', 'group2']);

		$actual = $this->principalBackend->findByUri('principal:' . $this->principalPrefix . '/backend3-res5', $this->principalPrefix);
		$this->assertEquals(null, $actual);
	}

	public function testFindByUriByPrincipalNotFound(): void {
		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->once())
			->method('getUser')
			->with()
			->willReturn($user);
		$this->groupManager->expects($this->once())
			->method('getUserGroupIds')
			->with($user)
			->willReturn(['group1', 'group2']);

		$actual = $this->principalBackend->findByUri('principal:' . $this->principalPrefix . '/db-123', $this->principalPrefix);
		$this->assertEquals(null, $actual);
	}

	public function testFindByUriByUnknownUri(): void {
		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->once())
			->method('getUser')
			->with()
			->willReturn($user);
		$this->groupManager->expects($this->once())
			->method('getUserGroupIds')
			->with($user)
			->willReturn(['group1', 'group2']);

		$actual = $this->principalBackend->findByUri('foobar:blub', $this->principalPrefix);
		$this->assertEquals(null, $actual);
	}

	protected function createTestDatasetInDb() {
		$query = self::$realDatabase->getQueryBuilder();
		$query->insert($this->mainDbTable)
			->values([
				'backend_id' => $query->createNamedParameter('backend1'),
				'resource_id' => $query->createNamedParameter('res1'),
				'email' => $query->createNamedParameter('res1@foo.bar'),
				'displayname' => $query->createNamedParameter('Beamer1'),
				'group_restrictions' => $query->createNamedParameter('[]'),
			])
			->execute();

		$query->insert($this->mainDbTable)
			->values([
				'backend_id' => $query->createNamedParameter('backend1'),
				'resource_id' => $query->createNamedParameter('res2'),
				'email' => $query->createNamedParameter('res2@foo.bar'),
				'displayname' => $query->createNamedParameter('TV1'),
				'group_restrictions' => $query->createNamedParameter('[]'),
			])
			->execute();

		$query->insert($this->mainDbTable)
			->values([
				'backend_id' => $query->createNamedParameter('backend2'),
				'resource_id' => $query->createNamedParameter('res3'),
				'email' => $query->createNamedParameter('res3@foo.bar'),
				'displayname' => $query->createNamedParameter('Beamer2'),
				'group_restrictions' => $query->createNamedParameter('[]'),
			])
			->execute();
		$id3 = $query->getLastInsertId();

		$query->insert($this->mainDbTable)
			->values([
				'backend_id' => $query->createNamedParameter('backend2'),
				'resource_id' => $query->createNamedParameter('res4'),
				'email' => $query->createNamedParameter('res4@foo.bar'),
				'displayname' => $query->createNamedParameter('TV2'),
				'group_restrictions' => $query->createNamedParameter('[]'),
			])
			->execute();
		$id4 = $query->getLastInsertId();

		$query->insert($this->mainDbTable)
			->values([
				'backend_id' => $query->createNamedParameter('backend3'),
				'resource_id' => $query->createNamedParameter('res5'),
				'email' => $query->createNamedParameter('res5@foo.bar'),
				'displayname' => $query->createNamedParameter('Beamer3'),
				'group_restrictions' => $query->createNamedParameter('["foo", "bar"]'),
			])
			->execute();

		$query->insert($this->mainDbTable)
			->values([
				'backend_id' => $query->createNamedParameter('backend3'),
				'resource_id' => $query->createNamedParameter('res6'),
				'email' => $query->createNamedParameter('res6@foo.bar'),
				'displayname' => $query->createNamedParameter('Pointer'),
				'group_restrictions' => $query->createNamedParameter('["group1", "bar"]'),
			])
			->execute();
		$id6 = $query->getLastInsertId();

		$query->insert($this->metadataDbTable)
			->values([
				$this->foreignKey => $query->createNamedParameter($id3),
				'key' => $query->createNamedParameter('{http://nextcloud.com/ns}foo'),
				'value' => $query->createNamedParameter('value1')
			])
			->execute();
		$query->insert($this->metadataDbTable)
			->values([
				$this->foreignKey => $query->createNamedParameter($id3),
				'key' => $query->createNamedParameter('{http://nextcloud.com/ns}meta2'),
				'value' => $query->createNamedParameter('value2')
			])
			->execute();
		$query->insert($this->metadataDbTable)
			->values([
				$this->foreignKey => $query->createNamedParameter($id4),
				'key' => $query->createNamedParameter('{http://nextcloud.com/ns}meta1'),
				'value' => $query->createNamedParameter('value1')
			])
			->execute();
		$query->insert($this->metadataDbTable)
			->values([
				$this->foreignKey => $query->createNamedParameter($id4),
				'key' => $query->createNamedParameter('{http://nextcloud.com/ns}meta3'),
				'value' => $query->createNamedParameter('value3-old')
			])
			->execute();
		$query->insert($this->metadataDbTable)
			->values([
				$this->foreignKey => $query->createNamedParameter($id6),
				'key' => $query->createNamedParameter('{http://nextcloud.com/ns}meta99'),
				'value' => $query->createNamedParameter('value99')
			])
			->execute();
	}
}
