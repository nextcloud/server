<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Collaboration\Collaborators;

use OC\Collaboration\Collaborators\RemotePlugin;
use OC\Collaboration\Collaborators\SearchResult;
use OC\Federation\CloudIdManager;
use OCA\Federation\TrustedServers;
use OCP\Collaboration\Collaborators\SearchResultType;
use OCP\Contacts\IManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Federation\ICloudIdManager;
use OCP\IAppConfig;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class RemotePluginTest extends TestCase {
	/** @var IUserManager|\PHPUnit\Framework\MockObject\MockObject */
	protected $userManager;

	/** @var IConfig|\PHPUnit\Framework\MockObject\MockObject */
	protected $config;

	/** @var IManager|\PHPUnit\Framework\MockObject\MockObject */
	protected $contactsManager;

	/** @var ICloudIdManager|\PHPUnit\Framework\MockObject\MockObject */
	protected $cloudIdManager;

	protected IAppConfig|MockObject $appConfig;
	protected ICloudIdManager|MockObject $trustedServers;

	/** @var RemotePlugin */
	protected $plugin;

	/** @var SearchResult */
	protected $searchResult;

	protected function setUp(): void {
		parent::setUp();

		$this->userManager = $this->createMock(IUserManager::class);
		$this->config = $this->createMock(IConfig::class);
		$this->contactsManager = $this->createMock(IManager::class);
		$this->cloudIdManager = new CloudIdManager(
			$this->createMock(ICacheFactory::class),
			$this->createMock(IEventDispatcher::class),
			$this->contactsManager,
			$this->createMock(IURLGenerator::class),
			$this->createMock(IUserManager::class),
		);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->trustedServers = $this->createMock(TrustedServers::class);
		$this->searchResult = new SearchResult();
	}

	public function instantiatePlugin() {
		$user = $this->createMock(IUser::class);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('admin');
		$userSession = $this->createMock(IUserSession::class);
		$userSession->expects($this->any())
			->method('getUser')
			->willReturn($user);
		$this->plugin = new RemotePlugin($this->contactsManager, $this->cloudIdManager, $this->config, $this->userManager, $userSession, $this->appConfig, $this->trustedServers);
	}

	/**
	 *
	 * @param string $searchTerm
	 * @param array $contacts
	 * @param bool $shareeEnumeration
	 * @param array $expected
	 * @param bool $exactIdMatch
	 * @param bool $reachedEnd
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataGetRemote')]
	public function testSearch($searchTerm, array $contacts, $shareeEnumeration, array $expected, $exactIdMatch, $reachedEnd): void {
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

		$this->instantiatePlugin();

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

		$this->assertSame($exactIdMatch, $this->searchResult->hasExactIdMatch(new SearchResultType('remotes')));
		$this->assertEquals($expected, $result);
		$this->assertSame($reachedEnd, $moreResults);
	}

	/**
	 *
	 * @param string $remote
	 * @param string $expectedUser
	 * @param string $expectedUrl
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataTestSplitUserRemote')]
	public function testSplitUserRemote($remote, $expectedUser, $expectedUrl): void {
		$this->instantiatePlugin();

		$this->contactsManager->expects($this->any())
			->method('search')
			->willReturn([]);

		[$remoteUser, $remoteUrl] = $this->plugin->splitUserRemote($remote);
		$this->assertSame($expectedUser, $remoteUser);
		$this->assertSame($expectedUrl, $remoteUrl);
	}

	/**
	 * @param string $id
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataTestSplitUserRemoteError')]
	public function testSplitUserRemoteError($id): void {
		$this->expectException(\Exception::class);

		$this->instantiatePlugin();
		$this->plugin->splitUserRemote($id);
	}

	public function testTrustedServerMetadata(): void {
		$this->config->expects($this->any())
			->method('getAppValue')
			->willReturnCallback(
				function ($appName, $key, $default) {
					if ($appName === 'core' && $key === 'shareapi_allow_share_dialog_user_enumeration') {
						return 'yes';
					}
					return $default;
				}
			);

		$this->trustedServers->expects($this->any())
			->method('isTrustedServer')
			->willReturnCallback(function ($serverUrl) {
				return $serverUrl === 'trustedserver.com';
			});

		$this->instantiatePlugin();

		$this->contactsManager->expects($this->any())
			->method('search')
			->willReturn([]);

		$this->plugin->search('test@trustedserver.com', 2, 0, $this->searchResult);
		$result = $this->searchResult->asArray();

		$this->assertNotEmpty($result['exact']['remotes']);
		$this->assertTrue($result['exact']['remotes'][0]['value']['isTrustedServer']);
	}

	public function testEmailSearchInContacts(): void {
		$this->config->expects($this->any())
			->method('getAppValue')
			->willReturnCallback(
				function ($appName, $key, $default) {
					if ($appName === 'core' && $key === 'shareapi_allow_share_dialog_user_enumeration') {
						return 'yes';
					}
					return $default;
				}
			);

		$this->trustedServers->expects($this->any())
			->method('isTrustedServer')
			->willReturnCallback(function ($serverUrl) {
				return $serverUrl === 'trustedserver.com';
			});

		$this->instantiatePlugin();

		$this->contactsManager->expects($this->once())
			->method('search')
			->with('john@gmail.com', ['CLOUD', 'FN', 'EMAIL'])
			->willReturn([
				[
					'FN' => 'John Doe',
					'EMAIL' => 'john@gmail.com',
					'CLOUD' => 'john@trustedserver.com',
					'UID' => 'john-contact-id'
				]
			]);

		$this->plugin->search('john@gmail.com', 2, 0, $this->searchResult);
		$result = $this->searchResult->asArray();

		$this->assertNotEmpty($result['exact']['remotes']);
		$this->assertEquals('john@trustedserver.com', $result['exact']['remotes'][0]['value']['shareWith']);
		$this->assertTrue($result['exact']['remotes'][0]['value']['isTrustedServer']);
	}

	public static function dataGetRemote() {
		return [
			['test', [], true, ['remotes' => [], 'exact' => ['remotes' => []]], false, true],
			['test', [], false, ['remotes' => [], 'exact' => ['remotes' => []]], false, true],
			[
				'test@remote',
				[],
				true,
				['remotes' => [], 'exact' => ['remotes' => [['label' => 'test (remote)', 'value' => ['shareType' => IShare::TYPE_REMOTE, 'shareWith' => 'test@remote', 'server' => 'remote', 'isTrustedServer' => false], 'uuid' => 'test', 'name' => 'test']]]],
				false,
				true,
			],
			[
				'test@remote',
				[],
				false,
				['remotes' => [], 'exact' => ['remotes' => [['label' => 'test (remote)', 'value' => ['shareType' => IShare::TYPE_REMOTE, 'shareWith' => 'test@remote', 'server' => 'remote', 'isTrustedServer' => false], 'uuid' => 'test', 'name' => 'test']]]],
				false,
				true,
			],
			[
				'test',
				[
					[
						'UID' => 'uid',
						'FN' => 'User3 @ Localhost',
					],
					[
						'UID' => 'uid',
						'FN' => 'User2 @ Localhost',
						'CLOUD' => [
						],
					],
					[
						'UID' => 'uid1',
						'FN' => 'User @ Localhost',
						'CLOUD' => [
							'username@localhost',
						],
					],
				],
				true,
				['remotes' => [['name' => 'User @ Localhost', 'label' => 'User @ Localhost (username@localhost)', 'uuid' => 'uid1', 'type' => '', 'value' => ['shareType' => IShare::TYPE_REMOTE, 'shareWith' => 'username@localhost', 'server' => 'localhost', 'isTrustedServer' => false]]], 'exact' => ['remotes' => []]],
				false,
				true,
			],
			[
				'test',
				[
					[
						'UID' => 'uid',
						'FN' => 'User3 @ Localhost',
					],
					[
						'UID' => 'uid',
						'FN' => 'User2 @ Localhost',
						'CLOUD' => [
						],
					],
					[
						'UID' => 'uid',
						'FN' => 'User @ Localhost',
						'CLOUD' => [
							'username@localhost',
						],
					],
				],
				false,
				['remotes' => [], 'exact' => ['remotes' => []]],
				false,
				true,
			],
			[
				'test@remote',
				[
					[
						'UID' => 'uid',
						'FN' => 'User3 @ Localhost',
					],
					[
						'UID' => 'uid',
						'FN' => 'User2 @ Localhost',
						'CLOUD' => [
						],
					],
					[
						'UID' => 'uid',
						'FN' => 'User @ Localhost',
						'CLOUD' => [
							'username@localhost',
						],
					],
				],
				true,
				['remotes' => [['name' => 'User @ Localhost', 'label' => 'User @ Localhost (username@localhost)', 'uuid' => 'uid', 'type' => '', 'value' => ['shareType' => IShare::TYPE_REMOTE, 'shareWith' => 'username@localhost', 'server' => 'localhost', 'isTrustedServer' => false]]], 'exact' => ['remotes' => [['label' => 'test (remote)', 'value' => ['shareType' => IShare::TYPE_REMOTE, 'shareWith' => 'test@remote', 'server' => 'remote', 'isTrustedServer' => false], 'uuid' => 'test', 'name' => 'test']]]],
				false,
				true,
			],
			[
				'test@remote',
				[
					[
						'UID' => 'uid',
						'FN' => 'User3 @ Localhost',
					],
					[
						'UID' => 'uid',
						'FN' => 'User2 @ Localhost',
						'CLOUD' => [
						],
					],
					[
						'UID' => 'uid',
						'FN' => 'User @ Localhost',
						'CLOUD' => [
							'username@localhost',
						],
					],
				],
				false,
				['remotes' => [], 'exact' => ['remotes' => [['label' => 'test (remote)', 'value' => ['shareType' => IShare::TYPE_REMOTE, 'shareWith' => 'test@remote', 'server' => 'remote', 'isTrustedServer' => false], 'uuid' => 'test', 'name' => 'test']]]],
				false,
				true,
			],
			[
				'username@localhost',
				[
					[
						'UID' => 'uid3',
						'FN' => 'User3 @ Localhost',
					],
					[
						'UID' => '2',
						'FN' => 'User2 @ Localhost',
						'CLOUD' => [
						],
					],
					[
						'UID' => 'uid1',
						'FN' => 'User @ Localhost',
						'CLOUD' => [
							'username@localhost',
						],
					],
				],
				true,
				['remotes' => [], 'exact' => ['remotes' => [['name' => 'User @ Localhost', 'label' => 'User @ Localhost (username@localhost)', 'uuid' => 'uid1', 'type' => '', 'value' => ['shareType' => IShare::TYPE_REMOTE, 'shareWith' => 'username@localhost', 'server' => 'localhost', 'isTrustedServer' => false]]]]],
				true,
				true,
			],
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
						'CLOUD' => [
						],
					],
					[
						'UID' => 'uid1',
						'FN' => 'User @ Localhost',
						'CLOUD' => [
							'username@localhost',
						],
					],
				],
				false,
				['remotes' => [], 'exact' => ['remotes' => [['name' => 'User @ Localhost', 'label' => 'User @ Localhost (username@localhost)', 'uuid' => 'uid1', 'type' => '', 'value' => ['shareType' => IShare::TYPE_REMOTE, 'shareWith' => 'username@localhost', 'server' => 'localhost', 'isTrustedServer' => false]]]]],
				true,
				true,
			],
			// contact with space
			[
				'user name@localhost',
				[
					[
						'UID' => 'uid1',
						'FN' => 'User3 @ Localhost',
					],
					[
						'UID' => 'uid2',
						'FN' => 'User2 @ Localhost',
						'CLOUD' => [
						],
					],
					[
						'UID' => 'uid3',
						'FN' => 'User Name @ Localhost',
						'CLOUD' => [
							'user name@localhost',
						],
					],
				],
				false,
				['remotes' => [], 'exact' => ['remotes' => [['name' => 'User Name @ Localhost', 'label' => 'User Name @ Localhost (user name@localhost)', 'uuid' => 'uid3', 'type' => '', 'value' => ['shareType' => IShare::TYPE_REMOTE, 'shareWith' => 'user name@localhost', 'server' => 'localhost', 'isTrustedServer' => false]]]]],
				true,
				true,
			],
			// remote with space, no contact
			[
				'user space@remote',
				[
					[
						'UID' => 'uid3',
						'FN' => 'User3 @ Localhost',
					],
					[
						'UID' => 'uid2',
						'FN' => 'User2 @ Localhost',
						'CLOUD' => [
						],
					],
					[
						'UID' => 'uid1',
						'FN' => 'User @ Localhost',
						'CLOUD' => [
							'username@localhost',
						],
					],
				],
				false,
				['remotes' => [], 'exact' => ['remotes' => [['label' => 'user space (remote)', 'value' => ['shareType' => IShare::TYPE_REMOTE, 'shareWith' => 'user space@remote', 'server' => 'remote', 'isTrustedServer' => false], 'uuid' => 'user space', 'name' => 'user space']]]],
				false,
				true,
			],
		];
	}

	public static function dataTestSplitUserRemote(): array {
		$userPrefix = ['user@name', 'username'];
		$protocols = ['', 'http://', 'https://'];
		$remotes = [
			'localhost',
			'local.host',
			'dev.local.host',
			'dev.local.host/path',
			'dev.local.host/at@inpath',
			'127.0.0.1',
			'::1',
			'::192.0.2.128',
			'::192.0.2.128/at@inpath',
		];

		$testCases = [];
		foreach ($userPrefix as $user) {
			foreach ($remotes as $remote) {
				foreach ($protocols as $protocol) {
					$baseUrl = $user . '@' . $protocol . $remote;

					if ($protocol === 'https://') {
						// https:// protocol is not expected in the final result
						$protocol = '';
					}

					$testCases[] = [$baseUrl, $user, $protocol . $remote];
					$testCases[] = [$baseUrl . '/', $user, $protocol . $remote];
					$testCases[] = [$baseUrl . '/index.php', $user, $protocol . $remote];
					$testCases[] = [$baseUrl . '/index.php/s/token', $user, $protocol . $remote];
				}
			}
		}
		return $testCases;
	}

	public static function dataTestSplitUserRemoteError(): array {
		return [
			// Invalid path
			['user@'],

			// Invalid user
			['@server'],
			['us/er@server'],
			['us:er@server'],

			// Invalid splitting
			['user'],
			[''],
			['us/erserver'],
			['us:erserver'],
		];
	}
}
