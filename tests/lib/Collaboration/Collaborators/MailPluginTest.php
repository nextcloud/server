<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Collaboration\Collaborators;

use OC\Collaboration\Collaborators\MailPlugin;
use OC\Collaboration\Collaborators\SearchResult;
use OC\Federation\CloudIdManager;
use OC\KnownUser\KnownUserService;
use OCP\Collaboration\Collaborators\SearchResultType;
use OCP\Contacts\IManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Federation\ICloudIdManager;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Share\IShare;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;
use Test\Traits\EmailValidatorTrait;

class MailPluginTest extends TestCase {
	use EmailValidatorTrait;

	protected IConfig&MockObject $config;
	protected IManager&MockObject $contactsManager;
	protected ICloudIdManager $cloudIdManager;
	protected MailPlugin $plugin;
	protected SearchResult $searchResult;
	protected IGroupManager&MockObject $groupManager;
	protected KnownUserService&MockObject $knownUserService;
	protected IUserSession&MockObject $userSession;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->contactsManager = $this->createMock(IManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->knownUserService = $this->createMock(KnownUserService::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->cloudIdManager = new CloudIdManager(
			$this->createMock(ICacheFactory::class),
			$this->createMock(IEventDispatcher::class),
			$this->contactsManager,
			$this->createMock(IURLGenerator::class),
			$this->createMock(IUserManager::class),
		);

		$this->searchResult = new SearchResult();
	}

	public function instantiatePlugin(int $shareType) {
		$this->plugin = new MailPlugin(
			$this->contactsManager,
			$this->cloudIdManager,
			$this->config,
			$this->groupManager,
			$this->knownUserService,
			$this->userSession,
			$this->getEmailValidatorWithStrictEmailCheck(),
			[],
			$shareType,
		);
	}

	#[DataProvider('dataSearchEmail')]
	public function testSearchEmail(string $searchTerm, array $contacts, bool $shareeEnumeration, array $expectedResult, bool $expectedExactIdMatch, bool $expectedMoreResults, bool $validEmail): void {
		$this->config->expects($this->any())
			->method('getAppValue')
			->willReturnCallback(
				function ($appName, $key, $default) use ($shareeEnumeration) {
					if ($appName === 'core' && $key === 'shareapi_allow_share_dialog_user_enumeration') {
						return $shareeEnumeration ? 'yes' : 'no';
					}
					return $default;
				}
			);

		$this->instantiatePlugin(IShare::TYPE_EMAIL);

		$currentUser = $this->createMock(IUser::class);
		$currentUser->method('getUID')
			->willReturn('current');
		$this->userSession->method('getUser')
			->willReturn($currentUser);

		$this->contactsManager->expects($this->any())
			->method('search')
			->willReturnCallback(function ($search, $searchAttributes) use ($searchTerm, $contacts) {
				if ($search === $searchTerm) {
					return $contacts;
				}
				return [];
			});

		$moreResults = $this->plugin->search($searchTerm, 2, 0, $this->searchResult);
		$result = $this->searchResult->asArray();

		$this->assertSame($expectedExactIdMatch, $this->searchResult->hasExactIdMatch(new SearchResultType('emails')));
		$this->assertEquals($expectedResult, $result);
		$this->assertSame($expectedMoreResults, $moreResults);
	}

	public static function dataSearchEmail(): array {
		return [
			// data set 0
			['test', [], true, ['emails' => [], 'exact' => ['emails' => []]], false, false, false],
			// data set 1
			['test', [], false, ['emails' => [], 'exact' => ['emails' => []]], false, false, false],
			// data set 2
			[
				'test@remote.com',
				[],
				true,
				['emails' => [], 'exact' => ['emails' => [['uuid' => 'test@remote.com', 'label' => 'test@remote.com', 'value' => ['shareType' => IShare::TYPE_EMAIL, 'shareWith' => 'test@remote.com']]]]],
				false,
				false,
				true,
			],
			// data set 3
			[ // no valid email address
				'test@remote',
				[],
				true,
				['emails' => [], 'exact' => ['emails' => []]],
				false,
				false,
				false,
			],
			// data set 4
			[
				'test@remote.com',
				[],
				false,
				['emails' => [], 'exact' => ['emails' => [['uuid' => 'test@remote.com', 'label' => 'test@remote.com', 'value' => ['shareType' => IShare::TYPE_EMAIL, 'shareWith' => 'test@remote.com']]]]],
				false,
				false,
				true,
			],
			// data set 5
			[
				'test',
				[
					[
						'UID' => 'uid3',
						'FN' => 'User3 @ Localhost',
					],
					[
						'UID' => 'uid2',
						'FN' => 'User2 @ Localhost',
						'EMAIL' => [
						],
					],
					[
						'UID' => 'uid1',
						'FN' => 'User @ Localhost',
						'EMAIL' => [
							'username@example.com',
						],
					],
				],
				true,
				['emails' => [['uuid' => 'uid1', 'name' => 'User @ Localhost', 'type' => '', 'label' => 'User @ Localhost (username@example.com)', 'value' => ['shareType' => IShare::TYPE_EMAIL, 'shareWith' => 'username@example.com']]], 'exact' => ['emails' => []]],
				false,
				false,
				false,
			],
			// data set 6
			[
				'test',
				[
					[
						'UID' => 'uid3',
						'FN' => 'User3 @ Localhost',
					],
					[
						'UID' => 'uid2',
						'FN' => 'User2 @ Localhost',
						'EMAIL' => [
						],
					],
					[
						'isLocalSystemBook' => true,
						'UID' => 'uid1',
						'FN' => 'User @ Localhost',
						'EMAIL' => [
							'username@localhost',
						],
					],
				],
				false,
				['emails' => [], 'exact' => ['emails' => []]],
				false,
				false,
				false,
			],
			// data set 7
			[
				'test@remote.com',
				[
					[
						'UID' => 'uid3',
						'FN' => 'User3 @ example.com',
					],
					[
						'UID' => 'uid2',
						'FN' => 'User2 @ example.com',
						'EMAIL' => [
						],
					],
					[
						'UID' => 'uid1',
						'FN' => 'User @ example.com',
						'EMAIL' => [
							'username@example.com',
						],
					],
				],
				true,
				['emails' => [['uuid' => 'uid1', 'name' => 'User @ example.com', 'type' => '', 'label' => 'User @ example.com (username@example.com)', 'value' => ['shareType' => IShare::TYPE_EMAIL, 'shareWith' => 'username@example.com']]], 'exact' => ['emails' => [['label' => 'test@remote.com', 'uuid' => 'test@remote.com', 'value' => ['shareType' => IShare::TYPE_EMAIL, 'shareWith' => 'test@remote.com']]]]],
				false,
				false,
				true,
			],
			// data set 8
			[
				'test@remote.com',
				[
					[
						'UID' => 'uid3',
						'FN' => 'User3 @ Localhost',
					],
					[
						'UID' => 'uid2',
						'FN' => 'User2 @ Localhost',
						'EMAIL' => [
						],
					],
					[
						'isLocalSystemBook' => true,
						'UID' => 'uid1',
						'FN' => 'User @ Localhost',
						'EMAIL' => [
							'username@localhost',
						],
					],
				],
				false,
				['emails' => [], 'exact' => ['emails' => [['label' => 'test@remote.com', 'uuid' => 'test@remote.com', 'value' => ['shareType' => IShare::TYPE_EMAIL, 'shareWith' => 'test@remote.com']]]]],
				false,
				false,
				true,
			],
			// data set 9
			[
				'username@example.com',
				[
					[
						'UID' => 'uid3',
						'FN' => 'User3 @ example.com',
					],
					[
						'UID' => 'uid2',
						'FN' => 'User2 @ example.com',
						'EMAIL' => [
						],
					],
					[
						'UID' => 'uid1',
						'FN' => 'User @ example.com',
						'EMAIL' => [
							'username@example.com',
						],
					],
				],
				true,
				['emails' => [], 'exact' => ['emails' => [['name' => 'User @ example.com', 'uuid' => 'uid1', 'type' => '', 'label' => 'User @ example.com (username@example.com)', 'value' => ['shareType' => IShare::TYPE_EMAIL, 'shareWith' => 'username@example.com']]]]],
				true,
				false,
				false,
			],
			// data set 10
			[
				'username@example.com',
				[
					[
						'UID' => 'uid1',
						'FN' => 'User3 @ example.com',
					],
					[
						'UID' => 'uid2',
						'FN' => 'User2 @ example.com',
						'EMAIL' => [
						],
					],
					[
						'UID' => 'uid1',
						'FN' => 'User @ example.com',
						'EMAIL' => [
							'username@example.com',
						],
					],
				],
				false,
				['emails' => [], 'exact' => ['emails' => [['name' => 'User @ example.com', 'uuid' => 'uid1', 'type' => '', 'label' => 'User @ example.com (username@example.com)', 'value' => ['shareType' => IShare::TYPE_EMAIL, 'shareWith' => 'username@example.com']]]]],
				true,
				false,
				false,
			],
			// data set 11
			// contact with space
			[
				'user name@localhost',
				[
					[
						'UID' => 'uid3',
						'FN' => 'User3 @ Localhost',
					],
					[
						'UID' => 'uid2',
						'FN' => 'User2 @ Localhost',
						'EMAIL' => [
						],
					],
					[
						'UID' => 'uid1',
						'FN' => 'User Name @ Localhost',
						'EMAIL' => [
							'user name@localhost',
						],
					],
				],
				false,
				['emails' => [], 'exact' => ['emails' => []]],
				false,
				false,
				false,
			],
			// data set 12
			// remote with space, no contact
			[
				'user space@remote.com',
				[
					[
						'UID' => 'uid3',
						'FN' => 'User3 @ Localhost',
					],
					[
						'UID' => 'uid2',
						'FN' => 'User2 @ Localhost',
						'EMAIL' => [
						],
					],
					[
						'isLocalSystemBook' => true,
						'UID' => 'uid1',
						'FN' => 'User @ Localhost',
						'EMAIL' => [
							'username@localhost',
						],
					],
				],
				false,
				['emails' => [], 'exact' => ['emails' => []]],
				false,
				false,
				false,
			],
			// data set 13
			// Local user found by email => no result
			[
				'test@example.com',
				[
					[
						'UID' => 'uid1',
						'FN' => 'User',
						'EMAIL' => ['test@example.com'],
						'CLOUD' => ['test@localhost'],
						'isLocalSystemBook' => true,
					]
				],
				false,
				['exact' => []],
				false,
				false,
				true,
			],
			// data set 14
			// Current local user found by email => no result
			[
				'test@example.com',
				[
					[
						'UID' => 'uid1',
						'FN' => 'User',
						'EMAIL' => ['test@example.com'],
						'CLOUD' => ['current@localhost'],
						'isLocalSystemBook' => true,
					]
				],
				true,
				['exact' => []],
				false,
				false,
				true,
			],
			// data set 15
			// Several local users found by email => no result nor pagination
			[
				'test@example',
				[
					[
						'UID' => 'uid1',
						'FN' => 'User1',
						'EMAIL' => ['test@example.com'],
						'CLOUD' => ['test1@localhost'],
						'isLocalSystemBook' => true,
					],
					[
						'UID' => 'uid2',
						'FN' => 'User2',
						'EMAIL' => ['test@example.de'],
						'CLOUD' => ['test2@localhost'],
						'isLocalSystemBook' => true,
					],
					[
						'UID' => 'uid3',
						'FN' => 'User3',
						'EMAIL' => ['test@example.org'],
						'CLOUD' => ['test3@localhost'],
						'isLocalSystemBook' => true,
					],
					[
						'UID' => 'uid4',
						'FN' => 'User4',
						'EMAIL' => ['test@example.net'],
						'CLOUD' => ['test4@localhost'],
						'isLocalSystemBook' => true,
					],
				],
				true,
				['emails' => [], 'exact' => ['emails' => []]],
				false,
				false,
				false,
			],
			// data set 16
			// Pagination and "more results" for normal emails
			[
				'test@example',
				[
					[
						'UID' => 'uid1',
						'FN' => 'User1',
						'EMAIL' => ['test@example.com'],
						'CLOUD' => ['test1@localhost'],
					],
					[
						'UID' => 'uid2',
						'FN' => 'User2',
						'EMAIL' => ['test@example.de'],
						'CLOUD' => ['test2@localhost'],
					],
					[
						'UID' => 'uid3',
						'FN' => 'User3',
						'EMAIL' => ['test@example.org'],
						'CLOUD' => ['test3@localhost'],
					],
					[
						'UID' => 'uid4',
						'FN' => 'User4',
						'EMAIL' => ['test@example.net'],
						'CLOUD' => ['test4@localhost'],
					],
				],
				true,
				['emails' => [
					['uuid' => 'uid1', 'name' => 'User1', 'type' => '', 'label' => 'User1 (test@example.com)', 'value' => ['shareType' => IShare::TYPE_EMAIL, 'shareWith' => 'test@example.com']],
					['uuid' => 'uid2', 'name' => 'User2', 'type' => '', 'label' => 'User2 (test@example.de)', 'value' => ['shareType' => IShare::TYPE_EMAIL, 'shareWith' => 'test@example.de']],
				], 'exact' => ['emails' => []]],
				false,
				true,
				false,
			],
			// data set 17
			// multiple email addresses with type
			[
				'User Name',
				[
					[
						'UID' => 'uid3',
						'FN' => 'User3',
					],
					[
						'UID' => 'uid2',
						'FN' => 'User2',
						'EMAIL' => [
						],
					],
					[
						'UID' => 'uid1',
						'FN' => 'User Name',
						'EMAIL' => [
							['type' => 'HOME', 'value' => 'username@example.com'],
							['type' => 'WORK', 'value' => 'other@example.com'],
						],
					],
				],
				false,
				['emails' => [
				], 'exact' => ['emails' => [
					['name' => 'User Name', 'uuid' => 'uid1', 'type' => 'HOME', 'label' => 'User Name (username@example.com)', 'value' => ['shareType' => IShare::TYPE_EMAIL, 'shareWith' => 'username@example.com']],
					['name' => 'User Name', 'uuid' => 'uid1', 'type' => 'WORK', 'label' => 'User Name (other@example.com)', 'value' => ['shareType' => IShare::TYPE_EMAIL, 'shareWith' => 'other@example.com']]
				]]],
				false,
				false,
				false,
			],
			// data set 18
			// idn email
			[
				'test@lölölölölölölöl.com',
				[],
				true,
				['emails' => [], 'exact' => ['emails' => [['uuid' => 'test@lölölölölölölöl.com', 'label' => 'test@lölölölölölölöl.com', 'value' => ['shareType' => IShare::TYPE_EMAIL, 'shareWith' => 'test@lölölölölölölöl.com']]]]],
				false,
				false,
				true,
			],
		];
	}

	#[DataProvider('dataSearchUser')]
	public function testSearchUser(string $searchTerm, array $contacts, bool $shareeEnumeration, array $expectedResult, bool $expectedExactIdMatch, bool $expectedMoreResults): void {
		$this->config->expects($this->any())
			->method('getAppValue')
			->willReturnCallback(
				function ($appName, $key, $default) use ($shareeEnumeration) {
					if ($appName === 'core' && $key === 'shareapi_allow_share_dialog_user_enumeration') {
						return $shareeEnumeration ? 'yes' : 'no';
					}
					return $default;
				}
			);

		$this->instantiatePlugin(IShare::TYPE_USER);

		$currentUser = $this->createMock(IUser::class);
		$currentUser->method('getUID')
			->willReturn('current');
		$this->userSession->method('getUser')
			->willReturn($currentUser);

		$this->contactsManager->expects($this->any())
			->method('search')
			->willReturnCallback(function ($search, $searchAttributes) use ($searchTerm, $contacts) {
				if ($search === $searchTerm) {
					return $contacts;
				}
				return [];
			});

		$moreResults = $this->plugin->search($searchTerm, 2, 0, $this->searchResult);
		$result = $this->searchResult->asArray();

		$this->assertSame($expectedExactIdMatch, $this->searchResult->hasExactIdMatch(new SearchResultType('emails')));
		$this->assertEquals($expectedResult, $result);
		$this->assertSame($expectedMoreResults, $moreResults);
	}

	public static function dataSearchUser(): array {
		return [
			// data set 0
			['test', [], true, ['exact' => []], false, false],
			// data set 1
			['test', [], false, ['exact' => []], false, false],
			// data set 2
			[
				'test@remote.com',
				[],
				true,
				['exact' => []],
				false,
				false,
			],
			// data set 3
			[
				'test@remote.com',
				[],
				false,
				['exact' => []],
				false,
				false,
			],
			// data set 4
			[
				'test',
				[
					[
						'UID' => 'uid3',
						'FN' => 'User3 @ Localhost',
					],
					[
						'UID' => 'uid2',
						'FN' => 'User2 @ Localhost',
						'EMAIL' => [
						],
					],
					[
						'UID' => 'uid1',
						'FN' => 'User @ Localhost',
						'EMAIL' => [
							'username@localhost',
						],
					],
				],
				true,
				['exact' => []],
				false,
				false,
			],
			// data set 5
			[
				'test',
				[
					[
						'UID' => 'uid3',
						'FN' => 'User3 @ Localhost',
					],
					[
						'UID' => 'uid2',
						'FN' => 'User2 @ Localhost',
						'EMAIL' => [
						],
					],
					[
						'isLocalSystemBook' => true,
						'UID' => 'uid1',
						'FN' => 'User @ Localhost',
						'EMAIL' => [
							'username@localhost',
						],
					],
				],
				false,
				['exact' => []],
				false,
				false,
			],
			// data set 6
			[
				'test@remote.com',
				[
					[
						'UID' => 'uid3',
						'FN' => 'User3 @ Localhost',
					],
					[
						'UID' => 'uid2',
						'FN' => 'User2 @ Localhost',
						'EMAIL' => [
						],
					],
					[
						'UID' => 'uid1',
						'FN' => 'User @ Localhost',
						'EMAIL' => [
							'username@localhost',
						],
					],
				],
				true,
				['exact' => []],
				false,
				false,
			],
			// data set 7
			[
				'username@localhost',
				[
					[
						'UID' => 'uid3',
						'FN' => 'User3 @ Localhost',
					],
					[
						'UID' => 'uid2',
						'FN' => 'User2 @ Localhost',
						'EMAIL' => [
						],
					],
					[
						'UID' => 'uid1',
						'FN' => 'User @ Localhost',
						'EMAIL' => [
							'username@localhost',
						],
					],
				],
				true,
				['exact' => []],
				false,
				false,
			],
			// data set 8
			// Local user found by email
			[
				'test@example.com',
				[
					[
						'UID' => 'uid1',
						'FN' => 'User',
						'EMAIL' => ['test@example.com'],
						'CLOUD' => ['test@localhost'],
						'isLocalSystemBook' => true,
					]
				],
				false,
				['users' => [], 'exact' => ['users' => [['uuid' => 'uid1', 'name' => 'User', 'label' => 'User (test@example.com)','value' => ['shareType' => IShare::TYPE_USER, 'shareWith' => 'test'], 'shareWithDisplayNameUnique' => 'test@example.com']]]],
				true,
				false,
			],
			// data set 9
			// Current local user found by email => no result
			[
				'test@example.com',
				[
					[
						'UID' => 'uid1',
						'FN' => 'User',
						'EMAIL' => ['test@example.com'],
						'CLOUD' => ['current@localhost'],
						'isLocalSystemBook' => true,
					]
				],
				true,
				['exact' => []],
				false,
				false,
			],
			// data set 10
			// Pagination and "more results" for user matches by emails
			[
				'test@example',
				[
					[
						'UID' => 'uid1',
						'FN' => 'User1',
						'EMAIL' => ['test@example.com'],
						'CLOUD' => ['test1@localhost'],
						'isLocalSystemBook' => true,
					],
					[
						'UID' => 'uid2',
						'FN' => 'User2',
						'EMAIL' => ['test@example.de'],
						'CLOUD' => ['test2@localhost'],
						'isLocalSystemBook' => true,
					],
					[
						'UID' => 'uid3',
						'FN' => 'User3',
						'EMAIL' => ['test@example.org'],
						'CLOUD' => ['test3@localhost'],
						'isLocalSystemBook' => true,
					],
					[
						'UID' => 'uid4',
						'FN' => 'User4',
						'EMAIL' => ['test@example.net'],
						'CLOUD' => ['test4@localhost'],
						'isLocalSystemBook' => true,
					],
				],
				true,
				['users' => [
					['uuid' => 'uid1', 'name' => 'User1', 'label' => 'User1 (test@example.com)', 'value' => ['shareType' => IShare::TYPE_USER, 'shareWith' => 'test1'], 'shareWithDisplayNameUnique' => 'test@example.com'],
					['uuid' => 'uid2', 'name' => 'User2', 'label' => 'User2 (test@example.de)', 'value' => ['shareType' => IShare::TYPE_USER, 'shareWith' => 'test2'], 'shareWithDisplayNameUnique' => 'test@example.de'],
				], 'exact' => ['users' => []]],
				false,
				true,
			],
			// data set 11
			// Pagination and "more results" for normal emails
			[
				'test@example',
				[
					[
						'UID' => 'uid1',
						'FN' => 'User1',
						'EMAIL' => ['test@example.com'],
						'CLOUD' => ['test1@localhost'],
					],
					[
						'UID' => 'uid2',
						'FN' => 'User2',
						'EMAIL' => ['test@example.de'],
						'CLOUD' => ['test2@localhost'],
					],
					[
						'UID' => 'uid3',
						'FN' => 'User3',
						'EMAIL' => ['test@example.org'],
						'CLOUD' => ['test3@localhost'],
					],
					[
						'UID' => 'uid4',
						'FN' => 'User4',
						'EMAIL' => ['test@example.net'],
						'CLOUD' => ['test4@localhost'],
					],
				],
				true,
				['exact' => []],
				false,
				false,
			],
		];
	}

	#[DataProvider('dataSearchEmailGroupsOnly')]
	public function testSearchEmailGroupsOnly(string $searchTerm, array $contacts, array $expectedResult, bool $expectedExactIdMatch, bool $expectedMoreResults, array $userToGroupMapping, bool $validEmail): void {
		$this->config->expects($this->any())
			->method('getAppValue')
			->willReturnCallback(
				function ($appName, $key, $default) {
					if ($appName === 'core' && $key === 'shareapi_allow_share_dialog_user_enumeration') {
						return 'yes';
					} elseif ($appName === 'core' && $key === 'shareapi_only_share_with_group_members') {
						return 'yes';
					}
					return $default;
				}
			);

		$this->instantiatePlugin(IShare::TYPE_EMAIL);

		/** @var IUser|\PHPUnit\Framework\MockObject\MockObject */
		$currentUser = $this->createMock('\OCP\IUser');

		$currentUser->expects($this->any())
			->method('getUID')
			->willReturn('currentUser');

		$this->contactsManager->expects($this->any())
			->method('search')
			->willReturnCallback(function ($search, $searchAttributes) use ($searchTerm, $contacts) {
				if ($search === $searchTerm) {
					return $contacts;
				}
				return [];
			});

		$this->userSession->expects($this->any())
			->method('getUser')
			->willReturn($currentUser);

		$this->groupManager->expects($this->any())
			->method('getUserGroupIds')
			->willReturnCallback(function (IUser $user) use ($userToGroupMapping) {
				return $userToGroupMapping[$user->getUID()];
			});

		$this->groupManager->expects($this->any())
			->method('isInGroup')
			->willReturnCallback(function ($userId, $group) use ($userToGroupMapping) {
				return in_array($group, $userToGroupMapping[$userId]);
			});

		$moreResults = $this->plugin->search($searchTerm, 2, 0, $this->searchResult);
		$result = $this->searchResult->asArray();

		$this->assertSame($expectedExactIdMatch, $this->searchResult->hasExactIdMatch(new SearchResultType('emails')));
		$this->assertEquals($expectedResult, $result);
		$this->assertSame($expectedMoreResults, $moreResults);
	}

	public static function dataSearchEmailGroupsOnly(): array {
		return [
			// The user `User` can share with the current user
			[
				'test',
				[
					[
						'FN' => 'User',
						'EMAIL' => ['test@example.com'],
						'CLOUD' => ['test@localhost'],
						'isLocalSystemBook' => true,
						'UID' => 'User',
					]
				],
				['emails' => [], 'exact' => ['emails' => []]],
				false,
				false,
				[
					'currentUser' => ['group1'],
					'User' => ['group1'],
				],
				false,
			],
			// The user `User` cannot share with the current user
			[
				'test',
				[
					[
						'FN' => 'User',
						'EMAIL' => ['test@example.com'],
						'CLOUD' => ['test@localhost'],
						'isLocalSystemBook' => true,
						'UID' => 'User',
					]
				],
				['emails' => [], 'exact' => ['emails' => []]],
				false,
				false,
				[
					'currentUser' => ['group1'],
					'User' => ['group2'],
				],
				false,
			],
			// The user `User` cannot share with the current user, but there is an exact match on the e-mail address -> share by e-mail
			[
				'test@example.com',
				[
					[
						'FN' => 'User',
						'EMAIL' => ['test@example.com'],
						'CLOUD' => ['test@localhost'],
						'isLocalSystemBook' => true,
						'UID' => 'User',
					]
				],
				['emails' => [], 'exact' => ['emails' => [['label' => 'test@example.com', 'uuid' => 'test@example.com', 'value' => ['shareType' => IShare::TYPE_EMAIL,'shareWith' => 'test@example.com']]]]],
				false,
				false,
				[
					'currentUser' => ['group1'],
					'User' => ['group2'],
				],
				true,
			]
		];
	}

	#[DataProvider('dataSearchUserGroupsOnly')]
	public function testSearchUserGroupsOnly(string $searchTerm, array $contacts, array $expectedResult, bool $expectedExactIdMatch, bool $expectedMoreResults, array $userToGroupMapping): void {
		$this->config->expects($this->any())
			->method('getAppValue')
			->willReturnCallback(
				function ($appName, $key, $default) {
					if ($appName === 'core' && $key === 'shareapi_allow_share_dialog_user_enumeration') {
						return 'yes';
					} elseif ($appName === 'core' && $key === 'shareapi_only_share_with_group_members') {
						return 'yes';
					}
					return $default;
				}
			);

		$this->instantiatePlugin(IShare::TYPE_USER);

		$currentUser = $this->createMock(\OCP\IUser::class);

		$currentUser->expects($this->any())
			->method('getUID')
			->willReturn('currentUser');

		$this->contactsManager->expects($this->any())
			->method('search')
			->willReturnCallback(function ($search, $searchAttributes) use ($searchTerm, $contacts) {
				if ($search === $searchTerm) {
					return $contacts;
				}
				return [];
			});

		$this->userSession->expects($this->any())
			->method('getUser')
			->willReturn($currentUser);

		$this->groupManager->expects($this->any())
			->method('getUserGroupIds')
			->willReturnCallback(function (IUser $user) use ($userToGroupMapping) {
				return $userToGroupMapping[$user->getUID()];
			});

		$this->groupManager->expects($this->any())
			->method('isInGroup')
			->willReturnCallback(function ($userId, $group) use ($userToGroupMapping) {
				return in_array($group, $userToGroupMapping[$userId]);
			});

		$moreResults = $this->plugin->search($searchTerm, 2, 0, $this->searchResult);
		$result = $this->searchResult->asArray();

		$this->assertSame($expectedExactIdMatch, $this->searchResult->hasExactIdMatch(new SearchResultType('emails')));
		$this->assertEquals($expectedResult, $result);
		$this->assertSame($expectedMoreResults, $moreResults);
	}

	public static function dataSearchUserGroupsOnly(): array {
		return [
			// The user `User` can share with the current user
			[
				'test',
				[
					[
						'FN' => 'User',
						'EMAIL' => ['test@example.com'],
						'CLOUD' => ['test@localhost'],
						'isLocalSystemBook' => true,
						'UID' => 'User',
					]
				],
				['users' => [['label' => 'User (test@example.com)', 'uuid' => 'User', 'name' => 'User', 'value' => ['shareType' => IShare::TYPE_USER, 'shareWith' => 'test'],'shareWithDisplayNameUnique' => 'test@example.com',]], 'exact' => ['users' => []]],
				false,
				false,
				[
					'currentUser' => ['group1'],
					'User' => ['group1'],
				],
			],
			// The user `User` cannot share with the current user
			[
				'test',
				[
					[
						'FN' => 'User',
						'EMAIL' => ['test@example.com'],
						'CLOUD' => ['test@localhost'],
						'isLocalSystemBook' => true,
						'UID' => 'User',
					]
				],
				['exact' => []],
				false,
				false,
				[
					'currentUser' => ['group1'],
					'User' => ['group2'],
				],
			],
			// The user `User` cannot share with the current user, but there is an exact match on the e-mail address -> share by e-mail
			[
				'test@example.com',
				[
					[
						'FN' => 'User',
						'EMAIL' => ['test@example.com'],
						'CLOUD' => ['test@localhost'],
						'isLocalSystemBook' => true,
						'UID' => 'User',
					]
				],
				['exact' => []],
				false,
				false,
				[
					'currentUser' => ['group1'],
					'User' => ['group2'],
				],
			]
		];
	}
}
