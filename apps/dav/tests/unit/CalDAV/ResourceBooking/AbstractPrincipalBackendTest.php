<?php
/**
 * @copyright Copyright (c) 2018, Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\Tests\unit\CalDAV\ResourceBooking;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\ILogger;
use OCP\IUser;
use OCP\IUserSession;
use Sabre\DAV\PropPatch;
use Test\TestCase;

abstract class AbstractPrincipalBackendTest extends TestCase {

	/** @var \OCA\DAV\CalDAV\ResourceBooking\ResourcePrincipalBackend|\OCA\DAV\CalDAV\ResourceBooking\RoomPrincipalBackend */
	protected $principalBackend;

	/** @var IDBConnection|\PHPUnit_Framework_MockObject_MockObject */
	protected $dbConnection;

	/** @var IUserSession|\PHPUnit_Framework_MockObject_MockObject */
	protected $userSession;

	/** @var IGroupManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $groupManager;

	/** @var ILogger|\PHPUnit_Framework_MockObject_MockObject */
	protected $logger;

	/** @var string */
	protected $expectedDbTable;

	/** @var string */
	protected $principalPrefix;

	public function setUp() {
		parent::setUp();

		$this->dbConnection = $this->createMock(IDBConnection::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->logger = $this->createMock(ILogger::class);
	}

	public function testGetPrincipalsByPrefix() {
		$queryBuilder = $this->createMock(IQueryBuilder::class);
		$stmt = $this->createMock(\Doctrine\DBAL\Driver\Statement::class);
		$this->dbConnection->expects($this->once())
			->method('getQueryBuilder')
			->with()
			->will($this->returnValue($queryBuilder));

		$queryBuilder->expects($this->at(0))
			->method('select')
			->with(['id', 'backend_id', 'resource_id', 'email', 'displayname'])
			->will($this->returnValue($queryBuilder));
		$queryBuilder->expects($this->at(1))
			->method('from')
			->with($this->expectedDbTable)
			->will($this->returnValue($queryBuilder));
		$queryBuilder->expects($this->at(2))
			->method('execute')
			->with()
			->will($this->returnValue($stmt));

		$stmt->expects($this->at(0))
			->method('fetch')
			->with(\PDO::FETCH_ASSOC)
			->will($this->returnValue([
				'id' => 0,
				'backend_id' => 'db',
				'resource_id' => '123',
				'email' => 'foo@bar.com',
				'displayname' => 'Resource 123'
			]));
		$stmt->expects($this->at(1))
			->method('fetch')
			->with(\PDO::FETCH_ASSOC)
			->will($this->returnValue([
				'id' => 1,
				'backend_id' => 'ldap',
				'resource_id' => '123',
				'email' => 'ldap@bar.com',
				'displayname' => 'Resource 123 ldap'
			]));
		$stmt->expects($this->at(2))
			->method('fetch')
			->with(\PDO::FETCH_ASSOC)
			->will($this->returnValue([
				'id' => 2,
				'backend_id' => 'db',
				'resource_id' => '456',
				'email' => 'bli@bar.com',
				'displayname' => 'Resource 456'
			]));
		$stmt->expects($this->at(3))
			->method('fetch')
			->with(\PDO::FETCH_ASSOC)
			->will($this->returnValue(null));
		$stmt->expects($this->at(4))
			->method('closeCursor')
			->with();

		$actual = $this->principalBackend->getPrincipalsByPrefix($this->principalPrefix);
		$this->assertEquals([
			[
				'uri' => $this->principalPrefix . '/db-123',
				'{DAV:}displayname' => 'Resource 123',
				'{http://sabredav.org/ns}email-address' => 'foo@bar.com',
			],
			[
				'uri' => $this->principalPrefix . '/ldap-123',
				'{DAV:}displayname' => 'Resource 123 ldap',
				'{http://sabredav.org/ns}email-address' => 'ldap@bar.com',
			],
			[
				'uri' => $this->principalPrefix . '/db-456',
				'{DAV:}displayname' => 'Resource 456',
				'{http://sabredav.org/ns}email-address' => 'bli@bar.com',
			],
		], $actual);

	}

	public function testGetNoPrincipalsByPrefixForWrongPrincipalPrefix() {
		$this->dbConnection->expects($this->never())
			->method('getQueryBuilder');

		$actual = $this->principalBackend->getPrincipalsByPrefix('principals/users');
		$this->assertEquals([], $actual);
	}

	public function testGetPrincipalByPath() {
		$queryBuilder = $this->createMock(IQueryBuilder::class);
		$stmt = $this->createMock(\Doctrine\DBAL\Driver\Statement::class);
		$expr = $this->createMock(\OCP\DB\QueryBuilder\IExpressionBuilder::class);

		$this->dbConnection->expects($this->at(0))
			->method('getQueryBuilder')
			->with()
			->will($this->returnValue($queryBuilder));
		$queryBuilder->method('expr')
			->will($this->returnValue($expr));
		$expr->method('eq')
			->will($this->returnValueMap([
				['backend_id', 'createNamedParameter-1', null, 'WHERE_CLAUSE_1'],
				['resource_id', 'createNamedParameter-2', null, 'WHERE_CLAUSE_2'],
			]));
		$queryBuilder->method('createNamedParameter')
			->will($this->returnValueMap([
				['db', \PDO::PARAM_STR, null, 'createNamedParameter-1'],
				['123', \PDO::PARAM_STR, null, 'createNamedParameter-2'],
			]));

		$queryBuilder->expects($this->at(0))
			->method('select')
			->with(['id', 'backend_id', 'resource_id', 'email', 'displayname'])
			->will($this->returnValue($queryBuilder));
		$queryBuilder->expects($this->at(1))
			->method('from')
			->with($this->expectedDbTable)
			->will($this->returnValue($queryBuilder));
		$queryBuilder->expects($this->at(4))
			->method('where')
			->with('WHERE_CLAUSE_1')
			->will($this->returnValue($queryBuilder));
		$queryBuilder->expects($this->at(7))
			->method('andWhere')
			->with('WHERE_CLAUSE_2')
			->will($this->returnValue($queryBuilder));
		$queryBuilder->expects($this->at(8))
			->method('execute')
			->with()
			->will($this->returnValue($stmt));

		$stmt->expects($this->at(0))
			->method('fetch')
			->with(\PDO::FETCH_ASSOC)
			->will($this->returnValue([
				'id' => 0,
				'backend_id' => 'db',
				'resource_id' => '123',
				'email' => 'foo@bar.com',
				'displayname' => 'Resource 123'
			]));

		$actual = $this->principalBackend->getPrincipalByPath($this->principalPrefix . '/db-123');
		$this->assertEquals([
			'uri' => $this->principalPrefix . '/db-123',
			'{DAV:}displayname' => 'Resource 123',
			'{http://sabredav.org/ns}email-address' => 'foo@bar.com',
		], $actual);
	}

	public function testGetPrincipalByPathNotFound() {
		$queryBuilder = $this->createMock(IQueryBuilder::class);
		$stmt = $this->createMock(\Doctrine\DBAL\Driver\Statement::class);
		$expr = $this->createMock(\OCP\DB\QueryBuilder\IExpressionBuilder::class);

		$this->dbConnection->expects($this->at(0))
			->method('getQueryBuilder')
			->with()
			->will($this->returnValue($queryBuilder));
		$queryBuilder->method('expr')
			->will($this->returnValue($expr));
		$expr->method('eq')
			->will($this->returnValueMap([
				['backend_id', 'createNamedParameter-1', null, 'WHERE_CLAUSE_1'],
				['resource_id', 'createNamedParameter-2', null, 'WHERE_CLAUSE_2'],
			]));
		$queryBuilder->method('createNamedParameter')
			->will($this->returnValueMap([
				['db', \PDO::PARAM_STR, null, 'createNamedParameter-1'],
				['123', \PDO::PARAM_STR, null, 'createNamedParameter-2'],
			]));

		$queryBuilder->expects($this->at(0))
			->method('select')
			->with(['id', 'backend_id', 'resource_id', 'email', 'displayname'])
			->will($this->returnValue($queryBuilder));
		$queryBuilder->expects($this->at(1))
			->method('from')
			->with($this->expectedDbTable)
			->will($this->returnValue($queryBuilder));
		$queryBuilder->expects($this->at(4))
			->method('where')
			->with('WHERE_CLAUSE_1')
			->will($this->returnValue($queryBuilder));
		$queryBuilder->expects($this->at(7))
			->method('andWhere')
			->with('WHERE_CLAUSE_2')
			->will($this->returnValue($queryBuilder));
		$queryBuilder->expects($this->at(8))
			->method('execute')
			->with()
			->will($this->returnValue($stmt));

		$stmt->expects($this->at(0))
			->method('fetch')
			->with(\PDO::FETCH_ASSOC)
			->will($this->returnValue(false));

		$actual = $this->principalBackend->getPrincipalByPath($this->principalPrefix . '/db-123');
		$this->assertEquals(null, $actual);
	}

	public function testGetPrincipalByPathWrongPrefix() {
		$this->dbConnection->expects($this->never())
			->method('getQueryBuilder');

		$actual = $this->principalBackend->getPrincipalByPath('principals/users/foo-bar');
		$this->assertEquals(null, $actual);
	}

	public function testGetGroupMemberSet() {
		$actual = $this->principalBackend->getGroupMemberSet($this->principalPrefix . '/foo-bar');
		$this->assertEquals([], $actual);
	}

	public function testGetGroupMembership() {
		$actual = $this->principalBackend->getGroupMembership($this->principalPrefix . '/foo-bar');
		$this->assertEquals([], $actual);
	}

	/**
	 * @expectedException        \Sabre\DAV\Exception
	 * @expectedExceptionMessage Setting members of the group is not supported yet
	 */
	public function testSetGroupMemberSet() {
		$this->principalBackend->setGroupMemberSet($this->principalPrefix . '/foo-bar', ['foo', 'bar']);
	}

	public function testUpdatePrincipal() {
		$propPatch = $this->createMock(PropPatch::class);
		$actual = $this->principalBackend->updatePrincipal($this->principalPrefix . '/foo-bar', $propPatch);

		$this->assertEquals(0, $actual);
	}

	/**
	 * @dataProvider dataSearchPrincipals
	 */
	public function testSearchPrincipals($expected, $test) {
		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->once())
			->method('getUser')
			->with()
			->will($this->returnValue($user));
		$this->groupManager->expects($this->once())
			->method('getUserGroupIds')
			->with($user)
			->will($this->returnValue(['group1', 'group2']));

		$queryBuilder1 = $this->createMock(IQueryBuilder::class);
		$queryBuilder2 = $this->createMock(IQueryBuilder::class);
		$stmt1 = $this->createMock(\Doctrine\DBAL\Driver\Statement::class);
		$stmt2 = $this->createMock(\Doctrine\DBAL\Driver\Statement::class);
		$expr1 = $this->createMock(\OCP\DB\QueryBuilder\IExpressionBuilder::class);
		$expr2 = $this->createMock(\OCP\DB\QueryBuilder\IExpressionBuilder::class);

		$this->dbConnection->expects($this->at(0))
			->method('getQueryBuilder')
			->with()
			->will($this->returnValue($queryBuilder1));
		$this->dbConnection->expects($this->at(1))
			->method('escapeLikeParameter')
			->with('foo')
			->will($this->returnValue('escapedFoo'));
		$this->dbConnection->expects($this->at(2))
			->method('getQueryBuilder')
			->with()
			->will($this->returnValue($queryBuilder2));
		$this->dbConnection->expects($this->at(3))
			->method('escapeLikeParameter')
			->with('bar')
			->will($this->returnValue('escapedBar'));

		$queryBuilder1->method('expr')
			->will($this->returnValue($expr1));
		$queryBuilder2->method('expr')
			->will($this->returnValue($expr2));

		$expr1->method('iLike')
			->will($this->returnValueMap([
				['email', 'createNamedParameter-1', null, 'ILIKE_CLAUSE_1'],
			]));
		$expr2->method('iLike')
			->will($this->returnValueMap([
				['displayname', 'createNamedParameter-2', null, 'ILIKE_CLAUSE_2'],
			]));

		$queryBuilder1->method('expr')
			->will($this->returnValue($expr1));
		$queryBuilder2->method('expr')
			->will($this->returnValue($expr2));

		$queryBuilder1->method('createNamedParameter')
			->will($this->returnValueMap([
				['%escapedFoo%', \PDO::PARAM_STR, null, 'createNamedParameter-1'],
			]));
		$queryBuilder2->method('createNamedParameter')
			->will($this->returnValueMap([
				['%escapedBar%', \PDO::PARAM_STR, null, 'createNamedParameter-2'],
			]));

		$queryBuilder1->expects($this->at(0))
			->method('select')
			->with(['id', 'backend_id', 'resource_id', 'email', 'displayname', 'group_restrictions'])
			->will($this->returnValue($queryBuilder1));
		$queryBuilder1->expects($this->at(1))
			->method('from')
			->with($this->expectedDbTable)
			->will($this->returnValue($queryBuilder1));
		$queryBuilder1->expects($this->at(4))
			->method('where')
			->with('ILIKE_CLAUSE_1')
			->will($this->returnValue($queryBuilder1));
		$queryBuilder1->expects($this->at(5))
			->method('execute')
			->with()
			->will($this->returnValue($stmt1));

		$queryBuilder2->expects($this->at(0))
			->method('select')
			->with(['id', 'backend_id', 'resource_id', 'email', 'displayname', 'group_restrictions'])
			->will($this->returnValue($queryBuilder2));
		$queryBuilder2->expects($this->at(1))
			->method('from')
			->with($this->expectedDbTable)
			->will($this->returnValue($queryBuilder2));
		$queryBuilder2->expects($this->at(4))
			->method('where')
			->with('ILIKE_CLAUSE_2')
			->will($this->returnValue($queryBuilder2));
		$queryBuilder2->expects($this->at(5))
			->method('execute')
			->with()
			->will($this->returnValue($stmt2));

		$stmt1->expects($this->at(0))
			->method('fetch')
			->with(\PDO::FETCH_ASSOC)
			->will($this->returnValue([
				'id' => 0,
				'backend_id' => 'db',
				'resource_id' => '1',
				'email' => '1',
				'displayname' => 'Resource 1',
				'group_restrictions' => null,
			]));
		$stmt1->expects($this->at(1))
			->method('fetch')
			->with(\PDO::FETCH_ASSOC)
			->will($this->returnValue([
				'id' => 1,
				'backend_id' => 'db',
				'resource_id' => '2',
				'email' => '2',
				'displayname' => 'Resource 2',
				'group_restrictions' => '',
			]));
		$stmt1->expects($this->at(2))
			->method('fetch')
			->with(\PDO::FETCH_ASSOC)
			->will($this->returnValue([
				'id' => 2,
				'backend_id' => 'db',
				'resource_id' => '3',
				'email' => '3',
				'displayname' => 'Resource 3',
				'group_restrictions' => '["group3"]',
			]));
		$stmt1->expects($this->at(3))
			->method('fetch')
			->with(\PDO::FETCH_ASSOC)
			->will($this->returnValue([
				'id' => 99,
				'backend_id' => 'db',
				'resource_id' => '99',
				'email' => '99',
				'displayname' => 'Resource 99',
				'group_restrictions' => '["group1", "group2"]',
			]));
		$stmt1->expects($this->at(4))
			->method('fetch')
			->with(\PDO::FETCH_ASSOC)
			->will($this->returnValue(null));

		$stmt2->expects($this->at(0))
			->method('fetch')
			->with(\PDO::FETCH_ASSOC)
			->will($this->returnValue([
				'id' => 0,
				'backend_id' => 'db',
				'resource_id' => '4',
				'email' => '4',
				'displayname' => 'Resource 4',
				'group_restrictions' => '[]'
			]));
		$stmt2->expects($this->at(1))
			->method('fetch')
			->with(\PDO::FETCH_ASSOC)
			->will($this->returnValue([
				'id' => 1,
				'backend_id' => 'db',
				'resource_id' => '5',
				'email' => '5',
				'displayname' => 'Resource 5',
				'group_restrictions' => '["group1", "group5"]'
			]));
		$stmt2->expects($this->at(2))
			->method('fetch')
			->with(\PDO::FETCH_ASSOC)
			->will($this->returnValue([
				'id' => 99,
				'backend_id' => 'db',
				'resource_id' => '99',
				'email' => '99',
				'displayname' => 'Resource 99',
				'group_restrictions' => '["group1", "group2"]',
			]));
		$stmt2->expects($this->at(3))
			->method('fetch')
			->with(\PDO::FETCH_ASSOC)
			->will($this->returnValue(null));

		$actual = $this->principalBackend->searchPrincipals($this->principalPrefix, [
			'{http://sabredav.org/ns}email-address' => 'foo',
			'{DAV:}displayname' => 'bar',
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
				'%prefix%/db-99'
			], 'allof'],
			[[
				'%prefix%/db-1',
				'%prefix%/db-2',
				'%prefix%/db-99',
				'%prefix%/db-4',
				'%prefix%/db-5',
			], 'anyof'],
		];
	}

	public function testSearchPrincipalsEmptySearchProperties() {
		$this->userSession->expects($this->never())
			->method('getUser');
		$this->groupManager->expects($this->never())
			->method('getUserGroupIds');
		$this->dbConnection->expects($this->never())
			->method('getQueryBuilder');

		$this->principalBackend->searchPrincipals($this->principalPrefix, []);
	}

	public function testSearchPrincipalsWrongPrincipalPrefix() {
		$this->userSession->expects($this->never())
			->method('getUser');
		$this->groupManager->expects($this->never())
			->method('getUserGroupIds');
		$this->dbConnection->expects($this->never())
			->method('getQueryBuilder');

		$this->principalBackend->searchPrincipals('principals/users', [
			'{http://sabredav.org/ns}email-address' => 'foo'
		]);
	}

	public function testFindByUriByEmail() {
		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->once())
			->method('getUser')
			->with()
			->will($this->returnValue($user));
		$this->groupManager->expects($this->once())
			->method('getUserGroupIds')
			->with($user)
			->will($this->returnValue(['group1', 'group2']));

		$queryBuilder = $this->createMock(IQueryBuilder::class);
		$stmt = $this->createMock(\Doctrine\DBAL\Driver\Statement::class);
		$expr = $this->createMock(\OCP\DB\QueryBuilder\IExpressionBuilder::class);

		$this->dbConnection->expects($this->at(0))
			->method('getQueryBuilder')
			->with()
			->will($this->returnValue($queryBuilder));
		$queryBuilder->method('expr')
			->will($this->returnValue($expr));
		$expr->method('eq')
			->will($this->returnValueMap([
				['email', 'createNamedParameter-1', null, 'WHERE_CLAUSE_1'],
			]));
		$queryBuilder->method('createNamedParameter')
			->will($this->returnValueMap([
				['foo@bar.com', \PDO::PARAM_STR, null, 'createNamedParameter-1'],
			]));

		$queryBuilder->expects($this->at(0))
			->method('select')
			->with(['id', 'backend_id', 'resource_id', 'email', 'displayname', 'group_restrictions'])
			->will($this->returnValue($queryBuilder));
		$queryBuilder->expects($this->at(1))
			->method('from')
			->with($this->expectedDbTable)
			->will($this->returnValue($queryBuilder));
		$queryBuilder->expects($this->at(4))
			->method('where')
			->with('WHERE_CLAUSE_1')
			->will($this->returnValue($queryBuilder));
		$queryBuilder->expects($this->at(5))
			->method('execute')
			->with()
			->will($this->returnValue($stmt));

		$stmt->expects($this->at(0))
			->method('fetch')
			->with(\PDO::FETCH_ASSOC)
			->will($this->returnValue([
				'id' => 0,
				'backend_id' => 'db',
				'resource_id' => '123',
				'email' => 'foo@bar.com',
				'displayname' => 'Resource 123',
				'group_restrictions' => '["group1"]',
			]));

		$actual = $this->principalBackend->findByUri('mailto:foo@bar.com', $this->principalPrefix);
		$this->assertEquals($this->principalPrefix . '/db-123', $actual);
	}

	public function testFindByUriByEmailForbiddenResource() {
		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->once())
			->method('getUser')
			->with()
			->will($this->returnValue($user));
		$this->groupManager->expects($this->once())
			->method('getUserGroupIds')
			->with($user)
			->will($this->returnValue(['group1', 'group2']));

		$queryBuilder = $this->createMock(IQueryBuilder::class);
		$stmt = $this->createMock(\Doctrine\DBAL\Driver\Statement::class);
		$expr = $this->createMock(\OCP\DB\QueryBuilder\IExpressionBuilder::class);

		$this->dbConnection->expects($this->at(0))
			->method('getQueryBuilder')
			->with()
			->will($this->returnValue($queryBuilder));
		$queryBuilder->method('expr')
			->will($this->returnValue($expr));
		$expr->method('eq')
			->will($this->returnValueMap([
				['email', 'createNamedParameter-1', null, 'WHERE_CLAUSE_1'],
			]));
		$queryBuilder->method('createNamedParameter')
			->will($this->returnValueMap([
				['foo@bar.com', \PDO::PARAM_STR, null, 'createNamedParameter-1'],
			]));

		$queryBuilder->expects($this->at(0))
			->method('select')
			->with(['id', 'backend_id', 'resource_id', 'email', 'displayname', 'group_restrictions'])
			->will($this->returnValue($queryBuilder));
		$queryBuilder->expects($this->at(1))
			->method('from')
			->with($this->expectedDbTable)
			->will($this->returnValue($queryBuilder));
		$queryBuilder->expects($this->at(4))
			->method('where')
			->with('WHERE_CLAUSE_1')
			->will($this->returnValue($queryBuilder));
		$queryBuilder->expects($this->at(5))
			->method('execute')
			->with()
			->will($this->returnValue($stmt));

		$stmt->expects($this->at(0))
			->method('fetch')
			->with(\PDO::FETCH_ASSOC)
			->will($this->returnValue([
				'id' => 0,
				'backend_id' => 'db',
				'resource_id' => '123',
				'email' => 'foo@bar.com',
				'displayname' => 'Resource 123',
				'group_restrictions' => '["group3"]',
			]));

		$actual = $this->principalBackend->findByUri('mailto:foo@bar.com', $this->principalPrefix);
		$this->assertEquals(null, $actual);
	}

	public function testFindByUriByEmailNotFound() {
		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->once())
			->method('getUser')
			->with()
			->will($this->returnValue($user));
		$this->groupManager->expects($this->once())
			->method('getUserGroupIds')
			->with($user)
			->will($this->returnValue(['group1', 'group2']));

		$queryBuilder = $this->createMock(IQueryBuilder::class);
		$stmt = $this->createMock(\Doctrine\DBAL\Driver\Statement::class);
		$expr = $this->createMock(\OCP\DB\QueryBuilder\IExpressionBuilder::class);

		$this->dbConnection->expects($this->at(0))
			->method('getQueryBuilder')
			->with()
			->will($this->returnValue($queryBuilder));
		$queryBuilder->method('expr')
			->will($this->returnValue($expr));
		$expr->method('eq')
			->will($this->returnValueMap([
				['email', 'createNamedParameter-1', null, 'WHERE_CLAUSE_1'],
			]));
		$queryBuilder->method('createNamedParameter')
			->will($this->returnValueMap([
				['foo@bar.com', \PDO::PARAM_STR, null, 'createNamedParameter-1'],
			]));

		$queryBuilder->expects($this->at(0))
			->method('select')
			->with(['id', 'backend_id', 'resource_id', 'email', 'displayname', 'group_restrictions'])
			->will($this->returnValue($queryBuilder));
		$queryBuilder->expects($this->at(1))
			->method('from')
			->with($this->expectedDbTable)
			->will($this->returnValue($queryBuilder));
		$queryBuilder->expects($this->at(4))
			->method('where')
			->with('WHERE_CLAUSE_1')
			->will($this->returnValue($queryBuilder));
		$queryBuilder->expects($this->at(5))
			->method('execute')
			->with()
			->will($this->returnValue($stmt));

		$stmt->expects($this->at(0))
			->method('fetch')
			->with(\PDO::FETCH_ASSOC)
			->will($this->returnValue(null));

		$actual = $this->principalBackend->findByUri('mailto:foo@bar.com', $this->principalPrefix);
		$this->assertEquals(null, $actual);
	}

	public function testFindByUriByPrincipal() {
		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->once())
			->method('getUser')
			->with()
			->will($this->returnValue($user));
		$this->groupManager->expects($this->once())
			->method('getUserGroupIds')
			->with($user)
			->will($this->returnValue(['group1', 'group2']));

		$queryBuilder = $this->createMock(IQueryBuilder::class);
		$stmt = $this->createMock(\Doctrine\DBAL\Driver\Statement::class);
		$expr = $this->createMock(\OCP\DB\QueryBuilder\IExpressionBuilder::class);

		$this->dbConnection->expects($this->at(0))
			->method('getQueryBuilder')
			->with()
			->will($this->returnValue($queryBuilder));
		$queryBuilder->method('expr')
			->will($this->returnValue($expr));
		$expr->method('eq')
			->will($this->returnValueMap([
				['email', 'createNamedParameter-1', null, 'WHERE_CLAUSE_1'],
			]));
		$queryBuilder->method('createNamedParameter')
			->will($this->returnValueMap([
				['foo@bar.com', \PDO::PARAM_STR, null, 'createNamedParameter-1'],
			]));

		$queryBuilder->expects($this->at(0))
			->method('select')
			->with(['id', 'backend_id', 'resource_id', 'email', 'displayname', 'group_restrictions'])
			->will($this->returnValue($queryBuilder));
		$queryBuilder->expects($this->at(1))
			->method('from')
			->with($this->expectedDbTable)
			->will($this->returnValue($queryBuilder));
		$queryBuilder->expects($this->at(4))
			->method('where')
			->with('WHERE_CLAUSE_1')
			->will($this->returnValue($queryBuilder));
		$queryBuilder->expects($this->at(5))
			->method('execute')
			->with()
			->will($this->returnValue($stmt));

		$stmt->expects($this->at(0))
			->method('fetch')
			->with(\PDO::FETCH_ASSOC)
			->will($this->returnValue([
				'id' => 0,
				'backend_id' => 'db',
				'resource_id' => '123',
				'email' => 'foo@bar.com',
				'displayname' => 'Resource 123',
				'group_restrictions' => '["group1"]',
			]));

		$actual = $this->principalBackend->findByUri('mailto:foo@bar.com', $this->principalPrefix);
		$this->assertEquals($this->principalPrefix . '/db-123', $actual);
	}

	public function testFindByUriByPrincipalForbiddenResource() {
		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->once())
			->method('getUser')
			->with()
			->will($this->returnValue($user));
		$this->groupManager->expects($this->once())
			->method('getUserGroupIds')
			->with($user)
			->will($this->returnValue(['group1', 'group2']));

		$queryBuilder = $this->createMock(IQueryBuilder::class);
		$stmt = $this->createMock(\Doctrine\DBAL\Driver\Statement::class);
		$expr = $this->createMock(\OCP\DB\QueryBuilder\IExpressionBuilder::class);

		$this->dbConnection->expects($this->at(0))
			->method('getQueryBuilder')
			->with()
			->will($this->returnValue($queryBuilder));
		$queryBuilder->method('expr')
			->will($this->returnValue($expr));
		$expr->method('eq')
			->will($this->returnValueMap([
				['backend_id', 'createNamedParameter-1', null, 'WHERE_CLAUSE_1'],
				['resource_id', 'createNamedParameter-2', null, 'WHERE_CLAUSE_2'],
			]));
		$queryBuilder->method('createNamedParameter')
			->will($this->returnValueMap([
				['db', \PDO::PARAM_STR, null, 'createNamedParameter-1'],
				['123', \PDO::PARAM_STR, null, 'createNamedParameter-2'],
			]));

		$queryBuilder->expects($this->at(0))
			->method('select')
			->with(['id', 'backend_id', 'resource_id', 'email', 'displayname', 'group_restrictions'])
			->will($this->returnValue($queryBuilder));
		$queryBuilder->expects($this->at(1))
			->method('from')
			->with($this->expectedDbTable)
			->will($this->returnValue($queryBuilder));
		$queryBuilder->expects($this->at(4))
			->method('where')
			->with('WHERE_CLAUSE_1')
			->will($this->returnValue($queryBuilder));
		$queryBuilder->expects($this->at(7))
			->method('andWhere')
			->with('WHERE_CLAUSE_2')
			->will($this->returnValue($queryBuilder));
		$queryBuilder->expects($this->at(8))
			->method('execute')
			->with()
			->will($this->returnValue($stmt));

		$stmt->expects($this->at(0))
			->method('fetch')
			->with(\PDO::FETCH_ASSOC)
			->will($this->returnValue([
				'id' => 0,
				'backend_id' => 'db',
				'resource_id' => '123',
				'email' => 'foo@bar.com',
				'displayname' => 'Resource 123',
				'group_restrictions' => '["group3"]',
			]));

		$actual = $this->principalBackend->findByUri('principal:' . $this->principalPrefix . '/db-123', $this->principalPrefix);
		$this->assertEquals(null, $actual);
	}

	public function testFindByUriByPrincipalNotFound() {
		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->once())
			->method('getUser')
			->with()
			->will($this->returnValue($user));
		$this->groupManager->expects($this->once())
			->method('getUserGroupIds')
			->with($user)
			->will($this->returnValue(['group1', 'group2']));

		$queryBuilder = $this->createMock(IQueryBuilder::class);
		$stmt = $this->createMock(\Doctrine\DBAL\Driver\Statement::class);
		$expr = $this->createMock(\OCP\DB\QueryBuilder\IExpressionBuilder::class);

		$this->dbConnection->expects($this->at(0))
			->method('getQueryBuilder')
			->with()
			->will($this->returnValue($queryBuilder));
		$queryBuilder->method('expr')
			->will($this->returnValue($expr));
		$expr->method('eq')
			->will($this->returnValueMap([
				['backend_id', 'createNamedParameter-1', null, 'WHERE_CLAUSE_1'],
				['resource_id', 'createNamedParameter-2', null, 'WHERE_CLAUSE_2'],
			]));
		$queryBuilder->method('createNamedParameter')
			->will($this->returnValueMap([
				['db', \PDO::PARAM_STR, null, 'createNamedParameter-1'],
				['123', \PDO::PARAM_STR, null, 'createNamedParameter-2'],
			]));

		$queryBuilder->expects($this->at(0))
			->method('select')
			->with(['id', 'backend_id', 'resource_id', 'email', 'displayname', 'group_restrictions'])
			->will($this->returnValue($queryBuilder));
		$queryBuilder->expects($this->at(1))
			->method('from')
			->with($this->expectedDbTable)
			->will($this->returnValue($queryBuilder));
		$queryBuilder->expects($this->at(4))
			->method('where')
			->with('WHERE_CLAUSE_1')
			->will($this->returnValue($queryBuilder));
		$queryBuilder->expects($this->at(7))
			->method('andWhere')
			->with('WHERE_CLAUSE_2')
			->will($this->returnValue($queryBuilder));
		$queryBuilder->expects($this->at(8))
			->method('execute')
			->with()
			->will($this->returnValue($stmt));

		$stmt->expects($this->at(0))
			->method('fetch')
			->with(\PDO::FETCH_ASSOC)
			->will($this->returnValue(null));

		$actual = $this->principalBackend->findByUri('principal:' . $this->principalPrefix . '/db-123', $this->principalPrefix);
		$this->assertEquals(null, $actual);
	}

	public function testFindByUriByUnknownUri() {
		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->once())
			->method('getUser')
			->with()
			->will($this->returnValue($user));
		$this->groupManager->expects($this->once())
			->method('getUserGroupIds')
			->with($user)
			->will($this->returnValue(['group1', 'group2']));

		$actual = $this->principalBackend->findByUri('foobar:blub', $this->principalPrefix);
		$this->assertEquals(null, $actual);
	}

}
