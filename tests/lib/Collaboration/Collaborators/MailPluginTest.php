<?php
/**
 * @copyright Copyright (c) 2017 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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

namespace Test\Collaboration\Collaborators;


use OC\Collaboration\Collaborators\MailPlugin;
use OC\Collaboration\Collaborators\SearchResult;
use OC\Federation\CloudIdManager;
use OCP\Collaboration\Collaborators\SearchResultType;
use OCP\Contacts\IManager;
use OCP\Federation\ICloudIdManager;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUserSession;
use OCP\Share;
use Test\TestCase;

class MailPluginTest extends TestCase {
	/** @var  IConfig|\PHPUnit_Framework_MockObject_MockObject */
	protected $config;

	/** @var  IManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $contactsManager;

	/** @var  ICloudIdManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $cloudIdManager;

	/** @var  MailPlugin */
	protected $plugin;

	/** @var  SearchResult */
	protected $searchResult;

	/** @var  IGroupManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $groupManager;

	/** @var  IUserSession|\PHPUnit_Framework_MockObject_MockObject */
	protected $userSession;

	public function setUp() {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->contactsManager = $this->createMock(IManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->cloudIdManager = new CloudIdManager();
		$this->searchResult = new SearchResult();
	}

	public function instantiatePlugin() {
		$this->plugin = new MailPlugin($this->contactsManager, $this->cloudIdManager, $this->config, $this->groupManager, $this->userSession);
	}

	/**
	 * @dataProvider dataGetEmail
	 *
	 * @param string $searchTerm
	 * @param array $contacts
	 * @param bool $shareeEnumeration
	 * @param array $expected
	 * @param bool $reachedEnd
	 */
	public function testSearch($searchTerm, $contacts, $shareeEnumeration, $expected, $exactIdMatch, $reachedEnd) {
		$this->config->expects($this->any())
			->method('getAppValue')
			->willReturnCallback(
				function($appName, $key, $default)
				use ($shareeEnumeration)
				{
					if ($appName === 'core' && $key === 'shareapi_allow_share_dialog_user_enumeration') {
						return $shareeEnumeration ? 'yes' : 'no';
					}
					return $default;
				}
			);

		$this->instantiatePlugin();

		$this->contactsManager->expects($this->any())
			->method('search')
			->with($searchTerm, ['EMAIL', 'FN'])
			->willReturn($contacts);

		$moreResults = $this->plugin->search($searchTerm, 2, 0, $this->searchResult);
		$result = $this->searchResult->asArray();

		$this->assertSame($exactIdMatch, $this->searchResult->hasExactIdMatch(new SearchResultType('emails')));
		$this->assertEquals($expected, $result);
		$this->assertSame($reachedEnd, $moreResults);
	}

	public function dataGetEmail() {
		return [
			['test', [], true, ['emails' => [], 'exact' => ['emails' => []]], false, true],
			['test', [], false, ['emails' => [], 'exact' => ['emails' => []]], false, true],
			[
				'test@remote.com',
				[],
				true,
				['emails' => [], 'exact' => ['emails' => [['label' => 'test@remote.com', 'value' => ['shareType' => Share::SHARE_TYPE_EMAIL, 'shareWith' => 'test@remote.com']]]]],
				false,
				true,
			],
			[ // no valid email address
				'test@remote',
				[],
				true,
				['emails' => [], 'exact' => ['emails' => []]],
				false,
				true,
			],
			[
				'test@remote.com',
				[],
				false,
				['emails' => [], 'exact' => ['emails' => [['label' => 'test@remote.com', 'value' => ['shareType' => Share::SHARE_TYPE_EMAIL, 'shareWith' => 'test@remote.com']]]]],
				false,
				true,
			],
			[
				'test',
				[
					[
						'FN' => 'User3 @ Localhost',
					],
					[
						'FN' => 'User2 @ Localhost',
						'EMAIL' => [
						],
					],
					[
						'FN' => 'User @ Localhost',
						'EMAIL' => [
							'username@localhost',
						],
					],
				],
				true,
				['emails' => [['label' => 'User @ Localhost (username@localhost)', 'value' => ['shareType' => Share::SHARE_TYPE_EMAIL, 'shareWith' => 'username@localhost']]], 'exact' => ['emails' => []]],
				false,
				true,
			],
			[
				'test',
				[
					[
						'FN' => 'User3 @ Localhost',
					],
					[
						'FN' => 'User2 @ Localhost',
						'EMAIL' => [
						],
					],
					[
						'FN' => 'User @ Localhost',
						'EMAIL' => [
							'username@localhost',
						],
					],
				],
				false,
				['emails' => [], 'exact' => ['emails' => []]],
				false,
				true,
			],
			[
				'test@remote.com',
				[
					[
						'FN' => 'User3 @ Localhost',
					],
					[
						'FN' => 'User2 @ Localhost',
						'EMAIL' => [
						],
					],
					[
						'FN' => 'User @ Localhost',
						'EMAIL' => [
							'username@localhost',
						],
					],
				],
				true,
				['emails' => [['label' => 'User @ Localhost (username@localhost)', 'value' => ['shareType' => Share::SHARE_TYPE_EMAIL, 'shareWith' => 'username@localhost']]], 'exact' => ['emails' => [['label' => 'test@remote.com', 'value' => ['shareType' => Share::SHARE_TYPE_EMAIL, 'shareWith' => 'test@remote.com']]]]],
				false,
				true,
			],
			[
				'test@remote.com',
				[
					[
						'FN' => 'User3 @ Localhost',
					],
					[
						'FN' => 'User2 @ Localhost',
						'EMAIL' => [
						],
					],
					[
						'FN' => 'User @ Localhost',
						'EMAIL' => [
							'username@localhost',
						],
					],
				],
				false,
				['emails' => [], 'exact' => ['emails' => [['label' => 'test@remote.com', 'value' => ['shareType' => Share::SHARE_TYPE_EMAIL, 'shareWith' => 'test@remote.com']]]]],
				false,
				true,
			],
			[
				'username@localhost',
				[
					[
						'FN' => 'User3 @ Localhost',
					],
					[
						'FN' => 'User2 @ Localhost',
						'EMAIL' => [
						],
					],
					[
						'FN' => 'User @ Localhost',
						'EMAIL' => [
							'username@localhost',
						],
					],
				],
				true,
				['emails' => [], 'exact' => ['emails' => [['label' => 'User @ Localhost (username@localhost)', 'value' => ['shareType' => Share::SHARE_TYPE_EMAIL, 'shareWith' => 'username@localhost']]]]],
				true,
				true,
			],
			[
				'username@localhost',
				[
					[
						'FN' => 'User3 @ Localhost',
					],
					[
						'FN' => 'User2 @ Localhost',
						'EMAIL' => [
						],
					],
					[
						'FN' => 'User @ Localhost',
						'EMAIL' => [
							'username@localhost',
						],
					],
				],
				false,
				['emails' => [], 'exact' => ['emails' => [['label' => 'User @ Localhost (username@localhost)', 'value' => ['shareType' => Share::SHARE_TYPE_EMAIL, 'shareWith' => 'username@localhost']]]]],
				true,
				true,
			],
			// contact with space
			[
				'user name@localhost',
				[
					[
						'FN' => 'User3 @ Localhost',
					],
					[
						'FN' => 'User2 @ Localhost',
						'EMAIL' => [
						],
					],
					[
						'FN' => 'User Name @ Localhost',
						'EMAIL' => [
							'user name@localhost',
						],
					],
				],
				false,
				['emails' => [], 'exact' => ['emails' => [['label' => 'User Name @ Localhost (user name@localhost)', 'value' => ['shareType' => Share::SHARE_TYPE_EMAIL, 'shareWith' => 'user name@localhost']]]]],
				true,
				true,
			],
			// remote with space, no contact
			[
				'user space@remote.com',
				[
					[
						'FN' => 'User3 @ Localhost',
					],
					[
						'FN' => 'User2 @ Localhost',
						'EMAIL' => [
						],
					],
					[
						'FN' => 'User @ Localhost',
						'EMAIL' => [
							'username@localhost',
						],
					],
				],
				false,
				['emails' => [], 'exact' => ['emails' => []]],
				false,
				true,
			],
			// Local user found by email
			[
				'test@example.com',
				[
					[
						'FN' => 'User',
						'EMAIL' => ['test@example.com'],
						'CLOUD' => ['test@localhost'],
						'isLocalSystemBook' => true,
					]
				],
				false,
				['users' => [], 'exact' => ['users' => [['label' => 'User (test@example.com)','value' => ['shareType' => 0, 'shareWith' => 'test'],]]]],
				true,
				false,
			]
		];
	}

	/**
	 * @dataProvider dataGetEmailGroupsOnly
	 *
	 * @param string $searchTerm
	 * @param array $contacts
	 * @param array $expected
	 * @param bool $exactIdMatch
	 * @param bool $reachedEnd
	 * @param array groups
	 */
	public function testSearchGroupsOnly($searchTerm, $contacts, $expected, $exactIdMatch, $reachedEnd, $userToGroupMapping) {
		$this->config->expects($this->any())
			->method('getAppValue')
			->willReturnCallback(
				function($appName, $key, $default) {
					if ($appName === 'core' && $key === 'shareapi_allow_share_dialog_user_enumeration') {
						return 'yes';
					} else if ($appName === 'core' && $key === 'shareapi_only_share_with_group_members') {
						return 'yes';
					}
					return $default;
				}
			);

		$this->instantiatePlugin();

		/** @var \OCP\IUser | \PHPUnit_Framework_MockObject_MockObject */
		$currentUser = $this->createMock('\OCP\IUser');

		$currentUser->expects($this->any())
			->method('getUID')
			->willReturn('currentUser');

		$this->contactsManager->expects($this->any())
			->method('search')
			->with($searchTerm, ['EMAIL', 'FN'])
			->willReturn($contacts);

		$this->userSession->expects($this->any())
			->method('getUser')
			->willReturn($currentUser);

		$this->groupManager->expects($this->any())
			->method('getUserGroupIds')
			->willReturnCallback(function(\OCP\IUser $user) use ($userToGroupMapping) {
				return $userToGroupMapping[$user->getUID()];
			});

		$this->groupManager->expects($this->any())
			->method('isInGroup')
			->willReturnCallback(function($userId, $group) use ($userToGroupMapping) {
				return in_array($group, $userToGroupMapping[$userId]);
			});

		$moreResults = $this->plugin->search($searchTerm, 2, 0, $this->searchResult);
		$result = $this->searchResult->asArray();

		$this->assertSame($exactIdMatch, $this->searchResult->hasExactIdMatch(new SearchResultType('emails')));
		$this->assertEquals($expected, $result);
		$this->assertSame($reachedEnd, $moreResults);
	}

	public function dataGetEmailGroupsOnly() {
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
						'UID' => 'User'
					]
				],
				['users' => [['label' => 'User (test@example.com)','value' => ['shareType' => 0, 'shareWith' => 'test'],]], 'emails' => [], 'exact' => ['emails' => [], 'users' => []]],
				false,
				true,
				[
					"currentUser" => ["group1"],
					"User" => ["group1"]
				]
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
						'UID' => 'User'
					]
				],
				['emails'=> [], 'exact' => ['emails' => []]],
				false,
				true,
				[
					"currentUser" => ["group1"],
					"User" => ["group2"]
				]
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
						'UID' => 'User'
					]
				],
				['emails' => [], 'exact' => ['emails' => [['label' => 'test@example.com', 'value' => ['shareType' => 4,'shareWith' => 'test@example.com']]]]],
				false,
				true,
				[
					"currentUser" => ["group1"],
					"User" => ["group2"]
				]
			]
		];
	}
}
