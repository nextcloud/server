<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Collaboration\Collaborators;

use OC\Collaboration\Collaborators\LookupPlugin;
use OC\Federation\CloudId;
use OCP\Collaboration\Collaborators\ISearchResult;
use OCP\Collaboration\Collaborators\SearchResultType;
use OCP\Federation\ICloudId;
use OCP\Federation\ICloudIdManager;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class LookupPluginTest extends TestCase {
	/** @var IConfig|MockObject */
	protected $config;
	/** @var IClientService|MockObject */
	protected $clientService;
	/** @var IUserSession|MockObject */
	protected $userSession;
	/** @var ICloudIdManager|MockObject */
	protected $cloudIdManager;
	/** @var LookupPlugin */
	protected $plugin;
	/** @var LoggerInterface|MockObject */
	protected $logger;

	protected function setUp(): void {
		parent::setUp();

		$this->userSession = $this->createMock(IUserSession::class);
		$this->cloudIdManager = $this->createMock(ICloudIdManager::class);
		$this->config = $this->createMock(IConfig::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->clientService = $this->createMock(IClientService::class);
		$cloudId = $this->createMock(ICloudId::class);
		$cloudId->expects($this->any())->method('getRemote')->willReturn('myNextcloud.net');
		$user = $this->createMock(IUser::class);
		$user->expects($this->any())->method('getCloudId')->willReturn('user@myNextcloud.net');
		$this->userSession->expects($this->any())->method('getUser')
			->willReturn($user);
		$this->cloudIdManager->expects($this->any())->method('resolveCloudId')
			->willReturnCallback(function ($cloudId) {
				if ($cloudId === 'user@myNextcloud.net') {
					return new CloudId('user@myNextcloud.net', 'user', 'myNextcloud.net');
				}
				return new CloudId('user@someNextcloud.net', 'user', 'someNextcloud.net');
			});


		$this->plugin = new LookupPlugin(
			$this->config,
			$this->clientService,
			$this->userSession,
			$this->cloudIdManager,
			$this->logger
		);
	}

	public function testSearchNoLookupServerURI(): void {
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('files_sharing', 'lookupServerEnabled', 'yes')
			->willReturn('yes');
		$this->config->expects($this->exactly(2))
			->method('getSystemValueBool')
			->withConsecutive(
				['gs.enabled', false],
				['has_internet_connection', true],
			)->willReturnOnConsecutiveCalls(
				false,
				true,
			);

		$this->config->expects($this->once())
			->method('getSystemValueString')
			->with('lookup_server', 'https://lookup.nextcloud.com')
			->willReturn('');

		$this->clientService->expects($this->never())
			->method('newClient');

		/** @var ISearchResult|MockObject $searchResult */
		$searchResult = $this->createMock(ISearchResult::class);

		$this->plugin->search('foobar', 10, 0, $searchResult);
	}

	public function testSearchNoInternet(): void {
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('files_sharing', 'lookupServerEnabled', 'yes')
			->willReturn('yes');
		$this->config->expects($this->exactly(2))
			->method('getSystemValueBool')
			->withConsecutive(
				['gs.enabled', false],
				['has_internet_connection', true],
			)->willReturnOnConsecutiveCalls(
				false,
				false,
			);

		$this->clientService->expects($this->never())
			->method('newClient');

		/** @var ISearchResult|MockObject $searchResult */
		$searchResult = $this->createMock(ISearchResult::class);

		$this->plugin->search('foobar', 10, 0, $searchResult);
	}

	/**
	 * @dataProvider searchDataProvider
	 * @param array $searchParams
	 */
	public function testSearch(array $searchParams): void {
		$type = new SearchResultType('lookup');

		/** @var ISearchResult|MockObject $searchResult */
		$searchResult = $this->createMock(ISearchResult::class);
		$searchResult->expects($this->once())
			->method('addResultSet')
			->with($type, $searchParams['expectedResult'], []);

		$this->config->expects($this->once())
			->method('getAppValue')
			->with('files_sharing', 'lookupServerEnabled', 'yes')
			->willReturn('yes');
		$this->config->expects($this->exactly(2))
			->method('getSystemValueBool')
			->withConsecutive(
				['gs.enabled', false],
				['has_internet_connection', true],
			)->willReturnOnConsecutiveCalls(
				false,
				true,
			);

		$this->config->expects($this->once())
			->method('getSystemValueString')
			->with('lookup_server', 'https://lookup.nextcloud.com')
			->willReturn($searchParams['server']);

		$response = $this->createMock(IResponse::class);
		$response->expects($this->once())
			->method('getBody')
			->willReturn(json_encode($searchParams['resultBody']));

		$client = $this->createMock(IClient::class);
		$client->expects($this->once())
			->method('get')
			->willReturnCallback(function ($url) use ($searchParams, $response) {
				$this->assertSame(strpos($url, $searchParams['server'] . '/users?search='), 0);
				$this->assertNotFalse(strpos($url, urlencode($searchParams['search'])));
				return $response;
			});

		$this->clientService->expects($this->once())
			->method('newClient')
			->willReturn($client);

		$moreResults = $this->plugin->search(
			$searchParams['search'],
			$searchParams['limit'],
			$searchParams['offset'],
			$searchResult
		);

		$this->assertFalse($moreResults);
	}


	/**
	 * @dataProvider dataSearchEnableDisableLookupServer
	 * @param array $searchParams
	 * @param bool $GSEnabled
	 * @param bool $LookupEnabled
	 */
	public function testSearchEnableDisableLookupServer(array $searchParams, $GSEnabled, $LookupEnabled): void {
		$type = new SearchResultType('lookup');

		/** @var ISearchResult|MockObject $searchResult */
		$searchResult = $this->createMock(ISearchResult::class);

		$this->config->expects($this->once())
			->method('getAppValue')
			->with('files_sharing', 'lookupServerEnabled', 'yes')
			->willReturn($LookupEnabled ? 'yes' : 'no');
		if ($GSEnabled || $LookupEnabled) {
			$searchResult->expects($this->once())
				->method('addResultSet')
				->with($type, $searchParams['expectedResult'], []);

			$this->config->expects($this->exactly(2))
				->method('getSystemValueBool')
				->withConsecutive(
					['gs.enabled', false],
					['has_internet_connection', true],
				)->willReturnOnConsecutiveCalls(
					$GSEnabled,
					true,
				);
			$this->config->expects($this->once())
				->method('getSystemValueString')
				->with('lookup_server', 'https://lookup.nextcloud.com')
				->willReturn($searchParams['server']);

			$response = $this->createMock(IResponse::class);
			$response->expects($this->once())
				->method('getBody')
				->willReturn(json_encode($searchParams['resultBody']));

			$client = $this->createMock(IClient::class);
			$client->expects($this->once())
				->method('get')
				->willReturnCallback(function ($url) use ($searchParams, $response) {
					$this->assertSame(strpos($url, $searchParams['server'] . '/users?search='), 0);
					$this->assertNotFalse(strpos($url, urlencode($searchParams['search'])));
					return $response;
				});

			$this->clientService->expects($this->once())
				->method('newClient')
				->willReturn($client);
		} else {
			$searchResult->expects($this->never())->method('addResultSet');
			$this->config->expects($this->exactly(2))
				->method('getSystemValueBool')
				->withConsecutive(
					['gs.enabled', false],
					['has_internet_connection', true],
				)->willReturnOnConsecutiveCalls(
					$GSEnabled,
					true,
				);
		}
		$moreResults = $this->plugin->search(
			$searchParams['search'],
			$searchParams['limit'],
			$searchParams['offset'],
			$searchResult
		);

		$this->assertFalse($moreResults);
	}


	public function testSearchLookupServerDisabled(): void {
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('files_sharing', 'lookupServerEnabled', 'yes')
			->willReturn('no');

		/** @var ISearchResult|MockObject $searchResult */
		$searchResult = $this->createMock(ISearchResult::class);
		$searchResult->expects($this->never())
			->method('addResultSet');
		$searchResult->expects($this->never())
			->method('markExactIdMatch');

		$this->assertFalse($this->plugin->search('irr', 10, 0, $searchResult));
	}

	public function dataSearchEnableDisableLookupServer() {
		$fedIDs = [
			'foo@enceladus.moon',
			'foobar@enceladus.moon',
			'foongus@enceladus.moon',
		];

		return [
			[[
				'search' => 'foo',
				'limit' => 10,
				'offset' => 0,
				'server' => 'https://lookup.example.io',
				'resultBody' => [
					['federationId' => $fedIDs[0]],
					['federationId' => $fedIDs[1]],
					['federationId' => $fedIDs[2]],
				],
				'expectedResult' => [
					[
						'label' => $fedIDs[0],
						'value' => [
							'shareType' => IShare::TYPE_REMOTE,
							'globalScale' => true,
							'shareWith' => $fedIDs[0]
						],
						'extra' => ['federationId' => $fedIDs[0]],
					],
					[
						'label' => $fedIDs[1],
						'value' => [
							'shareType' => IShare::TYPE_REMOTE,
							'globalScale' => true,
							'shareWith' => $fedIDs[1]
						],
						'extra' => ['federationId' => $fedIDs[1]],
					],
					[
						'label' => $fedIDs[2],
						'value' => [
							'shareType' => IShare::TYPE_REMOTE,
							'globalScale' => true,
							'shareWith' => $fedIDs[2]
						],
						'extra' => ['federationId' => $fedIDs[2]],
					],
				]
			],// GS , Lookup
				true, true
			],
			[[
				'search' => 'foo',
				'limit' => 10,
				'offset' => 0,
				'server' => 'https://lookup.example.io',
				'resultBody' => [
					['federationId' => $fedIDs[0]],
					['federationId' => $fedIDs[1]],
					['federationId' => $fedIDs[2]],
				],
				'expectedResult' => [
					[
						'label' => $fedIDs[0],
						'value' => [
							'shareType' => IShare::TYPE_REMOTE,
							'globalScale' => true,
							'shareWith' => $fedIDs[0]
						],
						'extra' => ['federationId' => $fedIDs[0]],
					],
					[
						'label' => $fedIDs[1],
						'value' => [
							'shareType' => IShare::TYPE_REMOTE,
							'globalScale' => true,
							'shareWith' => $fedIDs[1]
						],
						'extra' => ['federationId' => $fedIDs[1]],
					],
					[
						'label' => $fedIDs[2],
						'value' => [
							'shareType' => IShare::TYPE_REMOTE,
							'globalScale' => true,
							'shareWith' => $fedIDs[2]
						],
						'extra' => ['federationId' => $fedIDs[2]],
					],
				]
			],// GS , Lookup
				true, false
			],
			[[
				'search' => 'foo',
				'limit' => 10,
				'offset' => 0,
				'server' => 'https://lookup.example.io',
				'resultBody' => [
					['federationId' => $fedIDs[0]],
					['federationId' => $fedIDs[1]],
					['federationId' => $fedIDs[2]],
				],
				'expectedResult' => [
					[
						'label' => $fedIDs[0],
						'value' => [
							'shareType' => IShare::TYPE_REMOTE,
							'globalScale' => false,
							'shareWith' => $fedIDs[0]
						],
						'extra' => ['federationId' => $fedIDs[0]],
					],
					[
						'label' => $fedIDs[1],
						'value' => [
							'shareType' => IShare::TYPE_REMOTE,
							'globalScale' => false,
							'shareWith' => $fedIDs[1]
						],
						'extra' => ['federationId' => $fedIDs[1]],
					],
					[
						'label' => $fedIDs[2],
						'value' => [
							'shareType' => IShare::TYPE_REMOTE,
							'globalScale' => false,
							'shareWith' => $fedIDs[2]
						],
						'extra' => ['federationId' => $fedIDs[2]],
					],
				]
			],// GS , Lookup
				false, true
			],
			[[
				'search' => 'foo',
				'limit' => 10,
				'offset' => 0,
				'server' => 'https://lookup.example.io',
				'resultBody' => [
					['federationId' => $fedIDs[0]],
					['federationId' => $fedIDs[1]],
					['federationId' => $fedIDs[2]],
				],
				'expectedResult' => [
					[
						'label' => $fedIDs[0],
						'value' => [
							'shareType' => IShare::TYPE_REMOTE,
							'shareWith' => $fedIDs[0]
						],
						'extra' => ['federationId' => $fedIDs[0]],
					],
					[
						'label' => $fedIDs[1],
						'value' => [
							'shareType' => IShare::TYPE_REMOTE,
							'shareWith' => $fedIDs[1]
						],
						'extra' => ['federationId' => $fedIDs[1]],
					],
					[
						'label' => $fedIDs[2],
						'value' => [
							'shareType' => IShare::TYPE_REMOTE,
							'shareWith' => $fedIDs[2]
						],
						'extra' => ['federationId' => $fedIDs[2]],
					],
				]
			],// GS , Lookup
				false, false
			],
		];
	}

	public function searchDataProvider() {
		$fedIDs = [
			'foo@enceladus.moon',
			'foobar@enceladus.moon',
			'foongus@enceladus.moon',
		];

		return [
			// #0, standard search with results
			[[
				'search' => 'foo',
				'limit' => 10,
				'offset' => 0,
				'server' => 'https://lookup.example.io',
				'resultBody' => [
					['federationId' => $fedIDs[0]],
					['federationId' => $fedIDs[1]],
					['federationId' => $fedIDs[2]],
				],
				'expectedResult' => [
					[
						'label' => $fedIDs[0],
						'value' => [
							'shareType' => IShare::TYPE_REMOTE,
							'globalScale' => false,
							'shareWith' => $fedIDs[0]
						],
						'extra' => ['federationId' => $fedIDs[0]],
					],
					[
						'label' => $fedIDs[1],
						'value' => [
							'shareType' => IShare::TYPE_REMOTE,
							'globalScale' => false,
							'shareWith' => $fedIDs[1]
						],
						'extra' => ['federationId' => $fedIDs[1]],
					],
					[
						'label' => $fedIDs[2],
						'value' => [
							'shareType' => IShare::TYPE_REMOTE,
							'globalScale' => false,
							'shareWith' => $fedIDs[2]
						],
						'extra' => ['federationId' => $fedIDs[2]],
					],
				]
			]],
			// #1, search without results
			[[
				'search' => 'foo',
				'limit' => 10,
				'offset' => 0,
				'server' => 'https://lookup.example.io',
				'resultBody' => [],
				'expectedResult' => [],
			]],
		];
	}
}
