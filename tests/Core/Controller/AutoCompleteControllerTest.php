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

namespace Tests\Core\Controller;

use OC\Core\Controller\AutoCompleteController;
use OCP\Collaboration\AutoComplete\IManager;
use OCP\Collaboration\Collaborators\ISearch;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class AutoCompleteControllerTest extends TestCase {
	/** @var  ISearch|MockObject */
	protected $collaboratorSearch;
	/** @var  IManager|MockObject */
	protected $autoCompleteManager;
	/** @var  IEventDispatcher|MockObject */
	protected $dispatcher;
	/** @var  AutoCompleteController */
	protected $controller;

	protected function setUp(): void {
		parent::setUp();

		/** @var IRequest $request */
		$request = $this->createMock(IRequest::class);
		$this->collaboratorSearch = $this->createMock(ISearch::class);
		$this->autoCompleteManager = $this->createMock(IManager::class);
		$this->dispatcher = $this->createMock(IEventDispatcher::class);

		$this->controller = new AutoCompleteController(
			'core',
			$request,
			$this->collaboratorSearch,
			$this->autoCompleteManager,
			$this->dispatcher
		);
	}

	public function searchDataProvider() {
		return [
			[ #0 – regular search
				// searchResults
				[
					'exact' => [
						'users' => [],
						'robots' => [],
					],
					'users' => [
						['label' => 'Alice A.', 'value' => ['shareWith' => 'alice']],
						['label' => 'Bob Y.', 'value' => ['shareWith' => 'bob']],
					],
				],
				// expected
				[
					[ 'id' => 'alice', 'label' => 'Alice A.', 'icon' => '', 'source' => 'users', 'status' => '', 'subline' => '', 'shareWithDisplayNameUnique' => ''],
					[ 'id' => 'bob', 'label' => 'Bob Y.', 'icon' => '', 'source' => 'users', 'status' => '', 'subline' => '', 'shareWithDisplayNameUnique' => ''],
				],
				'',
				'files',
				'42',
				null
			],
			[ #1 – missing itemtype and id
				[
					'exact' => [
						'users' => [],
						'robots' => [],
					],
					'users' => [
						['label' => 'Alice A.', 'value' => ['shareWith' => 'alice']],
						['label' => 'Bob Y.', 'value' => ['shareWith' => 'bob']],
					],
				],
				// expected
				[
					[ 'id' => 'alice', 'label' => 'Alice A.', 'icon' => '', 'source' => 'users', 'status' => '', 'subline' => '', 'shareWithDisplayNameUnique' => ''],
					[ 'id' => 'bob', 'label' => 'Bob Y.', 'icon' => '', 'source' => 'users', 'status' => '', 'subline' => '', 'shareWithDisplayNameUnique' => ''],
				],
				'',
				null,
				null,
				null
			],
			[ #2 – with sorter
				[
					'exact' => [
						'users' => [],
						'robots' => [],
					],
					'users' => [
						['label' => 'Alice A.', 'value' => ['shareWith' => 'alice']],
						['label' => 'Bob Y.', 'value' => ['shareWith' => 'bob']],
					],
				],
				// expected
				[
					[ 'id' => 'alice', 'label' => 'Alice A.', 'icon' => '', 'source' => 'users', 'status' => '', 'subline' => '', 'shareWithDisplayNameUnique' => ''],
					[ 'id' => 'bob', 'label' => 'Bob Y.', 'icon' => '', 'source' => 'users', 'status' => '', 'subline' => '', 'shareWithDisplayNameUnique' => ''],
				],
				'',
				'files',
				'42',
				'karma|bus-factor'
			],
			[ #3 – exact Match
				[
					'exact' => [
						'users' => [
							['label' => 'Bob Y.', 'value' => ['shareWith' => 'bob']],
						],
						'robots' => [],
					],
					'users' => [
						['label' => 'Robert R.', 'value' => ['shareWith' => 'bobby']],
					],
				],
				[
					[ 'id' => 'bob', 'label' => 'Bob Y.', 'icon' => '', 'source' => 'users', 'status' => '', 'subline' => '', 'shareWithDisplayNameUnique' => ''],
					[ 'id' => 'bobby', 'label' => 'Robert R.', 'icon' => '', 'source' => 'users', 'status' => '', 'subline' => '', 'shareWithDisplayNameUnique' => ''],
				],
				'bob',
				'files',
				'42',
				null
			],
			[ #4 – with unique name
				[
					'exact' => [
						'users' => [],
						'robots' => [],
					],
					'users' => [
						['label' => 'Alice A.', 'value' => ['shareWith' => 'alice'], 'shareWithDisplayNameUnique' => 'alica@nextcloud.com'],
						['label' => 'Alice A.', 'value' => ['shareWith' => 'alicea'], 'shareWithDisplayNameUnique' => 'alicaa@nextcloud.com'],
					],
				],
				// expected
				[
					[ 'id' => 'alice', 'label' => 'Alice A.', 'icon' => '', 'source' => 'users', 'status' => '', 'subline' => '', 'shareWithDisplayNameUnique' => 'alica@nextcloud.com'],
					[ 'id' => 'alicea', 'label' => 'Alice A.', 'icon' => '', 'source' => 'users', 'status' => '', 'subline' => '', 'shareWithDisplayNameUnique' => 'alicaa@nextcloud.com'],
				],
				'',
				'files',
				'42',
				'karma|bus-factor'
			],
		];
	}

	/**
	 * @dataProvider searchDataProvider
	 */
	public function testGet(array $searchResults, array $expected, string $searchTerm, ?string $itemType, ?string $itemId, ?string $sorter) {
		$this->collaboratorSearch->expects($this->once())
			->method('search')
			->willReturn([$searchResults, false]);

		$runSorterFrequency = $sorter === null ? $this->never() : $this->once();
		$this->autoCompleteManager->expects($runSorterFrequency)
			->method('runSorters');

		$response = $this->controller->get($searchTerm, $itemType, $itemId, $sorter);

		$list = $response->getData();
		$this->assertEquals($expected, $list);	// has better error output…
		$this->assertSame($expected, $list);
	}
}
