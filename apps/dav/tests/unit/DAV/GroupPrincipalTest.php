<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\DAV;

use OC\Group\Group;
use OCA\DAV\DAV\GroupPrincipalBackend;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Share\IManager;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\PropPatch;

class GroupPrincipalTest extends \Test\TestCase {
	private IConfig&MockObject $config;
	private IGroupManager&MockObject $groupManager;
	private IUserSession&MockObject $userSession;
	private IManager&MockObject $shareManager;
	private GroupPrincipalBackend $connector;

	protected function setUp(): void {
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->shareManager = $this->createMock(IManager::class);
		$this->config = $this->createMock(IConfig::class);

		$this->connector = new GroupPrincipalBackend(
			$this->groupManager,
			$this->userSession,
			$this->shareManager,
			$this->config
		);
		parent::setUp();
	}

	public function testGetPrincipalsByPrefixWithoutPrefix(): void {
		$response = $this->connector->getPrincipalsByPrefix('');
		$this->assertSame([], $response);
	}

	public function testGetPrincipalsByPrefixWithUsers(): void {
		$group1 = $this->mockGroup('foo');
		$group2 = $this->mockGroup('bar');
		$this->groupManager
			->expects($this->once())
			->method('search')
			->with('')
			->willReturn([$group1, $group2]);

		$expectedResponse = [
			0 => [
				'uri' => 'principals/groups/foo',
				'{DAV:}displayname' => 'Group foo',
				'{urn:ietf:params:xml:ns:caldav}calendar-user-type' => 'GROUP',
			],
			1 => [
				'uri' => 'principals/groups/bar',
				'{DAV:}displayname' => 'Group bar',
				'{urn:ietf:params:xml:ns:caldav}calendar-user-type' => 'GROUP',
			]
		];
		$response = $this->connector->getPrincipalsByPrefix('principals/groups');
		$this->assertSame($expectedResponse, $response);
	}

	public function testGetPrincipalsByPrefixEmpty(): void {
		$this->groupManager
			->expects($this->once())
			->method('search')
			->with('')
			->willReturn([]);

		$response = $this->connector->getPrincipalsByPrefix('principals/groups');
		$this->assertSame([], $response);
	}

	public function testGetPrincipalsByPathWithoutMail(): void {
		$group1 = $this->mockGroup('foo');
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('foo')
			->willReturn($group1);

		$expectedResponse = [
			'uri' => 'principals/groups/foo',
			'{DAV:}displayname' => 'Group foo',
			'{urn:ietf:params:xml:ns:caldav}calendar-user-type' => 'GROUP',
		];
		$response = $this->connector->getPrincipalByPath('principals/groups/foo');
		$this->assertSame($expectedResponse, $response);
	}

	public function testGetPrincipalsByPathWithMail(): void {
		$fooUser = $this->mockGroup('foo');
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('foo')
			->willReturn($fooUser);

		$expectedResponse = [
			'uri' => 'principals/groups/foo',
			'{DAV:}displayname' => 'Group foo',
			'{urn:ietf:params:xml:ns:caldav}calendar-user-type' => 'GROUP',
		];
		$response = $this->connector->getPrincipalByPath('principals/groups/foo');
		$this->assertSame($expectedResponse, $response);
	}

	public function testGetPrincipalsByPathEmpty(): void {
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('foo')
			->willReturn(null);

		$response = $this->connector->getPrincipalByPath('principals/groups/foo');
		$this->assertSame(null, $response);
	}

	public function testGetPrincipalsByPathGroupWithSlash(): void {
		$group1 = $this->mockGroup('foo/bar');
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('foo/bar')
			->willReturn($group1);

		$expectedResponse = [
			'uri' => 'principals/groups/foo%2Fbar',
			'{DAV:}displayname' => 'Group foo/bar',
			'{urn:ietf:params:xml:ns:caldav}calendar-user-type' => 'GROUP',
		];
		$response = $this->connector->getPrincipalByPath('principals/groups/foo/bar');
		$this->assertSame($expectedResponse, $response);
	}

	public function testGetPrincipalsByPathGroupWithHash(): void {
		$group1 = $this->mockGroup('foo#bar');
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('foo#bar')
			->willReturn($group1);

		$expectedResponse = [
			'uri' => 'principals/groups/foo%23bar',
			'{DAV:}displayname' => 'Group foo#bar',
			'{urn:ietf:params:xml:ns:caldav}calendar-user-type' => 'GROUP',
		];
		$response = $this->connector->getPrincipalByPath('principals/groups/foo#bar');
		$this->assertSame($expectedResponse, $response);
	}

	public function testGetGroupMemberSet(): void {
		$response = $this->connector->getGroupMemberSet('principals/groups/foo');
		$this->assertSame([], $response);
	}

	public function testGetGroupMembership(): void {
		$response = $this->connector->getGroupMembership('principals/groups/foo');
		$this->assertSame([], $response);
	}


	public function testSetGroupMembership(): void {
		$this->expectException(\Sabre\DAV\Exception::class);
		$this->expectExceptionMessage('Setting members of the group is not supported yet');

		$this->connector->setGroupMemberSet('principals/groups/foo', ['foo']);
	}

	public function testUpdatePrincipal(): void {
		$this->assertSame(0, $this->connector->updatePrincipal('foo', new PropPatch([])));
	}

	public function testSearchPrincipalsWithEmptySearchProperties(): void {
		$this->assertSame([], $this->connector->searchPrincipals('principals/groups', []));
	}

	public function testSearchPrincipalsWithWrongPrefixPath(): void {
		$this->assertSame([], $this->connector->searchPrincipals('principals/users',
			['{DAV:}displayname' => 'Foo']));
	}

