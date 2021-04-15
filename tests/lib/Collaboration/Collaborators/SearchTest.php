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

use OC\Collaboration\Collaborators\Search;
use OC\Collaboration\Collaborators\SearchResult;
use OCP\Collaboration\Collaborators\ISearch;
use OCP\Collaboration\Collaborators\ISearchPlugin;
use OCP\Collaboration\Collaborators\SearchResultType;
use OCP\IContainer;
use OCP\Share\IShare;
use Test\TestCase;

class SearchTest extends TestCase {
	/** @var  IContainer|\PHPUnit\Framework\MockObject\MockObject */
	protected $container;
	/** @var  ISearch */
	protected $search;

	protected function setUp(): void {
		parent::setUp();

		$this->container = $this->createMock(IContainer::class);

		$this->search = new Search($this->container);
	}

	/**
	 * @dataProvider dataSearchSharees
	 */
	public function testSearch(
		string $searchTerm,
		array $shareTypes,
		int $page,
		int $perPage,
		array $mockedUserResult,
		array $mockedGroupsResult,
		array $mockedRemotesResult,
		array $mockedMailResult,
		array $expected,
		bool $expectedMoreResults
	) {
		$searchResult = new SearchResult();

		$userPlugin = $this->createMock(ISearchPlugin::class);
		$userPlugin->expects($this->any())
			->method('search')
			->willReturnCallback(function () use ($searchResult, $mockedUserResult, $expectedMoreResults) {
				$type = new SearchResultType('users');
				$searchResult->addResultSet($type, $mockedUserResult);
				return $expectedMoreResults;
			});

		$groupPlugin = $this->createMock(ISearchPlugin::class);
		$groupPlugin->expects($this->any())
			->method('search')
			->willReturnCallback(function () use ($searchResult, $mockedGroupsResult, $expectedMoreResults) {
				$type = new SearchResultType('groups');
				$searchResult->addResultSet($type, $mockedGroupsResult);
				return $expectedMoreResults;
			});

		$remotePlugin = $this->createMock(ISearchPlugin::class);
		$remotePlugin->expects($this->any())
			->method('search')
			->willReturnCallback(function () use ($searchResult, $mockedRemotesResult, $expectedMoreResults) {
				if ($mockedRemotesResult !== null) {
					$type = new SearchResultType('remotes');
					$searchResult->addResultSet($type, $mockedRemotesResult['results'], $mockedRemotesResult['exact']);
					if ($mockedRemotesResult['exactIdMatch'] === true) {
						$searchResult->markExactIdMatch($type);
					}
				}
				return $expectedMoreResults;
			});

		$mailPlugin = $this->createMock(ISearchPlugin::class);
		$mailPlugin->expects($this->any())
			->method('search')
			->willReturnCallback(function () use ($searchResult, $mockedMailResult, $expectedMoreResults) {
				$type = new SearchResultType('emails');
				$searchResult->addResultSet($type, $mockedMailResult);
				return $expectedMoreResults;
			});

		$this->container->expects($this->any())
			->method('resolve')
			->willReturnCallback(function ($class) use ($searchResult, $userPlugin, $groupPlugin, $remotePlugin, $mailPlugin) {
				if ($class === SearchResult::class) {
					return $searchResult;
				} elseif ($class === $userPlugin) {
					return $userPlugin;
				} elseif ($class === $groupPlugin) {
					return $groupPlugin;
				} elseif ($class === $remotePlugin) {
					return $remotePlugin;
				} elseif ($class === $mailPlugin) {
					return $mailPlugin;
				}
				return null;
			});

		$this->search->registerPlugin(['shareType' => 'SHARE_TYPE_USER', 'class' => $userPlugin]);
		$this->search->registerPlugin(['shareType' => 'SHARE_TYPE_GROUP', 'class' => $groupPlugin]);
		$this->search->registerPlugin(['shareType' => 'SHARE_TYPE_REMOTE', 'class' => $remotePlugin]);
		$this->search->registerPlugin(['shareType' => 'SHARE_TYPE_EMAIL', 'class' => $mailPlugin]);

		[$results, $moreResults] = $this->search->search($searchTerm, $shareTypes, false, $perPage, $perPage * ($page - 1));

		$this->assertEquals($expected, $results);
		$this->assertSame($expectedMoreResults, $moreResults);
	}

