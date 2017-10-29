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


use OC\Collaboration\Collaborators\LookupPlugin;
use OCP\Collaboration\Collaborators\ISearchResult;
use OCP\Collaboration\Collaborators\SearchResultType;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IConfig;
use OCP\Share;
use Test\TestCase;

class LookupPluginTest extends TestCase {

	/** @var  IConfig|\PHPUnit_Framework_MockObject_MockObject */
	protected $config;
	/** @var  IClientService|\PHPUnit_Framework_MockObject_MockObject */
	protected $clientService;
	/** @var  LookupPlugin */
	protected $plugin;

	public function setUp() {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->clientService = $this->createMock(IClientService::class);

		$this->plugin = new LookupPlugin($this->config, $this->clientService);
	}

	/**
	 * @dataProvider searchDataProvider
	 * @param array $searchParams
	 */
	public function testSearch(array $searchParams) {
		$type = new SearchResultType('lookup');

		/** @var ISearchResult|\PHPUnit_Framework_MockObject_MockObject $searchResult */
		$searchResult = $this->createMock(ISearchResult::class);
		$searchResult->expects($this->once())
			->method('addResultSet')
			->with($type, $searchParams['expectedResult'], []);

		$this->config->expects($this->once())
			->method('getAppValue')
			->with('files_sharing', 'lookupServerEnabled', 'no')
			->willReturn('yes');
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('lookup_server', 'https://lookup.nextcloud.com')
			->willReturn($searchParams['server']);

		$response = $this->createMock(IResponse::class);
		$response->expects($this->once())
			->method('getBody')
			->willReturn(json_encode($searchParams['resultBody']));

		$client = $this->createMock(IClient::class);
		$client->expects($this->once())
			->method('get')
			->willReturnCallback(function($url) use ($searchParams, $response) {
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

	public function testSearchLookupServerDisabled() {
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('files_sharing', 'lookupServerEnabled', 'no')
			->willReturn('no');

		/** @var ISearchResult|\PHPUnit_Framework_MockObject_MockObject $searchResult */
		$searchResult = $this->createMock(ISearchResult::class);
		$searchResult->expects($this->never())
			->method('addResultSet');
		$searchResult->expects($this->never())
			->method('markExactIdMatch');

		$this->assertFalse($this->plugin->search('irr', 10, 0, $searchResult));
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
					[ 'federationId' => $fedIDs[0] ],
					[ 'federationId' => $fedIDs[1] ],
					[ 'federationId' => $fedIDs[2] ],
				],
				'expectedResult' => [
					[
						'label' => $fedIDs[0],
						'value' => [
							'shareType' => Share::SHARE_TYPE_REMOTE,
							'shareWith' => $fedIDs[0]
						],
						'extra' => ['federationId' => $fedIDs[0]],
					],
					[
						'label' => $fedIDs[1],
						'value' => [
							'shareType' => Share::SHARE_TYPE_REMOTE,
							'shareWith' => $fedIDs[1]
						],
						'extra' => ['federationId' => $fedIDs[1]],
					],
					[
						'label' => $fedIDs[2],
						'value' => [
							'shareType' => Share::SHARE_TYPE_REMOTE,
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
