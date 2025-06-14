<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP\Tests;

use OC\ServerNotAvailableException;
use OCA\User_LDAP\Access;
use OCA\User_LDAP\Connection;
use OCA\User_LDAP\Exceptions\ConstraintViolationException;
use OCA\User_LDAP\Helper;
use OCA\User_LDAP\ILDAPWrapper;
use OCA\User_LDAP\LDAP;
use OCA\User_LDAP\Mapping\GroupMapping;
use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\User\Manager;
use OCA\User_LDAP\User\OfflineUser;
use OCA\User_LDAP\User\User;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\HintException;
use OCP\IAppConfig;
use OCP\IAvatarManager;
use OCP\IConfig;
use OCP\Image;
use OCP\IUserManager;
use OCP\Notification\IManager as INotificationManager;
use OCP\Server;
use OCP\Share\IManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

/**
 * Class AccessTest
 *
 * @group DB
 *
 * @package OCA\User_LDAP\Tests
 */
class AccessTest extends TestCase {
	protected UserMapping&MockObject $userMapper;
	protected IManager&MockObject $shareManager;
	protected GroupMapping&MockObject $groupMapper;
	private Connection&MockObject $connection;
	private LDAP&MockObject $ldap;
	private Manager&MockObject $userManager;
	private Helper&MockObject $helper;
	private IConfig&MockObject $config;
	private IUserManager&MockObject $ncUserManager;
	private LoggerInterface&MockObject $logger;
	private IAppConfig&MockObject $appConfig;
	private IEventDispatcher&MockObject $dispatcher;
	private Access $access;

	protected function setUp(): void {
		$this->ldap = $this->createMock(LDAP::class);
		$this->connection = $this->getMockBuilder(Connection::class)
			->setConstructorArgs([$this->ldap])
			->getMock();
		$this->userManager = $this->createMock(Manager::class);
		$this->helper = $this->createMock(Helper::class);
		$this->config = $this->createMock(IConfig::class);
		$this->userMapper = $this->createMock(UserMapping::class);
		$this->groupMapper = $this->createMock(GroupMapping::class);
		$this->ncUserManager = $this->createMock(IUserManager::class);
		$this->shareManager = $this->createMock(IManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->dispatcher = $this->createMock(IEventDispatcher::class);

		$this->access = new Access(
			$this->ldap,
			$this->connection,
			$this->userManager,
			$this->helper,
			$this->config,
			$this->ncUserManager,
			$this->logger,
			$this->appConfig,
			$this->dispatcher,
		);
		$this->dispatcher->expects($this->any())->method('dispatchTyped');
		$this->access->setUserMapper($this->userMapper);
		$this->access->setGroupMapper($this->groupMapper);
	}

	private function getConnectorAndLdapMock() {
		/** @var ILDAPWrapper&MockObject */
		$lw = $this->createMock(ILDAPWrapper::class);
		/** @var Connection&MockObject */
		$connector = $this->getMockBuilder(Connection::class)
			->setConstructorArgs([$lw, '', null])
			->getMock();
		$connector->expects($this->any())
			->method('getConnectionResource')
			->willReturn(ldap_connect('ldap://example.com'));
		/** @var Manager&MockObject */
		$um = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->createMock(IConfig::class),
				$this->createMock(LoggerInterface::class),
				$this->createMock(IAvatarManager::class),
				$this->createMock(Image::class),
				$this->createMock(IUserManager::class),
				$this->createMock(INotificationManager::class),
				$this->shareManager])
			->getMock();
		$helper = Server::get(Helper::class);