	public function dataSearchSharees() {
		return [
			// #0
			[
				'test', [IShare::TYPE_USER, IShare::TYPE_GROUP, IShare::TYPE_REMOTE], 1, 2, [], [], ['results' => [], 'exact' => [], 'exactIdMatch' => false],
				[],
				[
					'exact' => ['users' => [], 'groups' => [], 'remotes' => []],
					'users' => [],
					'groups' => [],
					'remotes' => [],
				],
				false
			],
			// #1
			[
				'test', [IShare::TYPE_USER, IShare::TYPE_GROUP, IShare::TYPE_REMOTE], 1, 2, [], [], ['results' => [], 'exact' => [], 'exactIdMatch' => false],
				[],
				[
					'exact' => ['users' => [], 'groups' => [], 'remotes' => []],
					'users' => [],
					'groups' => [],
					'remotes' => [],
				],
				false
			],
			// #2
			[
				'test', [IShare::TYPE_USER, IShare::TYPE_GROUP, IShare::TYPE_REMOTE], 1, 2, [
					['label' => 'test One', 'value' => ['shareType' => IShare::TYPE_USER, 'shareWith' => 'test1']],
				], [
					['label' => 'testgroup1', 'value' => ['shareType' => IShare::TYPE_GROUP, 'shareWith' => 'testgroup1']],
				], [
					'results' => [['label' => 'testz@remote', 'value' => ['shareType' => IShare::TYPE_REMOTE, 'shareWith' => 'testz@remote']]], 'exact' => [], 'exactIdMatch' => false,
				],
				[],
				[
					'exact' => ['users' => [], 'groups' => [], 'remotes' => []],
					'users' => [
						['label' => 'test One', 'value' => ['shareType' => IShare::TYPE_USER, 'shareWith' => 'test1']],
					],
					'groups' => [
						['label' => 'testgroup1', 'value' => ['shareType' => IShare::TYPE_GROUP, 'shareWith' => 'testgroup1']],
					],
					'remotes' => [
						['label' => 'testz@remote', 'value' => ['shareType' => IShare::TYPE_REMOTE, 'shareWith' => 'testz@remote']],
					],
				], true,
			],
			// #3 No groups requested
			[
				'test', [IShare::TYPE_USER, IShare::TYPE_REMOTE], 1, 2, [
					['label' => 'test One', 'value' => ['shareType' => IShare::TYPE_USER, 'shareWith' => 'test1']],
				], [], [
					'results' => [['label' => 'testz@remote', 'value' => ['shareType' => IShare::TYPE_REMOTE, 'shareWith' => 'testz@remote']]], 'exact' => [], 'exactIdMatch' => false
				],
				[],
				[
					'exact' => ['users' => [], 'remotes' => []],
					'users' => [
						['label' => 'test One', 'value' => ['shareType' => IShare::TYPE_USER, 'shareWith' => 'test1']],
					],
					'remotes' => [
						['label' => 'testz@remote', 'value' => ['shareType' => IShare::TYPE_REMOTE, 'shareWith' => 'testz@remote']],
					],
				], false,
			],
			// #4 Share type restricted to user - Only one user
			[
				'test', [IShare::TYPE_USER], 1, 2, [
					['label' => 'test One', 'value' => ['shareType' => IShare::TYPE_USER, 'shareWith' => 'test1']],
				], [], [], [],
				[
					'exact' => ['users' => []],
					'users' => [
						['label' => 'test One', 'value' => ['shareType' => IShare::TYPE_USER, 'shareWith' => 'test1']],
					],
				], false,
			],
			// #5 Share type restricted to user - Multipage result
			[
				'test', [IShare::TYPE_USER], 1, 2, [
					['label' => 'test 1', 'value' => ['shareType' => IShare::TYPE_USER, 'shareWith' => 'test1']],
					['label' => 'test 2', 'value' => ['shareType' => IShare::TYPE_USER, 'shareWith' => 'test2']],
				], [], [], [],
				[
					'exact' => ['users' => []],
					'users' => [
						['label' => 'test 1', 'value' => ['shareType' => IShare::TYPE_USER, 'shareWith' => 'test1']],
						['label' => 'test 2', 'value' => ['shareType' => IShare::TYPE_USER, 'shareWith' => 'test2']],
					],
				], true,
			],
			// #6 Mail shares filtered out in favor of remote shares
			[
				'test', // search term
				[IShare::TYPE_USER, IShare::TYPE_GROUP, IShare::TYPE_REMOTE, IShare::TYPE_EMAIL], // plugins
				1, // page
				10, // per page
				[  // user result
					['label' => 'test One', 'value' => ['shareType' => IShare::TYPE_USER, 'shareWith' => 'test1']],
				],
				[  // group result
					['label' => 'testgroup1', 'value' => ['shareType' => IShare::TYPE_GROUP, 'shareWith' => 'testgroup1']],
				],
				[  // remote result
					'results' => [
						['label' => 'testz@remote.tld', 'uuid' => 'f3d78140-abcc-46df-b58d-c7cc1176aadf','value' => ['shareType' => IShare::TYPE_REMOTE, 'shareWith' => 'testz@remote.tld']]
					],
					'exact' => [],
					'exactIdMatch' => false,
				],
				[  //  mail result
					['label' => 'test Two', 'uuid' => 'b2321e9e-31af-43ac-a406-583fb26d1964', 'value' => ['shareType' => IShare::TYPE_EMAIL, 'shareWith' => 'test2@remote.tld']],
					['label' => 'test One', 'uuid' => 'f3d78140-abcc-46df-b58d-c7cc1176aadf', 'value' => ['shareType' => IShare::TYPE_EMAIL, 'shareWith' => 'testz@remote.tld']],
				],
				[  // expected result
					'exact' => ['users' => [], 'groups' => [], 'remotes' => [], 'emails' => []],
					'users' => [
						['label' => 'test One', 'value' => ['shareType' => IShare::TYPE_USER, 'shareWith' => 'test1']],
					],
					'groups' => [
						['label' => 'testgroup1', 'value' => ['shareType' => IShare::TYPE_GROUP, 'shareWith' => 'testgroup1']],
					],
					'remotes' => [
						['label' => 'testz@remote.tld', 'uuid' => 'f3d78140-abcc-46df-b58d-c7cc1176aadf', 'value' => ['shareType' => IShare::TYPE_REMOTE, 'shareWith' => 'testz@remote.tld']],
					],
					'emails' => [
						//  one passes, another is filtered out
						['label' => 'test Two', 'uuid' => 'b2321e9e-31af-43ac-a406-583fb26d1964', 'value' => ['shareType' => IShare::TYPE_EMAIL, 'shareWith' => 'test2@remote.tld']]
					]
				],
				false, // expected more results indicator
			],
		];
	}
}