	/**
	 * @dataProvider searchPrincipalsDataProvider
	 */
	public function testSearchPrincipals(bool $sharingEnabled, bool $groupSharingEnabled, bool $groupsOnly, string $test, array $result): void {
		$this->shareManager->expects($this->once())
			->method('shareAPIEnabled')
			->willReturn($sharingEnabled);

		$this->shareManager->expects($sharingEnabled ? $this->once() : $this->never())
			->method('allowGroupSharing')
			->willReturn($groupSharingEnabled);

		if ($sharingEnabled && $groupSharingEnabled) {
			$this->shareManager->expects($this->once())
				->method('shareWithGroupMembersOnly')
				->willReturn($groupsOnly);

			if ($groupsOnly) {
				$user = $this->createMock(IUser::class);
				$this->userSession->expects($this->once())
					->method('getUser')
					->willReturn($user);

				$this->groupManager->expects($this->once())
					->method('getUserGroupIds')
					->with($user)
					->willReturn(['group1', 'group2', 'group5']);
			}
		} else {
			$this->shareManager->expects($this->never())
				->method('shareWithGroupMembersOnly');
			$this->groupManager->expects($this->never())
				->method($this->anything());
		}

		$group1 = $this->createMock(IGroup::class);
		$group1->method('getGID')->willReturn('group1');
		$group2 = $this->createMock(IGroup::class);
		$group2->method('getGID')->willReturn('group2');
		$group3 = $this->createMock(IGroup::class);
		$group3->method('getGID')->willReturn('group3');
		$group4 = $this->createMock(IGroup::class);
		$group4->method('getGID')->willReturn('group4');
		$group5 = $this->createMock(IGroup::class);
		$group5->method('getGID')->willReturn('group5');

		if ($sharingEnabled && $groupSharingEnabled) {
			$this->groupManager->expects($this->once())
				->method('search')
				->with('Foo')
				->willReturn([$group1, $group2, $group3, $group4, $group5]);
		} else {
			$this->groupManager->expects($this->never())
				->method('search');
		}

		$this->assertSame($result, $this->connector->searchPrincipals('principals/groups',
			['{DAV:}displayname' => 'Foo'], $test));
	}

	public static function searchPrincipalsDataProvider(): array {
		return [
			[true, true, false, 'allof', ['principals/groups/group1', 'principals/groups/group2', 'principals/groups/group3', 'principals/groups/group4', 'principals/groups/group5']],
			[true, true, false, 'anyof', ['principals/groups/group1', 'principals/groups/group2', 'principals/groups/group3', 'principals/groups/group4', 'principals/groups/group5']],
			[true, true, true, 'allof', ['principals/groups/group1', 'principals/groups/group2', 'principals/groups/group5']],
			[true, true, true, 'anyof', ['principals/groups/group1', 'principals/groups/group2', 'principals/groups/group5']],
			[true, false, false, 'allof', []],
			[false, true, false, 'anyof', []],
			[false, false, false, 'allof', []],
			[false, false, false, 'anyof', []],
		];
	}

	/**
	 * @dataProvider findByUriDataProvider
	 */
	public function testFindByUri(bool $sharingEnabled, bool $groupSharingEnabled, bool $groupsOnly, string $findUri, ?string $result): void {
		$this->shareManager->expects($this->once())
			->method('shareAPIEnabled')
			->willReturn($sharingEnabled);

		$this->shareManager->expects($sharingEnabled ? $this->once() : $this->never())
			->method('allowGroupSharing')
			->willReturn($groupSharingEnabled);

		if ($sharingEnabled && $groupSharingEnabled) {
			$this->shareManager->expects($this->once())
				->method('shareWithGroupMembersOnly')
				->willReturn($groupsOnly);

			if ($groupsOnly) {
				$user = $this->createMock(IUser::class);
				$this->userSession->expects($this->once())
					->method('getUser')
					->willReturn($user);

				$this->groupManager->expects($this->once())
					->method('getUserGroupIds')
					->with($user)
					->willReturn(['group1', 'group2', 'group5']);
			}
		} else {
			$this->shareManager->expects($this->never())
				->method('shareWithGroupMembersOnly');
			$this->groupManager->expects($this->never())
				->method($this->anything());
		}

		$this->assertEquals($result, $this->connector->findByUri($findUri, 'principals/groups'));
	}

	public static function findByUriDataProvider(): array {
		return [
			[false, false, false, 'principal:principals/groups/group1', null],
			[false, false, false, 'principal:principals/groups/group3', null],
			[false, true, false, 'principal:principals/groups/group1', null],
			[false, true, false, 'principal:principals/groups/group3', null],
			[false, false, true, 'principal:principals/groups/group1', null],
			[false, false, true, 'principal:principals/groups/group3', null],
			[true, false, true, 'principal:principals/groups/group1', null],
			[true, false, true, 'principal:principals/groups/group3', null],
			[true, true, true, 'principal:principals/groups/group1', 'principals/groups/group1'],
			[true, true, true, 'principal:principals/groups/group3', null],
			[true, true, false, 'principal:principals/groups/group1', 'principals/groups/group1'],
			[true, true, false, 'principal:principals/groups/group3', 'principals/groups/group3'],
		];
	}

	private function mockGroup(string $gid): Group&MockObject {
		$fooGroup = $this->createMock(Group::class);
		$fooGroup
			->expects($this->exactly(1))
			->method('getGID')
			->willReturn($gid);
		$fooGroup
			->expects($this->exactly(1))
			->method('getDisplayName')
			->willReturn('Group ' . $gid);
		return $fooGroup;
	}
}
