<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @copyright Copyright (c) 2018, Georg Ehrke
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
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

namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OC\User\User;
use OCA\DAV\CalDAV\Proxy\Proxy;
use OCA\DAV\CalDAV\Proxy\ProxyMapper;
use OCP\App\IAppManager;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Share\IManager;
use \Sabre\DAV\PropPatch;
use OCP\IUserManager;
use Test\TestCase;

class PrincipalTest extends TestCase {

	/** @var IUserManager | \PHPUnit_Framework_MockObject_MockObject */
	private $userManager;

	/** @var \OCA\DAV\Connector\Sabre\Principal */
	private $connector;

	/** @var IGroupManager | \PHPUnit_Framework_MockObject_MockObject */
	private $groupManager;

	/** @var IManager | \PHPUnit_Framework_MockObject_MockObject */
	private $shareManager;

	/** @var IUserSession | \PHPUnit_Framework_MockObject_MockObject */
	private $userSession;

	/** @var IAppManager | \PHPUnit_Framework_MockObject_MockObject  */
	private $appManager;

	/** @var ProxyMapper | \PHPUnit_Framework_MockObject_MockObject */
	private $proxyMapper;

	public function setUp() {
		$this->userManager = $this->createMock(IUserManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->shareManager = $this->createMock(IManager::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->proxyMapper = $this->createMock(ProxyMapper::class);

		$this->connector = new \OCA\DAV\Connector\Sabre\Principal(
			$this->userManager,
			$this->groupManager,
			$this->shareManager,
			$this->userSession,
			$this->appManager,
			$this->proxyMapper
		);
		parent::setUp();
	}

	public function testGetPrincipalsByPrefixWithoutPrefix() {
		$response = $this->connector->getPrincipalsByPrefix('');
		$this->assertSame([], $response);
	}

	public function testGetPrincipalsByPrefixWithUsers() {
		$fooUser = $this->createMock(User::class);
		$fooUser
				->expects($this->exactly(1))
				->method('getUID')
				->will($this->returnValue('foo'));
		$fooUser
				->expects($this->exactly(1))
				->method('getDisplayName')
				->will($this->returnValue('Dr. Foo-Bar'));
		$fooUser
				->expects($this->exactly(1))
				->method('getEMailAddress')
				->will($this->returnValue(''));
		$barUser = $this->createMock(User::class);
		$barUser
			->expects($this->exactly(1))
			->method('getUID')
			->will($this->returnValue('bar'));
		$barUser
				->expects($this->exactly(1))
				->method('getEMailAddress')
				->will($this->returnValue('bar@nextcloud.com'));
		$this->userManager
			->expects($this->once())
			->method('search')
			->with('')
			->will($this->returnValue([$fooUser, $barUser]));

		$expectedResponse = [
			0 => [
				'uri' => 'principals/users/foo',
				'{DAV:}displayname' => 'Dr. Foo-Bar',
				'{urn:ietf:params:xml:ns:caldav}calendar-user-type' => 'INDIVIDUAL',
			],
			1 => [
				'uri' => 'principals/users/bar',
				'{DAV:}displayname' => 'bar',
				'{urn:ietf:params:xml:ns:caldav}calendar-user-type' => 'INDIVIDUAL',
				'{http://sabredav.org/ns}email-address' => 'bar@nextcloud.com',
			]
		];
		$response = $this->connector->getPrincipalsByPrefix('principals/users');
		$this->assertSame($expectedResponse, $response);
	}

	public function testGetPrincipalsByPrefixEmpty() {
		$this->userManager
			->expects($this->once())
			->method('search')
			->with('')
			->will($this->returnValue([]));

		$response = $this->connector->getPrincipalsByPrefix('principals/users');
		$this->assertSame([], $response);
	}

	public function testGetPrincipalsByPathWithoutMail() {
		$fooUser = $this->createMock(User::class);
		$fooUser
			->expects($this->exactly(1))
			->method('getUID')
			->will($this->returnValue('foo'));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('foo')
			->will($this->returnValue($fooUser));

		$expectedResponse = [
			'uri' => 'principals/users/foo',
			'{DAV:}displayname' => 'foo',
			'{urn:ietf:params:xml:ns:caldav}calendar-user-type' => 'INDIVIDUAL',
		];
		$response = $this->connector->getPrincipalByPath('principals/users/foo');
		$this->assertSame($expectedResponse, $response);
	}

	public function testGetPrincipalsByPathWithMail() {
		$fooUser = $this->createMock(User::class);
		$fooUser
				->expects($this->exactly(1))
				->method('getEMailAddress')
				->will($this->returnValue('foo@nextcloud.com'));
		$fooUser
				->expects($this->exactly(1))
				->method('getUID')
				->will($this->returnValue('foo'));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('foo')
			->will($this->returnValue($fooUser));

		$expectedResponse = [
			'uri' => 'principals/users/foo',
			'{DAV:}displayname' => 'foo',
			'{urn:ietf:params:xml:ns:caldav}calendar-user-type' => 'INDIVIDUAL',
			'{http://sabredav.org/ns}email-address' => 'foo@nextcloud.com',
		];
		$response = $this->connector->getPrincipalByPath('principals/users/foo');
		$this->assertSame($expectedResponse, $response);
	}

	public function testGetPrincipalsByPathEmpty() {
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('foo')
			->will($this->returnValue(null));

		$response = $this->connector->getPrincipalByPath('principals/users/foo');
		$this->assertSame(null, $response);
	}

	public function testGetGroupMemberSet() {
		$response = $this->connector->getGroupMemberSet('principals/users/foo');
		$this->assertSame([], $response);
	}

	/**
	 * @expectedException \Sabre\DAV\Exception
	 * @expectedExceptionMessage Principal not found
	 */
	public function testGetGroupMemberSetEmpty() {
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('foo')
			->will($this->returnValue(null));

		$this->connector->getGroupMemberSet('principals/users/foo/calendar-proxy-read');
	}

	public function testGetGroupMemberSetProxyRead() {
		$fooUser = $this->createMock(User::class);
		$fooUser
			->expects($this->exactly(1))
			->method('getUID')
			->will($this->returnValue('foo'));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('foo')
			->will($this->returnValue($fooUser));

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

	public function testGetGroupMemberSetProxyWrite() {
		$fooUser = $this->createMock(User::class);
		$fooUser
			->expects($this->exactly(1))
			->method('getUID')
			->will($this->returnValue('foo'));
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('foo')
			->will($this->returnValue($fooUser));

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

	public function testGetGroupMembership() {
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

	/**
	 * @expectedException \Sabre\DAV\Exception
	 * @expectedExceptionMessage Principal not found
	 */
	public function testGetGroupMembershipEmpty() {
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('foo')
			->will($this->returnValue(null));

		$this->connector->getGroupMembership('principals/users/foo');
	}

	/**
	 * @expectedException \Sabre\DAV\Exception
	 * @expectedExceptionMessage Setting members of the group is not supported yet
	 */
	public function testSetGroupMembership() {
		$this->connector->setGroupMemberSet('principals/users/foo', ['foo']);
	}

	public function testSetGroupMembershipProxy() {
		$fooUser = $this->createMock(User::class);
		$fooUser
			->expects($this->exactly(1))
			->method('getUID')
			->will($this->returnValue('foo'));
		$barUser = $this->createMock(User::class);
		$barUser
			->expects($this->exactly(1))
			->method('getUID')
			->will($this->returnValue('bar'));
		$this->userManager
			->expects($this->at(0))
			->method('get')
			->with('foo')
			->will($this->returnValue($fooUser));
		$this->userManager
			->expects($this->at(1))
			->method('get')
			->with('bar')
			->will($this->returnValue($barUser));

		$this->proxyMapper->expects($this->at(0))
			->method('getProxiesOf')
			->with('principals/users/foo')
			->willReturn([]);

		$this->proxyMapper->expects($this->at(1))
			->method('insert')
			->with($this->callback(function($proxy) {
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






	public function testUpdatePrincipal() {
		$this->assertSame(0, $this->connector->updatePrincipal('foo', new PropPatch(array())));
	}

	public function testSearchPrincipalsWithEmptySearchProperties() {
		$this->assertSame([], $this->connector->searchPrincipals('principals/users', []));
	}

	public function testSearchPrincipalsWithWrongPrefixPath() {
		$this->assertSame([], $this->connector->searchPrincipals('principals/groups',
			['{http://sabredav.org/ns}email-address' => 'foo']));
	}

	/**
	 * @dataProvider searchPrincipalsDataProvider
	 */
	public function testSearchPrincipals($sharingEnabled, $groupsOnly, $test, $result) {
		$this->shareManager->expects($this->once())
			->method('shareAPIEnabled')
			->will($this->returnValue($sharingEnabled));

		if ($sharingEnabled) {
			$this->shareManager->expects($this->once())
				->method('shareWithGroupMembersOnly')
				->will($this->returnValue($groupsOnly));

			if ($groupsOnly) {
				$user = $this->createMock(IUser::class);
				$this->userSession->expects($this->once())
					->method('getUser')
					->will($this->returnValue($user));

				$this->groupManager->expects($this->at(0))
					->method('getUserGroupIds')
					->with($user)
					->will($this->returnValue(['group1', 'group2', 'group5']));
			}
		} else {
			$this->shareManager->expects($this->never())
				->method('shareWithGroupMembersOnly');
			$this->groupManager->expects($this->never())
				->method($this->anything());
		}

		$user2 = $this->createMock(IUser::class);
		$user2->method('getUID')->will($this->returnValue('user2'));
		$user3 = $this->createMock(IUser::class);
		$user3->method('getUID')->will($this->returnValue('user3'));
		$user4 = $this->createMock(IUser::class);
		$user4->method('getUID')->will($this->returnValue('user4'));

		if ($sharingEnabled) {
			$this->userManager->expects($this->at(0))
				->method('getByEmail')
				->with('user@example.com')
				->will($this->returnValue([$user2, $user3]));

			$this->userManager->expects($this->at(1))
				->method('searchDisplayName')
				->with('User 12')
				->will($this->returnValue([$user3, $user4]));
		} else {
			$this->userManager->expects($this->never())
				->method('getByEmail');

			$this->userManager->expects($this->never())
				->method('searchDisplayName');
		}

		if ($sharingEnabled && $groupsOnly) {
			$this->groupManager->expects($this->at(1))
				->method('getUserGroupIds')
				->with($user2)
				->will($this->returnValue(['group1', 'group3']));
			$this->groupManager->expects($this->at(2))
				->method('getUserGroupIds')
				->with($user3)
				->will($this->returnValue(['group3', 'group4']));
			$this->groupManager->expects($this->at(3))
				->method('getUserGroupIds')
				->with($user3)
				->will($this->returnValue(['group3', 'group4']));
			$this->groupManager->expects($this->at(4))
				->method('getUserGroupIds')
				->with($user4)
				->will($this->returnValue(['group4', 'group5']));
		}


		$this->assertEquals($result, $this->connector->searchPrincipals('principals/users',
			['{http://sabredav.org/ns}email-address' => 'user@example.com',
				'{DAV:}displayname' => 'User 12'], $test));
	}

	public function searchPrincipalsDataProvider() {
		return [
			[true, false, 'allof', ['principals/users/user3']],
			[true, false, 'anyof', ['principals/users/user2', 'principals/users/user3', 'principals/users/user4']],
			[true, true, 'allof', []],
			[true, true, 'anyof', ['principals/users/user2', 'principals/users/user4']],
			[false, false, 'allof', []],
			[false, false, 'anyof', []],
		];
	}

	public function testSearchPrincipalByCalendarUserAddressSet() {
		$this->shareManager->expects($this->exactly(2))
			->method('shareAPIEnabled')
			->will($this->returnValue(true));

		$this->shareManager->expects($this->exactly(2))
			->method('shareWithGroupMembersOnly')
			->will($this->returnValue(false));

		$user2 = $this->createMock(IUser::class);
		$user2->method('getUID')->will($this->returnValue('user2'));
		$user3 = $this->createMock(IUser::class);
		$user3->method('getUID')->will($this->returnValue('user3'));

		$this->userManager->expects($this->at(0))
			->method('getByEmail')
			->with('user@example.com')
			->will($this->returnValue([$user2, $user3]));

		$this->assertEquals([
				'principals/users/user2',
				'principals/users/user3',
			], $this->connector->searchPrincipals('principals/users',
			['{urn:ietf:params:xml:ns:caldav}calendar-user-address-set' => 'user@example.com']));
	}

	public function testFindByUriSharingApiDisabled() {
		$this->shareManager->expects($this->once())
			->method('shareApiEnabled')
			->will($this->returnValue(false));

		$this->assertEquals(null, $this->connector->findByUri('mailto:user@foo.com', 'principals/users'));
	}

	/**
	 * @dataProvider findByUriWithGroupRestrictionDataProvider
	 */
	public function testFindByUriWithGroupRestriction($uri, $email, $expects) {
		$this->shareManager->expects($this->once())
			->method('shareApiEnabled')
			->will($this->returnValue(true));

			$this->shareManager->expects($this->once())
				->method('shareWithGroupMembersOnly')
				->will($this->returnValue(true));

			$user = $this->createMock(IUser::class);
			$this->userSession->expects($this->once())
				->method('getUser')
				->will($this->returnValue($user));

			$this->groupManager->expects($this->at(0))
				->method('getUserGroupIds')
				->with($user)
				->will($this->returnValue(['group1', 'group2']));

			$user2 = $this->createMock(IUser::class);
			$user2->method('getUID')->will($this->returnValue('user2'));
			$user3 = $this->createMock(IUser::class);
			$user3->method('getUID')->will($this->returnValue('user3'));

			$this->userManager->expects($this->once())
				->method('getByEmail')
				->with($email)
				->will($this->returnValue([$email === 'user2@foo.bar' ? $user2 : $user3]));

			if ($email === 'user2@foo.bar') {
				$this->groupManager->expects($this->at(1))
					->method('getUserGroupIds')
					->with($user2)
					->will($this->returnValue(['group1', 'group3']));
			} else {
				$this->groupManager->expects($this->at(1))
					->method('getUserGroupIds')
					->with($user3)
					->will($this->returnValue(['group3', 'group3']));
			}

		$this->assertEquals($expects, $this->connector->findByUri($uri, 'principals/users'));
	}

	public function findByUriWithGroupRestrictionDataProvider() {
		return [
			['mailto:user2@foo.bar', 'user2@foo.bar', 'principals/users/user2'],
			['mailto:user3@foo.bar', 'user3@foo.bar', null],
		];
	}

	/**
	 * @dataProvider findByUriWithoutGroupRestrictionDataProvider
	 */
	public function testFindByUriWithoutGroupRestriction($uri, $email, $expects) {
		$this->shareManager->expects($this->once())
			->method('shareApiEnabled')
			->will($this->returnValue(true));

			$this->shareManager->expects($this->once())
				->method('shareWithGroupMembersOnly')
				->will($this->returnValue(false));

			$user2 = $this->createMock(IUser::class);
			$user2->method('getUID')->will($this->returnValue('user2'));
			$user3 = $this->createMock(IUser::class);
			$user3->method('getUID')->will($this->returnValue('user3'));

			$this->userManager->expects($this->once())
				->method('getByEmail')
				->with($email)
				->will($this->returnValue([$email === 'user2@foo.bar' ? $user2 : $user3]));

		$this->assertEquals($expects, $this->connector->findByUri($uri, 'principals/users'));
	}

	public function findByUriWithoutGroupRestrictionDataProvider() {
		return [
			['mailto:user2@foo.bar', 'user2@foo.bar', 'principals/users/user2'],
			['mailto:user3@foo.bar', 'user3@foo.bar', 'principals/users/user3'],
		];
	}
}
