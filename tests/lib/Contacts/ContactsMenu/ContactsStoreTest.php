<?php

declare(strict_types=1);

/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright 2017 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author 2017 Lukas Reschke <lukas@statuscode.ch>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Tests\Contacts\ContactsMenu;

use OC\Contacts\ContactsMenu\ContactsStore;
use OC\KnownUser\KnownUserService;
use OC\Profile\ProfileManager;
use OCA\UserStatus\Db\UserStatus;
use OCA\UserStatus\Service\StatusService;
use OCP\Contacts\IManager;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory as IL10NFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ContactsStoreTest extends TestCase {
	private ContactsStore $contactsStore;
	private StatusService|MockObject $statusService;
	/** @var IManager|MockObject */
	private $contactsManager;
	/** @var ProfileManager */
	private $profileManager;
	/** @var IUserManager|MockObject */
	private $userManager;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var IGroupManager|MockObject */
	private $groupManager;
	/** @var IConfig|MockObject */
	private $config;
	/** @var KnownUserService|MockObject */
	private $knownUserService;
	/** @var IL10NFactory */
	private $l10nFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->contactsManager = $this->createMock(IManager::class);
		$this->statusService = $this->createMock(StatusService::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->profileManager = $this->createMock(ProfileManager::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->config = $this->createMock(IConfig::class);
		$this->knownUserService = $this->createMock(KnownUserService::class);
		$this->l10nFactory = $this->createMock(IL10NFactory::class);
		$this->contactsStore = new ContactsStore(
			$this->contactsManager,
			$this->statusService,
			$this->config,
			$this->profileManager,
			$this->userManager,
			$this->urlGenerator,
			$this->groupManager,
			$this->knownUserService,
			$this->l10nFactory,
		);
	}

	public function testGetContactsWithoutFilter() {
		/** @var IUser|MockObject $user */
		$user = $this->createMock(IUser::class);
		$this->contactsManager->expects($this->once())
			->method('search')
			->with($this->equalTo(''), $this->equalTo(['FN', 'EMAIL']))
			->willReturn([
				[
					'UID' => 123,
				],
				[
					'UID' => 567,
					'FN' => 'Darren Roner',
					'EMAIL' => [
						'darren@roner.au'
					],
				],
			]);
		$user->expects($this->exactly(2))
			->method('getUID')
			->willReturn('user123');

		$entries = $this->contactsStore->getContacts($user, '');

		$this->assertCount(2, $entries);
		$this->assertEquals([
			'darren@roner.au'
		], $entries[1]->getEMailAddresses());
	}

	public function testGetContactsHidesOwnEntry() {
		/** @var IUser|MockObject $user */
		$user = $this->createMock(IUser::class);
		$this->contactsManager->expects($this->once())
			->method('search')
			->with($this->equalTo(''), $this->equalTo(['FN', 'EMAIL']))
			->willReturn([
				[
					'UID' => 'user123',
				],
				[
					'UID' => 567,
					'FN' => 'Darren Roner',
					'EMAIL' => [
						'darren@roner.au'
					],
				],
			]);
		$user->expects($this->exactly(2))
			->method('getUID')
			->willReturn('user123');

		$entries = $this->contactsStore->getContacts($user, '');

		$this->assertCount(1, $entries);
	}

	public function testGetContactsWithoutBinaryImage() {
		/** @var IUser|MockObject $user */
		$user = $this->createMock(IUser::class);
		$this->urlGenerator->expects($this->any())
			->method('linkToRouteAbsolute')
			->with('core.GuestAvatar.getAvatar', $this->anything())
			->willReturn('https://urlToNcAvatar.test');
		$this->contactsManager->expects($this->once())
			->method('search')
			->with($this->equalTo(''), $this->equalTo(['FN', 'EMAIL']))
			->willReturn([
				[
					'UID' => 123,
				],
				[
					'UID' => 567,
					'FN' => 'Darren Roner',
					'EMAIL' => [
						'darren@roner.au'
					],
					'PHOTO' => base64_encode('photophotophoto'),
				],
			]);
		$user->expects($this->exactly(2))
			->method('getUID')
			->willReturn('user123');

		$entries = $this->contactsStore->getContacts($user, '');

		$this->assertCount(2, $entries);
		$this->assertSame('https://urlToNcAvatar.test', $entries[1]->getAvatar());
	}

	public function testGetContactsWithoutAvatarURI() {
		/** @var IUser|MockObject $user */
		$user = $this->createMock(IUser::class);
		$this->contactsManager->expects($this->once())
			->method('search')
			->with($this->equalTo(''), $this->equalTo(['FN', 'EMAIL']))
			->willReturn([
				[
					'UID' => 123,
				],
				[
					'UID' => 567,
					'FN' => 'Darren Roner',
					'EMAIL' => [
						'darren@roner.au'
					],
					'PHOTO' => 'VALUE=uri:https://photo',
				],
			]);
		$user->expects($this->exactly(2))
			->method('getUID')
			->willReturn('user123');

		$entries = $this->contactsStore->getContacts($user, '');

		$this->assertCount(2, $entries);
		$this->assertEquals('https://photo', $entries[1]->getAvatar());
	}

	public function testGetContactsWhenUserIsInExcludeGroups() {
		$this->config
			->method('getAppValue')
			->willReturnMap([
				['core', 'shareapi_allow_share_dialog_user_enumeration', 'yes', 'yes'],
				['core', 'shareapi_restrict_user_enumeration_to_group', 'no', 'no'],
				['core', 'shareapi_restrict_user_enumeration_to_phone', 'no', 'no'],
				['core', 'shareapi_exclude_groups', 'no', 'yes'],
				['core', 'shareapi_only_share_with_group_members', 'no', 'yes'],
				['core', 'shareapi_exclude_groups_list', '', '["group1", "group5", "group6"]'],
			]);

		/** @var IUser|MockObject $currentUser */
		$currentUser = $this->createMock(IUser::class);
		$currentUser->expects($this->exactly(2))
			->method('getUID')
			->willReturn('user001');

		$this->groupManager->expects($this->once())
			->method('getUserGroupIds')
			->with($this->equalTo($currentUser))
			->willReturn(['group1', 'group2', 'group3']);


		$this->contactsManager->expects($this->once())
			->method('search')
			->with($this->equalTo(''), $this->equalTo(['FN', 'EMAIL']))
			->willReturn([
				[
					'UID' => 'user123',
					'isLocalSystemBook' => true
				],
				[
					'UID' => 'user12345',
					'isLocalSystemBook' => true
				],
			]);


		$entries = $this->contactsStore->getContacts($currentUser, '');

		$this->assertCount(0, $entries);
	}

	public function testGetContactsOnlyShareIfInTheSameGroup() {
		$this->config
			->method('getAppValue')
			->willReturnMap([
				['core', 'shareapi_allow_share_dialog_user_enumeration', 'yes', 'yes'],
				['core', 'shareapi_restrict_user_enumeration_to_group', 'no', 'no'],
				['core', 'shareapi_restrict_user_enumeration_to_phone', 'no', 'no'],
				['core', 'shareapi_exclude_groups', 'no', 'no'],
				['core', 'shareapi_only_share_with_group_members', 'no', 'yes'],
			]);

		/** @var IUser|MockObject $currentUser */
		$currentUser = $this->createMock(IUser::class);
		$currentUser->expects($this->exactly(2))
			->method('getUID')
			->willReturn('user001');

		$user1 = $this->createMock(IUser::class);
		$user2 = $this->createMock(IUser::class);
		$user3 = $this->createMock(IUser::class);

		$this->groupManager->expects($this->exactly(4))
			->method('getUserGroupIds')
			->withConsecutive(
				[$this->equalTo($currentUser)],
				[$this->equalTo($user1)],
				[$this->equalTo($user2)],
				[$this->equalTo($user3)]
			)
			->willReturnOnConsecutiveCalls(
				['group1', 'group2', 'group3'],
				['group1'],
				['group2', 'group3'],
				['group8', 'group9']
			);

		$this->userManager->expects($this->exactly(3))
			->method('get')
			->withConsecutive(
				['user1'],
				['user2'],
				['user3']
			)
			->willReturnOnConsecutiveCalls($user1, $user2, $user3);

		$this->contactsManager->expects($this->once())
			->method('search')
			->with($this->equalTo(''), $this->equalTo(['FN', 'EMAIL']))
			->willReturn([
				[
					'UID' => 'user1',
					'isLocalSystemBook' => true
				],
				[
					'UID' => 'user2',
					'isLocalSystemBook' => true
				],
				[
					'UID' => 'user3',
					'isLocalSystemBook' => true
				],
				[
					'UID' => 'contact',
				],
			]);

		$entries = $this->contactsStore->getContacts($currentUser, '');

		$this->assertCount(3, $entries);
		$this->assertEquals('user1', $entries[0]->getProperty('UID'));
		$this->assertEquals('user2', $entries[1]->getProperty('UID'));
		$this->assertEquals('contact', $entries[2]->getProperty('UID'));
	}

	public function testGetContactsOnlyEnumerateIfInTheSameGroup() {
		$this->config
			->method('getAppValue')
			->willReturnMap([
				['core', 'shareapi_allow_share_dialog_user_enumeration', 'yes', 'yes'],
				['core', 'shareapi_restrict_user_enumeration_to_group', 'no', 'yes'],
				['core', 'shareapi_restrict_user_enumeration_to_phone', 'no', 'no'],
				['core', 'shareapi_exclude_groups', 'no', 'no'],
				['core', 'shareapi_only_share_with_group_members', 'no', 'yes'],
			]);

		/** @var IUser|MockObject $currentUser */
		$currentUser = $this->createMock(IUser::class);
		$currentUser->expects($this->exactly(2))
			->method('getUID')
			->willReturn('user001');

		$user1 = $this->createMock(IUser::class);
		$user2 = $this->createMock(IUser::class);
		$user3 = $this->createMock(IUser::class);

		$this->groupManager->expects($this->exactly(4))
			->method('getUserGroupIds')
			->withConsecutive(
				[$this->equalTo($currentUser)],
				[$this->equalTo($user1)],
				[$this->equalTo($user2)],
				[$this->equalTo($user3)]
			)
			->willReturnOnConsecutiveCalls(
				['group1', 'group2', 'group3'],
				['group1'],
				['group2', 'group3'],
				['group8', 'group9']
			);

		$this->userManager->expects($this->exactly(3))
			->method('get')
			->withConsecutive(
				['user1'],
				['user2'],
				['user3']
			)
			->willReturn($user1, $user2, $user3);

		$this->contactsManager->expects($this->once())
			->method('search')
			->with($this->equalTo(''), $this->equalTo(['FN', 'EMAIL']))
			->willReturn([
				[
					'UID' => 'user1',
					'isLocalSystemBook' => true
				],
				[
					'UID' => 'user2',
					'isLocalSystemBook' => true
				],
				[
					'UID' => 'user3',
					'isLocalSystemBook' => true
				],
				[
					'UID' => 'contact',
				],
			]);

		$entries = $this->contactsStore->getContacts($currentUser, '');

		$this->assertCount(3, $entries);
		$this->assertEquals('user1', $entries[0]->getProperty('UID'));
		$this->assertEquals('user2', $entries[1]->getProperty('UID'));
		$this->assertEquals('contact', $entries[2]->getProperty('UID'));
	}

	public function testGetContactsOnlyEnumerateIfPhoneBookMatch() {
		$this->config
			->method('getAppValue')
			->willReturnMap([
				['core', 'shareapi_allow_share_dialog_user_enumeration', 'yes', 'yes'],
				['core', 'shareapi_restrict_user_enumeration_to_group', 'no', 'no'],
				['core', 'shareapi_restrict_user_enumeration_to_phone', 'no', 'yes'],
				['core', 'shareapi_exclude_groups', 'no', 'no'],
				['core', 'shareapi_only_share_with_group_members', 'no', 'no'],
			]);

		/** @var IUser|MockObject $currentUser */
		$currentUser = $this->createMock(IUser::class);
		$currentUser->expects($this->exactly(2))
			->method('getUID')
			->willReturn('user001');

		$this->groupManager->expects($this->once())
			->method('getUserGroupIds')
			->with($this->equalTo($currentUser))
			->willReturn(['group1', 'group2', 'group3']);

		$this->knownUserService->method('isKnownToUser')
			->willReturnMap([
				['user001', 'user1', true],
				['user001', 'user2', true],
				['user001', 'user3', false],
			]);

		$this->contactsManager->expects($this->once())
			->method('search')
			->with($this->equalTo(''), $this->equalTo(['FN', 'EMAIL']))
			->willReturn([
				[
					'UID' => 'user1',
					'isLocalSystemBook' => true
				],
				[
					'UID' => 'user2',
					'isLocalSystemBook' => true
				],
				[
					'UID' => 'user3',
					'isLocalSystemBook' => true
				],
				[
					'UID' => 'contact',
				],
			]);

		$entries = $this->contactsStore->getContacts($currentUser, '');

		$this->assertCount(3, $entries);
		$this->assertEquals('user1', $entries[0]->getProperty('UID'));
		$this->assertEquals('user2', $entries[1]->getProperty('UID'));
		$this->assertEquals('contact', $entries[2]->getProperty('UID'));
	}

	public function testGetContactsOnlyEnumerateIfPhoneBookMatchWithOwnGroupsOnly() {
		$this->config
			->method('getAppValue')
			->willReturnMap([
				['core', 'shareapi_allow_share_dialog_user_enumeration', 'yes', 'yes'],
				['core', 'shareapi_restrict_user_enumeration_to_group', 'no', 'no'],
				['core', 'shareapi_restrict_user_enumeration_to_phone', 'no', 'yes'],
				['core', 'shareapi_exclude_groups', 'no', 'no'],
				['core', 'shareapi_only_share_with_group_members', 'no', 'yes'],
			]);

		/** @var IUser|MockObject $currentUser */
		$currentUser = $this->createMock(IUser::class);
		$currentUser->expects($this->exactly(2))
			->method('getUID')
			->willReturn('user001');

		$user1 = $this->createMock(IUser::class);
		$user2 = $this->createMock(IUser::class);
		$user3 = $this->createMock(IUser::class);

		$this->groupManager->expects($this->exactly(4))
			->method('getUserGroupIds')
			->withConsecutive(
				[$this->equalTo($currentUser)],
				[$this->equalTo($user1)],
				[$this->equalTo($user2)],
				[$this->equalTo($user3)]
			)
			->willReturnOnConsecutiveCalls(
				['group1', 'group2', 'group3'],
				['group1'],
				['group2', 'group3'],
				['group8', 'group9']
			);

		$this->userManager->expects($this->exactly(3))
			->method('get')
			->withConsecutive(
				['user1'],
				['user2'],
				['user3']
			)
			->willReturnOnConsecutiveCalls($user1, $user2, $user3);

		$this->knownUserService->method('isKnownToUser')
			->willReturnMap([
				['user001', 'user1', true],
				['user001', 'user2', true],
				['user001', 'user3', true],
			]);

		$this->contactsManager->expects($this->once())
			->method('search')
			->with($this->equalTo(''), $this->equalTo(['FN', 'EMAIL']))
			->willReturn([
				[
					'UID' => 'user1',
					'isLocalSystemBook' => true
				],
				[
					'UID' => 'user2',
					'isLocalSystemBook' => true
				],
				[
					'UID' => 'user3',
					'isLocalSystemBook' => true
				],
				[
					'UID' => 'contact',
				],
			]);

		$entries = $this->contactsStore->getContacts($currentUser, '');

		$this->assertCount(3, $entries);
		$this->assertEquals('user1', $entries[0]->getProperty('UID'));
		$this->assertEquals('user2', $entries[1]->getProperty('UID'));
		$this->assertEquals('contact', $entries[2]->getProperty('UID'));
	}

	public function testGetContactsOnlyEnumerateIfPhoneBookOrSameGroup() {
		$this->config
			->method('getAppValue')
			->willReturnMap([
				['core', 'shareapi_allow_share_dialog_user_enumeration', 'yes', 'yes'],
				['core', 'shareapi_restrict_user_enumeration_to_group', 'no', 'yes'],
				['core', 'shareapi_restrict_user_enumeration_to_phone', 'no', 'yes'],
				['core', 'shareapi_exclude_groups', 'no', 'no'],
				['core', 'shareapi_only_share_with_group_members', 'no', 'no'],
			]);

		/** @var IUser|MockObject $currentUser */
		$currentUser = $this->createMock(IUser::class);
		$currentUser->expects($this->exactly(2))
			->method('getUID')
			->willReturn('user001');

		$user1 = $this->createMock(IUser::class);

		$this->groupManager->expects($this->exactly(2))
			->method('getUserGroupIds')
			->withConsecutive(
				[$this->equalTo($currentUser)],
				[$this->equalTo($user1)]
			)
			->willReturnOnConsecutiveCalls(
				['group1', 'group2', 'group3'],
				['group1']
			);

		$this->userManager->expects($this->once())
			->method('get')
			->with('user1')
			->willReturn($user1);

		$this->knownUserService->method('isKnownToUser')
			->willReturnMap([
				['user001', 'user1', false],
				['user001', 'user2', true],
				['user001', 'user3', true],
			]);

		$this->contactsManager->expects($this->once())
			->method('search')
			->with($this->equalTo(''), $this->equalTo(['FN', 'EMAIL']))
			->willReturn([
				[
					'UID' => 'user1',
					'isLocalSystemBook' => true
				],
				[
					'UID' => 'user2',
					'isLocalSystemBook' => true
				],
				[
					'UID' => 'user3',
					'isLocalSystemBook' => true
				],
				[
					'UID' => 'contact',
				],
			]);

		$entries = $this->contactsStore->getContacts($currentUser, '');

		$this->assertCount(4, $entries);
		$this->assertEquals('user1', $entries[0]->getProperty('UID'));
		$this->assertEquals('user2', $entries[1]->getProperty('UID'));
		$this->assertEquals('user3', $entries[2]->getProperty('UID'));
		$this->assertEquals('contact', $entries[3]->getProperty('UID'));
	}

	public function testGetContactsOnlyEnumerateIfPhoneBookOrSameGroupInOwnGroupsOnly() {
		$this->config
			->method('getAppValue')
			->willReturnMap([
				['core', 'shareapi_allow_share_dialog_user_enumeration', 'yes', 'yes'],
				['core', 'shareapi_restrict_user_enumeration_to_group', 'no', 'yes'],
				['core', 'shareapi_restrict_user_enumeration_to_phone', 'no', 'yes'],
				['core', 'shareapi_exclude_groups', 'no', 'no'],
				['core', 'shareapi_only_share_with_group_members', 'no', 'yes'],
			]);

		/** @var IUser|MockObject $currentUser */
		$currentUser = $this->createMock(IUser::class);
		$currentUser->expects($this->exactly(2))
			->method('getUID')
			->willReturn('user001');

		$user1 = $this->createMock(IUser::class);
		$user2 = $this->createMock(IUser::class);
		$user3 = $this->createMock(IUser::class);

		$this->groupManager->expects($this->exactly(4))
			->method('getUserGroupIds')
			->withConsecutive(
				[$this->equalTo($currentUser)],
				[$this->equalTo($user1)],
				[$this->equalTo($user2)],
				[$this->equalTo($user3)]
			)
			->willReturnOnConsecutiveCalls(
				['group1', 'group2', 'group3'],
				['group1'],
				['group2', 'group3'],
				['group8', 'group9']
			);

		$this->userManager->expects($this->exactly(3))
			->method('get')
			->withConsecutive(
				['user1'],
				['user2'],
				['user3']
			)
			->willReturnOnConsecutiveCalls($user1, $user2, $user3);

		$this->knownUserService->method('isKnownToUser')
			->willReturnMap([
				['user001', 'user1', false],
				['user001', 'user2', true],
				['user001', 'user3', true],
			]);

		$this->contactsManager->expects($this->once())
			->method('search')
			->with($this->equalTo(''), $this->equalTo(['FN', 'EMAIL']))
			->willReturn([
				[
					'UID' => 'user1',
					'isLocalSystemBook' => true
				],
				[
					'UID' => 'user2',
					'isLocalSystemBook' => true
				],
				[
					'UID' => 'user3',
					'isLocalSystemBook' => true
				],
				[
					'UID' => 'contact',
				],
			]);

		$entries = $this->contactsStore->getContacts($currentUser, '');

		$this->assertCount(3, $entries);
		$this->assertEquals('user1', $entries[0]->getProperty('UID'));
		$this->assertEquals('user2', $entries[1]->getProperty('UID'));
		$this->assertEquals('contact', $entries[2]->getProperty('UID'));
	}

	public function testGetContactsWithFilter() {
		$this->config
			->method('getAppValue')
			->willReturnMap([
				['core', 'shareapi_allow_share_dialog_user_enumeration', 'yes', 'no'],
				['core', 'shareapi_restrict_user_enumeration_full_match', 'yes', 'yes'],
			]);

		/** @var IUser|MockObject $user */
		$user = $this->createMock(IUser::class);
		$this->contactsManager->expects($this->any())
			->method('search')
			->willReturn([
				[
					'UID' => 'a567',
					'FN' => 'Darren Roner',
					'EMAIL' => [
						'darren@roner.au',
					],
					'isLocalSystemBook' => true,
				],
				[
					'UID' => 'john',
					'FN' => 'John Doe',
					'EMAIL' => [
						'john@example.com',
					],
					'isLocalSystemBook' => true,
				],
				[
					'FN' => 'Anne D',
					'EMAIL' => [
						'anne@example.com',
					],
					'isLocalSystemBook' => false,
				],
			]);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('user123');

		// Complete match on UID should match
		$entry = $this->contactsStore->getContacts($user, 'a567');
		$this->assertSame(2, count($entry));
		$this->assertEquals([
			'darren@roner.au'
		], $entry[0]->getEMailAddresses());

		// Partial match on UID should not match
		$entry = $this->contactsStore->getContacts($user, 'a56');
		$this->assertSame(1, count($entry));
		$this->assertEquals([
			'anne@example.com'
		], $entry[0]->getEMailAddresses());

		// Complete match on email should match
		$entry = $this->contactsStore->getContacts($user, 'john@example.com');
		$this->assertSame(2, count($entry));
		$this->assertEquals([
			'john@example.com'
		], $entry[0]->getEMailAddresses());
		$this->assertEquals([
			'anne@example.com'
		], $entry[1]->getEMailAddresses());

		// Partial match on email should not match
		$entry = $this->contactsStore->getContacts($user, 'john@example.co');
		$this->assertSame(1, count($entry));
		$this->assertEquals([
			'anne@example.com'
		], $entry[0]->getEMailAddresses());

		// Match on FN should not match
		$entry = $this->contactsStore->getContacts($user, 'Darren Roner');
		$this->assertSame(1, count($entry));
		$this->assertEquals([
			'anne@example.com'
		], $entry[0]->getEMailAddresses());

		// Don't filter users in local addressbook
		$entry = $this->contactsStore->getContacts($user, 'Anne D');
		$this->assertSame(1, count($entry));
		$this->assertEquals([
			'anne@example.com'
		], $entry[0]->getEMailAddresses());
	}

	public function testGetContactsWithFilterWithoutFullMatch() {
		$this->config
			->method('getAppValue')
			->willReturnMap([
				['core', 'shareapi_allow_share_dialog_user_enumeration', 'yes', 'no'],
				['core', 'shareapi_restrict_user_enumeration_full_match', 'yes', 'no'],
			]);

		/** @var IUser|MockObject $user */
		$user = $this->createMock(IUser::class);
		$this->contactsManager->expects($this->any())
			->method('search')
			->willReturn([
				[
					'UID' => 'a567',
					'FN' => 'Darren Roner',
					'EMAIL' => [
						'darren@roner.au',
					],
					'isLocalSystemBook' => true,
				],
				[
					'UID' => 'john',
					'FN' => 'John Doe',
					'EMAIL' => [
						'john@example.com',
					],
					'isLocalSystemBook' => true,
				],
				[
					'FN' => 'Anne D',
					'EMAIL' => [
						'anne@example.com',
					],
					'isLocalSystemBook' => false,
				],
			]);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('user123');

		// Complete match on UID should not match
		$entry = $this->contactsStore->getContacts($user, 'a567');
		$this->assertSame(1, count($entry));
		$this->assertEquals([
			'anne@example.com'
		], $entry[0]->getEMailAddresses());

		// Partial match on UID should not match
		$entry = $this->contactsStore->getContacts($user, 'a56');
		$this->assertSame(1, count($entry));
		$this->assertEquals([
			'anne@example.com'
		], $entry[0]->getEMailAddresses());

		// Complete match on email should not match
		$entry = $this->contactsStore->getContacts($user, 'john@example.com');
		$this->assertSame(1, count($entry));
		$this->assertEquals([
			'anne@example.com'
		], $entry[0]->getEMailAddresses());

		// Partial match on email should not match
		$entry = $this->contactsStore->getContacts($user, 'john@example.co');
		$this->assertSame(1, count($entry));
		$this->assertEquals([
			'anne@example.com'
		], $entry[0]->getEMailAddresses());

		// Match on FN should not match
		$entry = $this->contactsStore->getContacts($user, 'Darren Roner');
		$this->assertSame(1, count($entry));
		$this->assertEquals([
			'anne@example.com'
		], $entry[0]->getEMailAddresses());

		// Don't filter users in local addressbook
		$entry = $this->contactsStore->getContacts($user, 'Anne D');
		$this->assertSame(1, count($entry));
		$this->assertEquals([
			'anne@example.com'
		], $entry[0]->getEMailAddresses());
	}

	public function testFindOneUser() {
		$this->config
			->method('getAppValue')
			->willReturnMap([
				['core', 'shareapi_allow_share_dialog_user_enumeration', 'yes', 'yes'],
				['core', 'shareapi_restrict_user_enumeration_to_group', 'no', 'no'],
				['core', 'shareapi_restrict_user_enumeration_to_phone', 'no', 'no'],
				['core', 'shareapi_restrict_user_enumeration_full_match', 'yes', 'yes'],
				['core', 'shareapi_exclude_groups', 'no', 'yes'],
				['core', 'shareapi_exclude_groups_list', '', ''],
				['core', 'shareapi_only_share_with_group_members', 'no', 'no'],
			]);

		/** @var IUser|MockObject $user */
		$user = $this->createMock(IUser::class);
		$this->contactsManager->expects($this->once())
			->method('search')
			->with($this->equalTo('a567'), $this->equalTo(['UID']))
			->willReturn([
				[
					'UID' => 123,
					'isLocalSystemBook' => false
				],
				[
					'UID' => 'a567',
					'FN' => 'Darren Roner',
					'EMAIL' => [
						'darren@roner.au'
					],
					'isLocalSystemBook' => true
				],
			]);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('user123');

		$entry = $this->contactsStore->findOne($user, 0, 'a567');

		$this->assertEquals([
			'darren@roner.au'
		], $entry->getEMailAddresses());
	}

	public function testFindOneEMail() {
		/** @var IUser|MockObject $user */
		$user = $this->createMock(IUser::class);
		$this->contactsManager->expects($this->once())
			->method('search')
			->with($this->equalTo('darren@roner.au'), $this->equalTo(['EMAIL']))
			->willReturn([
				[
					'UID' => 123,
					'isLocalSystemBook' => false
				],
				[
					'UID' => 'a567',
					'FN' => 'Darren Roner',
					'EMAIL' => [
						'darren@roner.au'
					],
					'isLocalSystemBook' => false
				],
			]);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('user123');

		$entry = $this->contactsStore->findOne($user, 4, 'darren@roner.au');

		$this->assertEquals([
			'darren@roner.au'
		], $entry->getEMailAddresses());
	}

	public function testFindOneNotSupportedType() {
		/** @var IUser|MockObject $user */
		$user = $this->createMock(IUser::class);

		$entry = $this->contactsStore->findOne($user, 42, 'darren@roner.au');

		$this->assertEquals(null, $entry);
	}

	public function testFindOneNoMatches() {
		/** @var IUser|MockObject $user */
		$user = $this->createMock(IUser::class);
		$this->contactsManager->expects($this->once())
			->method('search')
			->with($this->equalTo('a567'), $this->equalTo(['UID']))
			->willReturn([
				[
					'UID' => 123,
					'isLocalSystemBook' => false
				],
				[
					'UID' => 'a567',
					'FN' => 'Darren Roner',
					'EMAIL' => [
						'darren@roner.au123'
					],
					'isLocalSystemBook' => false
				],
			]);
		$user->expects($this->never())
			->method('getUID');

		$entry = $this->contactsStore->findOne($user, 0, 'a567');

		$this->assertEquals(null, $entry);
	}

	public function testGetRecentStatusFirst(): void {
		$user = $this->createMock(IUser::class);
		$status1 = new UserStatus();
		$status1->setUserId('user1');
		$status2 = new UserStatus();
		$status2->setUserId('user2');
		$this->statusService->expects(self::once())
			->method('findAllRecentStatusChanges')
			->willReturn([
				$status1,
				$status2,
			]);
		$user1 = $this->createMock(IUser::class);
		$user1->method('getCloudId')->willReturn('user1@localcloud');
		$user2 = $this->createMock(IUser::class);
		$user2->method('getCloudId')->willReturn('user2@localcloud');
		$this->userManager->expects(self::exactly(2))
			->method('get')
			->willReturnCallback(function ($uid) use ($user1, $user2) {
				return match ($uid) {
					'user1' => $user1,
					'user2' => $user2,
				};
			});
		$this->contactsManager
			->expects(self::exactly(3))
			->method('search')
			->willReturnCallback(function ($uid, $searchProps, $options) {
				return match ([$uid, $options['limit'] ?? null]) {
					['user1@localcloud', 1] => [
						[
							'UID' => 'user1',
							'URI' => 'user1.vcf',
						],
					],
					['user2@localcloud' => [], 1], // Simulate not found
					['', 4] => [
						[
							'UID' => 'contact1',
							'URI' => 'contact1.vcf',
						],
						[
							'UID' => 'contact2',
							'URI' => 'contact2.vcf',
						],
					],
					default => [],
				};
			});

		$contacts = $this->contactsStore->getContacts(
			$user,
			null,
			5,
		);

		self::assertCount(3, $contacts);
		self::assertEquals('user1', $contacts[0]->getProperty('UID'));
		self::assertEquals('contact1', $contacts[1]->getProperty('UID'));
		self::assertEquals('contact2', $contacts[2]->getProperty('UID'));
	}

	public function testPaginateRecentStatus(): void {
		$user = $this->createMock(IUser::class);
		$status1 = new UserStatus();
		$status1->setUserId('user1');
		$status2 = new UserStatus();
		$status2->setUserId('user2');
		$status3 = new UserStatus();
		$status3->setUserId('user3');
		$this->statusService->expects(self::never())
			->method('findAllRecentStatusChanges');
		$this->contactsManager
			->expects(self::exactly(2))
			->method('search')
			->willReturnCallback(function ($uid, $searchProps, $options) {
				return match ([$uid, $options['limit'] ?? null, $options['offset'] ?? null]) {
					['', 2, 0] => [
						[
							'UID' => 'contact1',
							'URI' => 'contact1.vcf',
						],
						[
							'UID' => 'contact2',
							'URI' => 'contact2.vcf',
						],
					],
					['', 2, 3] => [
						[
							'UID' => 'contact3',
							'URI' => 'contact3.vcf',
						],
					],
					default => [],
				};
			});

		$page1 = $this->contactsStore->getContacts(
			$user,
			null,
			2,
			0,
		);
		$page2 = $this->contactsStore->getContacts(
			$user,
			null,
			2,
			3,
		);

		self::assertCount(2, $page1);
		self::assertCount(1, $page2);
	}
}
