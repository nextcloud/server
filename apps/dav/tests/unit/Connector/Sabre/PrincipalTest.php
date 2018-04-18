<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
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

	public function setUp() {
		$this->userManager = $this->createMock(IUserManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->shareManager = $this->createMock(IManager::class);
		$this->userSession = $this->createMock(IUserSession::class);

		$this->connector = new \OCA\DAV\Connector\Sabre\Principal(
			$this->userManager,
			$this->groupManager,
			$this->shareManager,
			$this->userSession);
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
				->will($this->returnValue('bar@owncloud.org'));
		$this->userManager
			->expects($this->once())
			->method('search')
			->with('')
			->will($this->returnValue([$fooUser, $barUser]));

		$expectedResponse = [
			0 => [
				'uri' => 'principals/users/foo',
				'{DAV:}displayname' => 'Dr. Foo-Bar'
			],
			1 => [
				'uri' => 'principals/users/bar',
				'{DAV:}displayname' => 'bar',
				'{http://sabredav.org/ns}email-address' => 'bar@owncloud.org'
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
			'{DAV:}displayname' => 'foo'
		];
		$response = $this->connector->getPrincipalByPath('principals/users/foo');
		$this->assertSame($expectedResponse, $response);
	}

	public function testGetPrincipalsByPathWithMail() {
		$fooUser = $this->createMock(User::class);
		$fooUser
				->expects($this->exactly(1))
				->method('getEMailAddress')
				->will($this->returnValue('foo@owncloud.org'));
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
			'{http://sabredav.org/ns}email-address' => 'foo@owncloud.org'
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

		$response = $this->connector->getGroupMemberSet('principals/users/foo');
		$this->assertSame(['principals/users/foo'], $response);
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

		$this->connector->getGroupMemberSet('principals/users/foo');
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
			->expects($this->once())
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

		$expectedResponse = [
			'principals/groups/group1',
			'principals/groups/foo%2Fbar',
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
	public function testSearchPrincipals($sharingEnabled, $groupsOnly, $result) {
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
					->will($this->returnValue(['group1', 'group2']));
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

		if ($sharingEnabled) {
			$this->userManager->expects($this->at(0))
				->method('getByEmail')
				->with('user')
				->will($this->returnValue([$user2, $user3]));
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
		}

		$this->assertEquals($result, $this->connector->searchPrincipals('principals/users',
			['{http://sabredav.org/ns}email-address' => 'user']));
	}

	public function searchPrincipalsDataProvider() {
		return [
			[true, false, ['principals/users/user2', 'principals/users/user3']],
			[true, true, ['principals/users/user2']],
			[false, false, []],
		];
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