		return [$lw, $connector, $um, $helper];
	}

	public function testEscapeFilterPartValidChars(): void {
		$input = 'okay';
		$this->assertSame($input, $this->access->escapeFilterPart($input));
	}

	public function testEscapeFilterPartEscapeWildcard(): void {
		$input = '*';
		$expected = '\\2a';
		$this->assertSame($expected, $this->access->escapeFilterPart($input));
	}

	public function testEscapeFilterPartEscapeWildcard2(): void {
		$input = 'foo*bar';
		$expected = 'foo\\2abar';
		$this->assertSame($expected, $this->access->escapeFilterPart($input));
	}

	/**
	 * @dataProvider convertSID2StrSuccessData
	 * @param array $sidArray
	 * @param $sidExpected
	 */
	public function testConvertSID2StrSuccess(array $sidArray, $sidExpected): void {
		$sidBinary = implode('', $sidArray);
		$this->assertSame($sidExpected, $this->access->convertSID2Str($sidBinary));
	}

	public static function convertSID2StrSuccessData(): array {
		return [
			[
				[
					"\x01",
					"\x04",
					"\x00\x00\x00\x00\x00\x05",
					"\x15\x00\x00\x00",
					"\xa6\x81\xe5\x0e",
					"\x4d\x6c\x6c\x2b",
					"\xca\x32\x05\x5f",
				],
				'S-1-5-21-249921958-728525901-1594176202',
			],
			[
				[
					"\x01",
					"\x02",
					"\xFF\xFF\xFF\xFF\xFF\xFF",
					"\xFF\xFF\xFF\xFF",
					"\xFF\xFF\xFF\xFF",
				],
				'S-1-281474976710655-4294967295-4294967295',
			],
		];
	}

	public function testConvertSID2StrInputError(): void {
		$sidIllegal = 'foobar';
		$sidExpected = '';

		$this->assertSame($sidExpected, $this->access->convertSID2Str($sidIllegal));
	}

	public function testGetDomainDNFromDNSuccess(): void {
		$inputDN = 'uid=zaphod,cn=foobar,dc=my,dc=server,dc=com';
		$domainDN = 'dc=my,dc=server,dc=com';

		$this->ldap->expects($this->once())
			->method('explodeDN')
			->with($inputDN, 0)
			->willReturn(explode(',', $inputDN));

		$this->assertSame($domainDN, $this->access->getDomainDNFromDN($inputDN));
	}

	public function testGetDomainDNFromDNError(): void {
		$inputDN = 'foobar';
		$expected = '';

		$this->ldap->expects($this->once())
			->method('explodeDN')
			->with($inputDN, 0)
			->willReturn(false);

		$this->assertSame($expected, $this->access->getDomainDNFromDN($inputDN));
	}

	public static function dnInputDataProvider(): array {
		return [
			[
				'foo=bar,bar=foo,dc=foobar',
				[
					'count' => 3,
					0 => 'foo=bar',
					1 => 'bar=foo',
					2 => 'dc=foobar'
				],
				true
			],
			[
				'foobarbarfoodcfoobar',
				false,
				false
			]
		];
	}

	/**
	 * @dataProvider dnInputDataProvider
	 */
	public function testStringResemblesDN(string $input, array|bool $interResult, bool $expectedResult): void {
		[$lw, $con, $um, $helper] = $this->getConnectorAndLdapMock();
		/** @var IConfig&MockObject $config */
		$config = $this->createMock(IConfig::class);
		$access = new Access($lw, $con, $um, $helper, $config, $this->ncUserManager, $this->logger, $this->appConfig, $this->dispatcher);

		$lw->expects($this->exactly(1))
			->method('explodeDN')
			->willReturnCallback(function ($dn) use ($input, $interResult) {
				if ($dn === $input) {
					return $interResult;
				}
				return null;
			});

		$this->assertSame($expectedResult, $access->stringResemblesDN($input));
	}

	/**
	 * @dataProvider dnInputDataProvider
	 */
	public function testStringResemblesDNLDAPmod(string $input, array|bool $interResult, bool $expectedResult): void {
		[, $con, $um, $helper] = $this->getConnectorAndLdapMock();
		/** @var IConfig&MockObject $config */
		$config = $this->createMock(IConfig::class);
		$lw = new LDAP();
		$access = new Access($lw, $con, $um, $helper, $config, $this->ncUserManager, $this->logger, $this->appConfig, $this->dispatcher);

		if (!function_exists('ldap_explode_dn')) {
			$this->markTestSkipped('LDAP Module not available');
		}

		$this->assertSame($expectedResult, $access->stringResemblesDN($input));
	}

	public function testCacheUserHome(): void {
		$this->connection->expects($this->once())
			->method('writeToCache');

		$this->access->cacheUserHome('foobar', '/foobars/path');
	}

	public function testBatchApplyUserAttributes(): void {
		$this->ldap->expects($this->any())
			->method('isResource')
			->willReturn(true);

		$this->connection
			->expects($this->any())
			->method('getConnectionResource')
			->willReturn(ldap_connect('ldap://example.com'));

		$this->ldap->expects($this->any())
			->method('getAttributes')
			->willReturn(['displayname' => ['bar', 'count' => 1]]);

		/** @var UserMapping&MockObject $mapperMock */
		$mapperMock = $this->createMock(UserMapping::class);
		$mapperMock->expects($this->any())
			->method('getNameByDN')
			->willReturn(false);
		$mapperMock->expects($this->any())
			->method('map')
			->willReturn(true);

		$userMock = $this->createMock(User::class);

		// also returns for userUuidAttribute
		$this->access->connection->expects($this->any())
			->method('__get')
			->willReturn('displayName');

		$this->access->setUserMapper($mapperMock);

		$displayNameAttribute = strtolower($this->access->connection->ldapUserDisplayName);
		$data = [
			[
				'dn' => ['foobar'],
				$displayNameAttribute => 'barfoo'
			],
			[
				'dn' => ['foo'],
				$displayNameAttribute => 'bar'
			],
			[
				'dn' => ['raboof'],
				$displayNameAttribute => 'oofrab'
			]
		];

		$userMock->expects($this->exactly(count($data)))
			->method('processAttributes');

		$this->userManager->expects($this->exactly(count($data) * 2))
			->method('get')
			->willReturn($userMock);

		$this->access->batchApplyUserAttributes($data);
	}

	public function testBatchApplyUserAttributesSkipped(): void {
		/** @var UserMapping&MockObject $mapperMock */
		$mapperMock = $this->createMock(UserMapping::class);
		$mapperMock->expects($this->any())
			->method('getNameByDN')
			->willReturn('a_username');

		$userMock = $this->createMock(User::class);

		$this->access->connection->expects($this->any())
			->method('__get')
			->willReturn('displayName');

		$this->access->setUserMapper($mapperMock);

		$displayNameAttribute = strtolower($this->access->connection->ldapUserDisplayName);
		$data = [
			[
				'dn' => ['foobar'],
				$displayNameAttribute => 'barfoo'
			],
			[
				'dn' => ['foo'],
				$displayNameAttribute => 'bar'
			],
			[
				'dn' => ['raboof'],
				$displayNameAttribute => 'oofrab'
			]
		];

		$userMock->expects($this->never())
			->method('processAttributes');

		$this->userManager->expects($this->any())
			->method('get')
			->willReturn($this->createMock(User::class));

		$this->access->batchApplyUserAttributes($data);
	}

	public function testBatchApplyUserAttributesDontSkip(): void {
		/** @var UserMapping&MockObject $mapperMock */
		$mapperMock = $this->createMock(UserMapping::class);
		$mapperMock->expects($this->any())
			->method('getNameByDN')
			->willReturn('a_username');

		$userMock = $this->createMock(User::class);

		$this->access->connection->expects($this->any())
			->method('__get')
			->willReturn('displayName');

		$this->access->setUserMapper($mapperMock);

		$displayNameAttribute = strtolower($this->access->connection->ldapUserDisplayName);
		$data = [
			[
				'dn' => ['foobar'],
				$displayNameAttribute => 'barfoo'
			],
			[
				'dn' => ['foo'],
				$displayNameAttribute => 'bar'
			],
			[
				'dn' => ['raboof'],
				$displayNameAttribute => 'oofrab'
			]
		];

		$userMock->expects($this->exactly(count($data)))
			->method('processAttributes');

		$this->userManager->expects($this->exactly(count($data) * 2))
			->method('get')
			->willReturn($userMock);

		$this->access->batchApplyUserAttributes($data);
	}

	public static function dNAttributeProvider(): array {
		// corresponds to Access::resemblesDN()
		return [
			'dn' => ['dn'],
			'uniqueMember' => ['uniquemember'],
			'member' => ['member'],
			'memberOf' => ['memberof']
		];
	}

	/**
	 * @dataProvider dNAttributeProvider
	 */
	public function testSanitizeDN(string $attribute): void {
		[$lw, $con, $um, $helper] = $this->getConnectorAndLdapMock();
		/** @var IConfig&MockObject $config */
		$config = $this->createMock(IConfig::class);

		$dnFromServer = 'cn=Mixed Cases,ou=Are Sufficient To,ou=Test,dc=example,dc=org';

		$lw->expects($this->any())
			->method('isResource')
			->willReturn(true);
		$lw->expects($this->any())
			->method('getAttributes')
			->willReturn([
				$attribute => ['count' => 1, $dnFromServer]
			]);

		$access = new Access($lw, $con, $um, $helper, $config, $this->ncUserManager, $this->logger, $this->appConfig, $this->dispatcher);
		$values = $access->readAttribute('uid=whoever,dc=example,dc=org', $attribute);
		$this->assertSame($values[0], strtolower($dnFromServer));
	}


	public function testSetPasswordWithDisabledChanges(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('LDAP password changes are disabled');

		$this->connection
			->method('__get')
			->willReturn(false);

		/** @noinspection PhpUnhandledExceptionInspection */
		$this->access->setPassword('CN=foo', 'MyPassword');
	}

	public function testSetPasswordWithLdapNotAvailable(): void {
		$this->connection
			->method('__get')
			->willReturn(true);
		$connection = ldap_connect('ldap://example.com');
		$this->connection
			->expects($this->once())
			->method('getConnectionResource')
			->willThrowException(new ServerNotAvailableException('Connection to LDAP server could not be established'));
		$this->ldap
			->expects($this->never())
			->method('isResource');

		$this->expectException(ServerNotAvailableException::class);
		$this->expectExceptionMessage('Connection to LDAP server could not be established');
		$this->access->setPassword('CN=foo', 'MyPassword');
	}


	public function testSetPasswordWithRejectedChange(): void {
		$this->expectException(HintException::class);
		$this->expectExceptionMessage('Password change rejected.');

		$this->connection
			->method('__get')
			->willReturn(true);
		$connection = ldap_connect('ldap://example.com');
		$this->connection
			->expects($this->any())
			->method('getConnectionResource')
			->willReturn($connection);
		$this->ldap
			->expects($this->once())
			->method('modReplace')
			->with($connection, 'CN=foo', 'MyPassword')
			->willThrowException(new ConstraintViolationException());

		/** @noinspection PhpUnhandledExceptionInspection */
		$this->access->setPassword('CN=foo', 'MyPassword');
	}

	public function testSetPassword(): void {
		$this->connection
			->method('__get')
			->willReturn(true);
		$connection = ldap_connect('ldap://example.com');
		$this->connection
			->expects($this->any())
			->method('getConnectionResource')
			->willReturn($connection);
		$this->ldap
			->expects($this->once())
			->method('modReplace')
			->with($connection, 'CN=foo', 'MyPassword')
			->willReturn(true);

		/** @noinspection PhpUnhandledExceptionInspection */
		$this->assertTrue($this->access->setPassword('CN=foo', 'MyPassword'));
	}

	protected function prepareMocksForSearchTests(
		$base,
		$fakeConnection,
		$fakeSearchResultResource,
		$fakeLdapEntries,
	) {
		$this->connection
			->expects($this->any())
			->method('getConnectionResource')
			->willReturn($fakeConnection);
		$this->connection->expects($this->any())
			->method('__get')
			->willReturnCallback(function ($key) use ($base) {
				if (stripos($key, 'base') !== false) {
					return [$base];
				}
				return null;
			});

		$this->ldap
			->expects($this->any())
			->method('isResource')
			->willReturnCallback(function ($resource) {
				return is_object($resource);
			});
		$this->ldap
			->expects($this->any())
			->method('errno')
			->willReturn(0);
		$this->ldap
			->expects($this->once())
			->method('search')
			->willReturn($fakeSearchResultResource);
		$this->ldap
			->expects($this->exactly(1))
			->method('getEntries')
			->willReturn($fakeLdapEntries);

		$this->helper->expects($this->any())
			->method('sanitizeDN')
			->willReturnArgument(0);
	}

	public function testSearchNoPagedSearch(): void {
		// scenario: no pages search, 1 search base
		$filter = 'objectClass=nextcloudUser';
		$base = 'ou=zombies,dc=foobar,dc=nextcloud,dc=com';

		$fakeConnection = ldap_connect();
		$fakeSearchResultResource = ldap_connect();
		$fakeLdapEntries = [
			'count' => 2,
			[
				'dn' => 'uid=sgarth,' . $base,
			],
			[
				'dn' => 'uid=wwilson,' . $base,
			]
		];

		$expected = $fakeLdapEntries;
		unset($expected['count']);

		$this->prepareMocksForSearchTests($base, $fakeConnection, $fakeSearchResultResource, $fakeLdapEntries);

		/** @noinspection PhpUnhandledExceptionInspection */
		$result = $this->access->search($filter, $base);
		$this->assertSame($expected, $result);
	}

	public function testFetchListOfUsers(): void {
		$filter = 'objectClass=nextcloudUser';
		$base = 'ou=zombies,dc=foobar,dc=nextcloud,dc=com';
		$attrs = ['dn', 'uid'];

		$fakeConnection = ldap_connect();
		$fakeSearchResultResource = ldap_connect();
		$fakeLdapEntries = [
			'count' => 2,
			[
				'dn' => 'uid=sgarth,' . $base,
				'uid' => [ 'sgarth' ],
			],
			[
				'dn' => 'uid=wwilson,' . $base,
				'uid' => [ 'wwilson' ],
			]
		];
		$expected = $fakeLdapEntries;
		unset($expected['count']);
		array_walk($expected, function (&$v): void {
			$v['dn'] = [$v['dn']];	// dn is translated into an array internally for consistency
		});

		$this->prepareMocksForSearchTests($base, $fakeConnection, $fakeSearchResultResource, $fakeLdapEntries);

		// Called twice per user, for userExists and userExistsOnLdap
		$this->connection->expects($this->exactly(2 * $fakeLdapEntries['count']))
			->method('writeToCache')
			->with($this->stringStartsWith('userExists'), true);

		$this->userMapper->expects($this->exactly($fakeLdapEntries['count']))
			->method('getNameByDN')
			->willReturnCallback(function ($fdn) {
				$parts = ldap_explode_dn($fdn, 0);
				return $parts[0];
			});

		/** @noinspection PhpUnhandledExceptionInspection */
		$list = $this->access->fetchListOfUsers($filter, $attrs);
		$this->assertSame($expected, $list);
	}

	public function testFetchListOfGroupsKnown(): void {
		$filter = 'objectClass=nextcloudGroup';
		$attributes = ['cn', 'gidNumber', 'dn'];
		$base = 'ou=SomeGroups,dc=my,dc=directory';

		$fakeConnection = ldap_connect();
		$fakeSearchResultResource = ldap_connect();
		$fakeLdapEntries = [
			'count' => 2,
			[
				'dn' => 'cn=Good Team,' . $base,
				'cn' => ['Good Team'],
			],
			[
				'dn' => 'cn=Another Good Team,' . $base,
				'cn' => ['Another Good Team'],
			]
		];

		$this->prepareMocksForSearchTests($base, $fakeConnection, $fakeSearchResultResource, $fakeLdapEntries);

		$this->groupMapper->expects($this->any())
			->method('getListOfIdsByDn')
			->willReturn([
				'cn=Good Team,' . $base => 'Good_Team',
				'cn=Another Good Team,' . $base => 'Another_Good_Team',
			]);
		$this->groupMapper->expects($this->never())
			->method('getNameByDN');

		$this->connection->expects($this->exactly(1))
			->method('writeToCache');

		$groups = $this->access->fetchListOfGroups($filter, $attributes);
		$this->assertSame(2, count($groups));
		$this->assertSame('Good Team', $groups[0]['cn'][0]);
		$this->assertSame('Another Good Team', $groups[1]['cn'][0]);
	}

	public static function intUsernameProvider(): array {
		return [
			['alice', 'alice'],
			['b/ob', 'bob'],
			['charly🐬', 'charly'],
			['debo rah', 'debo_rah'],
			['epost@poste.test', 'epost@poste.test'],
			['fränk', 'frank'],
			[' UPPÉR Case/[\]^`', 'UPPER_Case'],
			[' gerda ', 'gerda'],
			['🕱🐵🐘🐑', null],
			[
				'OneNameToRuleThemAllOneNameToFindThemOneNameToBringThemAllAndInTheDarknessBindThem',
				'81ff71b5dd0f0092e2dc977b194089120093746e273f2ef88c11003762783127'
			]
		];
	}

	public static function groupIDCandidateProvider(): array {
		return [
			['alice', 'alice'],
			['b/ob', 'b/ob'],
			['charly🐬', 'charly🐬'],
			['debo rah', 'debo rah'],
			['epost@poste.test', 'epost@poste.test'],
			['fränk', 'fränk'],
			[' gerda ', 'gerda'],
			['🕱🐵🐘🐑', '🕱🐵🐘🐑'],
			[
				'OneNameToRuleThemAllOneNameToFindThemOneNameToBringThemAllAndInTheDarknessBindThem',
				'81ff71b5dd0f0092e2dc977b194089120093746e273f2ef88c11003762783127'
			]
		];
	}

	/**
	 * @dataProvider intUsernameProvider
	 */
	public function testSanitizeUsername(string $name, ?string $expected): void {
		if ($expected === null) {
			$this->expectException(\InvalidArgumentException::class);
		}
		$sanitizedName = $this->access->sanitizeUsername($name);
		$this->assertSame($expected, $sanitizedName);
	}

	/**
	 * @dataProvider groupIDCandidateProvider
	 */
	public function testSanitizeGroupIDCandidate(string $name, string $expected): void {
		$sanitizedName = $this->access->sanitizeGroupIDCandidate($name);
		$this->assertSame($expected, $sanitizedName);
	}

	public function testUserStateUpdate(): void {
		$this->connection->expects($this->any())
			->method('__get')
			->willReturnMap([
				[ 'ldapUserDisplayName', 'displayName' ],
				[ 'ldapUserDisplayName2', null],
			]);

		$offlineUserMock = $this->createMock(OfflineUser::class);
		$offlineUserMock->expects($this->once())
			->method('unmark');

		$regularUserMock = $this->createMock(User::class);

		$this->userManager->expects($this->atLeastOnce())
			->method('get')
			->with('detta')
			->willReturnOnConsecutiveCalls($offlineUserMock, $regularUserMock);

		/** @var UserMapping&MockObject $mapperMock */
		$mapperMock = $this->createMock(UserMapping::class);
		$mapperMock->expects($this->any())
			->method('getNameByDN')
			->with('uid=detta,ou=users,dc=hex,dc=ample')
			->willReturn('detta');
		$this->access->setUserMapper($mapperMock);

		$records = [
			[
				'dn' => ['uid=detta,ou=users,dc=hex,dc=ample'],
				'displayName' => ['Detta Detkova'],
			]
		];
		$this->access->nextcloudUserNames($records);
	}
}
