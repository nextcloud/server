<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OC\KnownUser\KnownUserService;
use OC\User\User;
use OCA\DAV\CalDAV\Proxy\Proxy;
use OCA\DAV\CalDAV\Proxy\ProxyMapper;
use OCA\DAV\Connector\Sabre\Principal;
use OCP\Accounts\IAccount;
use OCP\Accounts\IAccountManager;
use OCP\Accounts\IAccountProperty;
use OCP\Accounts\IAccountPropertyCollection;
use OCP\App\IAppManager;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Share\IManager;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\Exception;
use Sabre\DAV\PropPatch;
use Test\TestCase;

class PrincipalTest extends TestCase {
	private IUserManager&MockObject $userManager;
	private IGroupManager&MockObject $groupManager;
	private IAccountManager&MockObject $accountManager;
	private IManager&MockObject $shareManager;
	private IUserSession&MockObject $userSession;
	private IAppManager&MockObject $appManager;
	private ProxyMapper&MockObject $proxyMapper;
	private KnownUserService&MockObject $knownUserService;
	private IConfig&MockObject $config;
	private IFactory&MockObject $languageFactory;
	private Principal $connector;

	protected function setUp(): void {
		parent::setUp();

		$this->userManager = $this->createMock(IUserManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->accountManager = $this->createMock(IAccountManager::class);
		$this->shareManager = $this->createMock(IManager::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->proxyMapper = $this->createMock(ProxyMapper::class);
		$this->knownUserService = $this->createMock(KnownUserService::class);
		$this->config = $this->createMock(IConfig::class);
		$this->languageFactory = $this->createMock(IFactory::class);

		$this->connector = new Principal(
			$this->userManager,
			$this->groupManager,
			$this->accountManager,
			$this->shareManager,
			$this->userSession,
			$this->appManager,
			$this->proxyMapper,
			$this->knownUserService,
			$this->config,
			$this->languageFactory
		);
	}

	public function testGetPrincipalsByPrefixWithoutPrefix(): void {
		$response = $this->connector->getPrincipalsByPrefix('');
		$this->assertSame([], $response);
	}

	public function testGetPrincipalsByPrefixWithUsers(): void {
		$fooUser = $this->createMock(User::class);
		$fooUser
			->expects($this->once())
			->method('getUID')
			->willReturn('foo');
		$fooUser
			->expects($this->once())
			->method('getDisplayName')
			->willReturn('Dr. Foo-Bar');
		$fooUser
			->expects($this->once())
			->method('getSystemEMailAddress')
			->willReturn('');
		$barUser = $this->createMock(User::class);
		$barUser
			->expects($this->once())
			->method('getUID')
			->willReturn('bar');
		$barUser
			->expects($this->once())
			->method('getSystemEMailAddress')
			->willReturn('bar@nextcloud.com');
		$this->userManager
			->expects($this->once())
			->method('search')
			->with('')
			->willReturn([$fooUser, $barUser]);

		$this->languageFactory
			->expects($this->exactly(2))
			->method('getUserLanguage')
			->willReturnMap([
				[$fooUser, 'de'],
				[$barUser, 'en'],
			]);

		$fooAccountPropertyCollection = $this->createMock(IAccountPropertyCollection::class);
		$fooAccountPropertyCollection->expects($this->once())
			->method('getProperties')
			->willReturn([]);
		$fooAccount = $this->createMock(IAccount::class);
		$fooAccount->expects($this->once())
			->method('getPropertyCollection')
			->with(IAccountManager::COLLECTION_EMAIL)
			->willReturn($fooAccountPropertyCollection);

		$emailPropertyOne = $this->createMock(IAccountProperty::class);
		$emailPropertyOne->expects($this->once())
			->method('getValue')
			->willReturn('alias@nextcloud.com');
		$emailPropertyTwo = $this->createMock(IAccountProperty::class);
		$emailPropertyTwo->expects($this->once())
			->method('getValue')
			->willReturn('alias2@nextcloud.com');

		$barAccountPropertyCollection = $this->createMock(IAccountPropertyCollection::class);
		$barAccountPropertyCollection->expects($this->once())
			->method('getProperties')
			->willReturn([$emailPropertyOne, $emailPropertyTwo]);
		$barAccount = $this->createMock(IAccount::class);
		$barAccount->expects($this->once())
			->method('getPropertyCollection')
			->with(IAccountManager::COLLECTION_EMAIL)
			->willReturn($barAccountPropertyCollection);

		$this->accountManager
			->expects($this->exactly(2))
			->method('getAccount')
			->willReturnMap([
				[$fooUser, $fooAccount],
				[$barUser, $barAccount],
			]);

		$expectedResponse = [
			0 => [
				'uri' => 'principals/users/foo',
				'{DAV:}displayname' => 'Dr. Foo-Bar',
				'{urn:ietf:params:xml:ns:caldav}calendar-user-type' => 'INDIVIDUAL',
				'{http://nextcloud.com/ns}language' => 'de',
			],
			1 => [
				'uri' => 'principals/users/bar',
				'{DAV:}displayname' => 'bar',
				'{urn:ietf:params:xml:ns:caldav}calendar-user-type' => 'INDIVIDUAL',
				'{http://nextcloud.com/ns}language' => 'en',
				'{http://sabredav.org/ns}email-address' => 'bar@nextcloud.com',
				'{DAV:}alternate-URI-set' => ['mailto:alias@nextcloud.com', 'mailto:alias2@nextcloud.com']
			]
		];
		$response = $this->connector->getPrincipalsByPrefix('principals/users');
		$this->assertSame($expectedResponse, $response);
	}

	public function testGetPrincipalsByPrefixEmpty(): void {
		$this->userManager
			->expects($this->once())
			->method('search')
			->with('')
			->willReturn([]);

		$response = $this->connector->getPrincipalsByPrefix('principals/users');
		$this->assertSame([], $response);
	}

	public function testGetPrincipalsByPathWithoutMail(): void {
		$fooUser = $this->createMock(User::class);
		$fooUser
			->expects($this->once())
			->method('getUID')
			->willReturn('foo');
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('foo')
			->willReturn($fooUser);

		$this->languageFactory
			->expects($this->once())
			->method('getUserLanguage')
			->with($fooUser)
			->willReturn('de');

		$expectedResponse = [
			'uri' => 'principals/users/foo',
			'{DAV:}displayname' => 'foo',
			'{urn:ietf:params:xml:ns:caldav}calendar-user-type' => 'INDIVIDUAL',
			'{http://nextcloud.com/ns}language' => 'de'
		];
		$response = $this->connector->getPrincipalByPath('principals/users/foo');
		$this->assertSame($expectedResponse, $response);
	}

	public function testGetPrincipalsByPathWithMail(): void {
		$fooUser = $this->createMock(User::class);
		$fooUser
			->expects($this->once())
			->method('getSystemEMailAddress')
			->willReturn('foo@nextcloud.com');
		$fooUser
			->expects($this->once())
			->method('getUID')
			->willReturn('foo');
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('foo')
			->willReturn($fooUser);

		$this->languageFactory
			->expects($this->once())
			->method('getUserLanguage')
			->with($fooUser)
			->willReturn('de');

		$expectedResponse = [
			'uri' => 'principals/users/foo',
			'{DAV:}displayname' => 'foo',
			'{urn:ietf:params:xml:ns:caldav}calendar-user-type' => 'INDIVIDUAL',
			'{http://nextcloud.com/ns}language' => 'de',
			'{http://sabredav.org/ns}email-address' => 'foo@nextcloud.com',
		];
		$response = $this->connector->getPrincipalByPath('principals/users/foo');
		$this->assertSame($expectedResponse, $response);
	}

	public function testGetPrincipalsByPathEmpty(): void {
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('foo')
			->willReturn(null);

		$response = $this->connector->getPrincipalByPath('principals/users/foo');
		$this->assertNull($response);
	}

	public function testGetGroupMemberSet(): void {
		$response = $this->connector->getGroupMemberSet('principals/users/foo');
		$this->assertSame([], $response);
	}


	public function testGetGroupMemberSetEmpty(): void {
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Principal not found');

		$this->userManager
			->expects($this->once())
			->method('get')
			->with('foo')
			->willReturn(null);

		$this->connector->getGroupMemberSet('principals/users/foo/calendar-proxy-read');
	}

	public function testGetGroupMemberSetProxyRead(): void {
		$fooUser = $this->createMock(User::class);
		$fooUser
			->expects($this->once())
			->method('getUID')
			->willReturn('foo');
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('foo')
			->willReturn($fooUser);

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
			->with('principals/users/foo')
			->willReturn([$proxy1, $proxy2, $proxy3]);

		$this->assertEquals(['proxyId1'], $this->connector->getGroupMemberSet('principals/users/foo/calendar-proxy-read'));
	}

	public function testGetGroupMemberSetProxyWrite(): void {
		$fooUser = $this->createMock(User::class);
		$fooUser
			->expects($this->once())
			->method('getUID')
			->willReturn('foo');
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('foo')
			->willReturn($fooUser);

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
			->with('principals/users/foo')
			->willReturn([$proxy1, $proxy2, $proxy3]);

		$this->assertEquals(['proxyId2', 'proxyId3'], $this->connector->getGroupMemberSet('principals/users/foo/calendar-proxy-write'));
	}

	public function testGetGroupMembership(): void {
		$fooUser = $this->createMock(User::class);
		$group1 = $this->createMock(IGroup::class);
		$group1->expects($this->once())
			->method('getGID')
			->willReturn('group1');
		$group2 = $this->createMock(IGroup::class);
		$group2->expects($this->once())
			->method('getGID')
			->willReturn('foo/bar');
		$this->userManager
			->expects($this->exactly(2))
			->method('get')
			->with('foo')
			->willReturn($fooUser);
		$this->groupManager
			->expects($this->once())
			->method('getUserGroups')
			->with($fooUser)
			->willReturn([
				$group1,
				$group2,
			]);

		$proxy1 = new Proxy();
		$proxy1->setOwnerId('proxyId1');
		$proxy1->setPermissions(1);

		$proxy2 = new Proxy();
		$proxy2->setOwnerId('proxyId2');
		$proxy2->setPermissions(3);

		$this->proxyMapper->expects($this->once())
			->method('getProxiesFor')
			->with('principals/users/foo')
			->willReturn([$proxy1, $proxy2]);

		$expectedResponse = [
			'principals/groups/group1',
			'principals/groups/foo%2Fbar',
			'proxyId1/calendar-proxy-read',
			'proxyId2/calendar-proxy-write',
		];
		$response = $this->connector->getGroupMembership('principals/users/foo');
		$this->assertSame($expectedResponse, $response);
	}


	public function testGetGroupMembershipEmpty(): void {
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Principal not found');

		$this->userManager
			->expects($this->once())
			->method('get')
			->with('foo')
			->willReturn(null);

		$this->connector->getGroupMembership('principals/users/foo');
	}


	public function testSetGroupMembership(): void {
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Setting members of the group is not supported yet');

		$this->connector->setGroupMemberSet('principals/users/foo', ['foo']);
	}

	public function testSetGroupMembershipProxy(): void {
		$fooUser = $this->createMock(User::class);
		$fooUser
			->expects($this->once())
			->method('getUID')
			->willReturn('foo');
		$barUser = $this->createMock(User::class);
		$barUser
			->expects($this->once())
			->method('getUID')
			->willReturn('bar');
		$this->userManager
			->expects($this->exactly(2))
			->method('get')
			->willReturnMap([
				['foo', $fooUser],
				['bar', $barUser],
			]);

		$this->proxyMapper->expects($this->once())
			->method('getProxiesOf')
			->with('principals/users/foo')
			->willReturn([]);

		$this->proxyMapper->expects($this->once())
			->method('insert')
			->with($this->callback(function ($proxy) {
				/** @var Proxy $proxy */
				if ($proxy->getOwnerId() !== 'principals/users/foo') {
					return false;
				}
				if ($proxy->getProxyId() !== 'principals/users/bar') {
					return false;
				}
				if ($proxy->getPermissions() !== 3) {
					return false;
				}

				return true;
			}));

		$this->connector->setGroupMemberSet('principals/users/foo/calendar-proxy-write', ['principals/users/bar']);
	}

	public function testUpdatePrincipal(): void {
		$this->assertSame(0, $this->connector->updatePrincipal('foo', new PropPatch([])));
	}

	public function testSearchPrincipalsWithEmptySearchProperties(): void {
		$this->assertSame([], $this->connector->searchPrincipals('principals/users', []));
	}

	public function testSearchPrincipalsWithWrongPrefixPath(): void {
		$this->assertSame([], $this->connector->searchPrincipals('principals/groups',
			['{http://sabredav.org/ns}email-address' => 'foo']));
	}

	/**
	 * @dataProvider searchPrincipalsDataProvider
	 */
	public function testSearchPrincipals(bool $sharingEnabled, bool $groupsOnly, string $test, array $result): void {
		$this->shareManager->expects($this->once())
			->method('shareAPIEnabled')
			->willReturn($sharingEnabled);

		$getUserGroupIdsReturnMap = [];

		if ($sharingEnabled) {
			$this->shareManager->expects($this->once())
				->method('allowEnumeration')
				->willReturn(true);

			$this->shareManager->expects($this->once())
				->method('shareWithGroupMembersOnly')
				->willReturn($groupsOnly);

			if ($groupsOnly) {
				$user = $this->createMock(IUser::class);
				$this->userSession->expects($this->atLeastOnce())
					->method('getUser')
					->willReturn($user);

				$getUserGroupIdsReturnMap[] = [$user, ['group1', 'group2', 'group5']];
			}
		} else {
			$this->config->expects($this->never())
				->method('getAppValue');
			$this->shareManager->expects($this->never())
				->method('shareWithGroupMembersOnly');
			$this->groupManager->expects($this->never())
				->method($this->anything());
		}

		$user2 = $this->createMock(IUser::class);
		$user2->method('getUID')->willReturn('user2');
		$user3 = $this->createMock(IUser::class);
		$user3->method('getUID')->willReturn('user3');
		$user4 = $this->createMock(IUser::class);
		$user4->method('getUID')->willReturn('user4');

		if ($sharingEnabled) {
			$this->userManager->expects($this->once())
				->method('getByEmail')
				->with('user@example.com')
				->willReturn([$user2, $user3]);

			$this->userManager->expects($this->once())
				->method('searchDisplayName')
				->with('User 12')
				->willReturn([$user3, $user4]);
		} else {
			$this->userManager->expects($this->never())
				->method('getByEmail');

			$this->userManager->expects($this->never())
				->method('searchDisplayName');
		}

		if ($sharingEnabled && $groupsOnly) {
			$getUserGroupIdsReturnMap[] = [$user2, ['group1', 'group3']];
			$getUserGroupIdsReturnMap[] = [$user3, ['group3', 'group4']];
			$getUserGroupIdsReturnMap[] = [$user4, ['group4', 'group5']];
		}

		$this->groupManager->expects($this->any())
			->method('getUserGroupIds')
			->willReturnMap($getUserGroupIdsReturnMap);


		$this->assertEquals($result, $this->connector->searchPrincipals('principals/users',
			['{http://sabredav.org/ns}email-address' => 'user@example.com',
				'{DAV:}displayname' => 'User 12'], $test));
	}

	public static function searchPrincipalsDataProvider(): array {
		return [
			[true, false, 'allof', ['principals/users/user3']],
			[true, false, 'anyof', ['principals/users/user2', 'principals/users/user3', 'principals/users/user4']],
			[true, true, 'allof', []],
			[true, true, 'anyof', ['principals/users/user2', 'principals/users/user4']],
			[false, false, 'allof', []],
			[false, false, 'anyof', []],
		];
	}

	public function testSearchPrincipalByCalendarUserAddressSet(): void {
		$this->shareManager->expects($this->exactly(2))
			->method('shareAPIEnabled')
			->willReturn(true);

		$this->shareManager->expects($this->exactly(2))
			->method('allowEnumeration')
			->willReturn(true);

		$this->shareManager->expects($this->exactly(2))
			->method('shareWithGroupMembersOnly')
			->willReturn(false);

		$user2 = $this->createMock(IUser::class);
		$user2->method('getUID')->willReturn('user2');
		$user3 = $this->createMock(IUser::class);
		$user3->method('getUID')->willReturn('user3');

		$this->userManager->expects($this->once())
			->method('getByEmail')
			->with('user@example.com')
			->willReturn([$user2, $user3]);

		$this->assertEquals([
			'principals/users/user2',
			'principals/users/user3',
		], $this->connector->searchPrincipals('principals/users',
			['{urn:ietf:params:xml:ns:caldav}calendar-user-address-set' => 'user@example.com']));
	}

	public function testSearchPrincipalWithEnumerationDisabledDisplayname(): void {
		$this->shareManager->expects($this->once())
			->method('shareAPIEnabled')
			->willReturn(true);

		$this->shareManager->expects($this->once())
			->method('allowEnumeration')
			->willReturn(false);

		$this->shareManager->expects($this->once())
			->method('shareWithGroupMembersOnly')
			->willReturn(false);

		$this->shareManager->expects($this->once())
			->method('allowEnumerationFullMatch')
			->willReturn(true);

		$user2 = $this->createMock(IUser::class);
		$user2->method('getUID')->willReturn('user2');
		$user2->method('getDisplayName')->willReturn('User 2');
		$user2->method('getSystemEMailAddress')->willReturn('user2@foo.bar');
		$user3 = $this->createMock(IUser::class);
		$user3->method('getUID')->willReturn('user3');
		$user3->method('getDisplayName')->willReturn('User 22');
		$user3->method('getSystemEMailAddress')->willReturn('user2@foo.bar123');
		$user4 = $this->createMock(IUser::class);
		$user4->method('getUID')->willReturn('user4');
		$user4->method('getDisplayName')->willReturn('User 222');
		$user4->method('getSystemEMailAddress')->willReturn('user2@foo.bar456');

		$this->userManager->expects($this->once())
			->method('searchDisplayName')
			->with('User 2')
			->willReturn([$user2, $user3, $user4]);

		$this->assertEquals(['principals/users/user2'], $this->connector->searchPrincipals('principals/users',
			['{DAV:}displayname' => 'User 2']));
	}

	public function testSearchPrincipalWithEnumerationDisabledDisplaynameOnFullMatch(): void {
		$this->shareManager->expects($this->once())
			->method('shareAPIEnabled')
			->willReturn(true);

		$this->shareManager->expects($this->once())
			->method('allowEnumeration')
			->willReturn(false);

		$this->shareManager->expects($this->once())
			->method('shareWithGroupMembersOnly')
			->willReturn(false);

		$this->shareManager->expects($this->once())
			->method('allowEnumerationFullMatch')
			->willReturn(false);

		$this->assertEquals([], $this->connector->searchPrincipals('principals/users',
			['{DAV:}displayname' => 'User 2']));
	}

	public function testSearchPrincipalWithEnumerationDisabledEmail(): void {
		$this->shareManager->expects($this->once())
			->method('shareAPIEnabled')
			->willReturn(true);

		$this->shareManager->expects($this->once())
			->method('allowEnumeration')
			->willReturn(false);

		$this->shareManager->expects($this->once())
			->method('shareWithGroupMembersOnly')
			->willReturn(false);

		$this->shareManager->expects($this->once())
			->method('allowEnumerationFullMatch')
			->willReturn(true);

		$this->shareManager->expects($this->once())
			->method('matchEmail')
			->willReturn(true);

		$user2 = $this->createMock(IUser::class);
		$user2->method('getUID')->willReturn('user2');
		$user2->method('getDisplayName')->willReturn('User 2');
		$user2->method('getSystemEMailAddress')->willReturn('user2@foo.bar');
		$user3 = $this->createMock(IUser::class);
		$user3->method('getUID')->willReturn('user3');
		$user2->method('getDisplayName')->willReturn('User 22');
		$user2->method('getSystemEMailAddress')->willReturn('user2@foo.bar123');
		$user4 = $this->createMock(IUser::class);
		$user4->method('getUID')->willReturn('user4');
		$user2->method('getDisplayName')->willReturn('User 222');
		$user2->method('getSystemEMailAddress')->willReturn('user2@foo.bar456');

		$this->userManager->expects($this->once())
			->method('getByEmail')
			->with('user2@foo.bar')
			->willReturn([$user2]);

		$this->assertEquals(['principals/users/user2'], $this->connector->searchPrincipals('principals/users',
			['{http://sabredav.org/ns}email-address' => 'user2@foo.bar']));
	}

	public function testSearchPrincipalWithEnumerationDisabledEmailOnFullMatch(): void {
		$this->shareManager->expects($this->once())
			->method('shareAPIEnabled')
			->willReturn(true);

		$this->shareManager->expects($this->once())
			->method('allowEnumeration')
			->willReturn(false);

		$this->shareManager->expects($this->once())
			->method('shareWithGroupMembersOnly')
			->willReturn(false);

		$this->shareManager->expects($this->once())
			->method('allowEnumerationFullMatch')
			->willReturn(false);


		$this->assertEquals([], $this->connector->searchPrincipals('principals/users',
			['{http://sabredav.org/ns}email-address' => 'user2@foo.bar']));
	}

	public function testSearchPrincipalWithEnumerationLimitedDisplayname(): void {
		$this->shareManager->expects($this->once())
			->method('shareAPIEnabled')
			->willReturn(true);

		$this->shareManager->expects($this->once())
			->method('allowEnumeration')
			->willReturn(true);

		$this->shareManager->expects($this->once())
			->method('limitEnumerationToGroups')
			->willReturn(true);

		$this->shareManager->expects($this->once())
			->method('shareWithGroupMembersOnly')
			->willReturn(false);

		$user2 = $this->createMock(IUser::class);
		$user2->method('getUID')->willReturn('user2');
		$user2->method('getDisplayName')->willReturn('User 2');
		$user2->method('getSystemEMailAddress')->willReturn('user2@foo.bar');
		$user3 = $this->createMock(IUser::class);
		$user3->method('getUID')->willReturn('user3');
		$user3->method('getDisplayName')->willReturn('User 22');
		$user3->method('getSystemEMailAddress')->willReturn('user2@foo.bar123');
		$user4 = $this->createMock(IUser::class);
		$user4->method('getUID')->willReturn('user4');
		$user4->method('getDisplayName')->willReturn('User 222');
		$user4->method('getSystemEMailAddress')->willReturn('user2@foo.bar456');


		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn($user2);

		$this->groupManager->expects($this->exactly(4))
			->method('getUserGroupIds')
			->willReturnMap([
				[$user2, ['group1']],
				[$user3, ['group1']],
				[$user4, ['group2']],
			]);

		$this->userManager->expects($this->once())
			->method('searchDisplayName')
			->with('User')
			->willReturn([$user2, $user3, $user4]);


		$this->assertEquals([
			'principals/users/user2',
			'principals/users/user3',
		], $this->connector->searchPrincipals('principals/users',
			['{DAV:}displayname' => 'User']));
	}

	public function testSearchPrincipalWithEnumerationLimitedMail(): void {
		$this->shareManager->expects($this->once())
			->method('shareAPIEnabled')
			->willReturn(true);

		$this->shareManager->expects($this->once())
			->method('allowEnumeration')
			->willReturn(true);

		$this->shareManager->expects($this->once())
			->method('limitEnumerationToGroups')
			->willReturn(true);

		$this->shareManager->expects($this->once())
			->method('shareWithGroupMembersOnly')
			->willReturn(false);

		$user2 = $this->createMock(IUser::class);
		$user2->method('getUID')->willReturn('user2');
		$user2->method('getDisplayName')->willReturn('User 2');
		$user2->method('getSystemEMailAddress')->willReturn('user2@foo.bar');
		$user3 = $this->createMock(IUser::class);
		$user3->method('getUID')->willReturn('user3');
		$user3->method('getDisplayName')->willReturn('User 22');
		$user3->method('getSystemEMailAddress')->willReturn('user2@foo.bar123');
		$user4 = $this->createMock(IUser::class);
		$user4->method('getUID')->willReturn('user4');
		$user4->method('getDisplayName')->willReturn('User 222');
		$user4->method('getSystemEMailAddress')->willReturn('user2@foo.bar456');


		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn($user2);

		$this->groupManager->expects($this->exactly(4))
			->method('getUserGroupIds')
			->willReturnMap([
				[$user2, ['group1']],
				[$user3, ['group1']],
				[$user4, ['group2']],
			]);

		$this->userManager->expects($this->once())
			->method('getByEmail')
			->with('user')
			->willReturn([$user2, $user3, $user4]);


		$this->assertEquals([
			'principals/users/user2',
			'principals/users/user3'
		], $this->connector->searchPrincipals('principals/users',
			['{http://sabredav.org/ns}email-address' => 'user']));
	}

	public function testFindByUriSharingApiDisabled(): void {
		$this->shareManager->expects($this->once())
			->method('shareApiEnabled')
			->willReturn(false);

		$this->assertEquals(null, $this->connector->findByUri('mailto:user@foo.com', 'principals/users'));
	}

	/**
	 * @dataProvider findByUriWithGroupRestrictionDataProvider
	 */
	public function testFindByUriWithGroupRestriction(string $uri, string $email, ?string $expects): void {
		$this->shareManager->expects($this->once())
			->method('shareApiEnabled')
			->willReturn(true);

		$this->shareManager->expects($this->once())
			->method('shareWithGroupMembersOnly')
			->willReturn(true);

		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn($user);

		$user2 = $this->createMock(IUser::class);
		$user2->method('getUID')->willReturn('user2');
		$user3 = $this->createMock(IUser::class);
		$user3->method('getUID')->willReturn('user3');

		$this->userManager->expects($this->once())
			->method('getByEmail')
			->with($email)
			->willReturn([$email === 'user2@foo.bar' ? $user2 : $user3]);

		if ($email === 'user2@foo.bar') {
			$this->groupManager->expects($this->exactly(2))
				->method('getUserGroupIds')
				->willReturnMap([
					[$user, ['group1', 'group2']],
					[$user2, ['group1', 'group3']],
				]);
		} else {
			$this->groupManager->expects($this->exactly(2))
				->method('getUserGroupIds')
				->willReturnMap([
					[$user, ['group1', 'group2']],
					[$user3, ['group3', 'group3']],
				]);
		}

		$this->assertEquals($expects, $this->connector->findByUri($uri, 'principals/users'));
	}

	public static function findByUriWithGroupRestrictionDataProvider(): array {
		return [
			['mailto:user2@foo.bar', 'user2@foo.bar', 'principals/users/user2'],
			['mailto:user3@foo.bar', 'user3@foo.bar', null],
		];
	}

	/**
	 * @dataProvider findByUriWithoutGroupRestrictionDataProvider
	 */
	public function testFindByUriWithoutGroupRestriction(string $uri, string $email, string $expects): void {
		$this->shareManager->expects($this->once())
			->method('shareApiEnabled')
			->willReturn(true);

		$this->shareManager->expects($this->once())
			->method('shareWithGroupMembersOnly')
			->willReturn(false);

		$user2 = $this->createMock(IUser::class);
		$user2->method('getUID')->willReturn('user2');
		$user3 = $this->createMock(IUser::class);
		$user3->method('getUID')->willReturn('user3');

		$this->userManager->expects($this->once())
			->method('getByEmail')
			->with($email)
			->willReturn([$email === 'user2@foo.bar' ? $user2 : $user3]);

		$this->assertEquals($expects, $this->connector->findByUri($uri, 'principals/users'));
	}

	public static function findByUriWithoutGroupRestrictionDataProvider(): array {
		return [
			['mailto:user2@foo.bar', 'user2@foo.bar', 'principals/users/user2'],
			['mailto:user3@foo.bar', 'user3@foo.bar', 'principals/users/user3'],
		];
	}

	public function testGetEmailAddressesOfPrincipal(): void {
		$principal = [
			'{http://sabredav.org/ns}email-address' => 'bar@company.org',
			'{DAV:}alternate-URI-set' => [
				'/some/url',
				'mailto:foo@bar.com',
				'mailto:duplicate@example.com',
			],
			'{urn:ietf:params:xml:ns:caldav}calendar-user-address-set' => [
				'mailto:bernard@example.com',
				'mailto:bernard.desruisseaux@example.com',
			],
			'{http://calendarserver.org/ns/}email-address-set' => [
				'mailto:duplicate@example.com',
				'mailto:user@some.org',
			],
		];

		$expected = [
			'bar@company.org',
			'foo@bar.com',
			'duplicate@example.com',
			'bernard@example.com',
			'bernard.desruisseaux@example.com',
			'user@some.org',
		];
		$actual = $this->connector->getEmailAddressesOfPrincipal($principal);
		$this->assertEquals($expected, $actual);
	}
}
