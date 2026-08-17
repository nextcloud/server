<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Controller;

use OC\Core\Controller\AutoCompleteController;
use OCP\Collaboration\AutoComplete\IManager;
use OCP\Collaboration\Collaborators\ISearch;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IRequest;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class AutoCompleteControllerTest extends TestCase {
	/** @var ISearch|MockObject */
	protected $collaboratorSearch;
	/** @var IManager|MockObject */
	protected $autoCompleteManager;
	/** @var IEventDispatcher|MockObject */
	protected $dispatcher;
	/** @var IRequest|MockObject */
	protected $request;
	/** @var IURLGenerator|MockObject */
	protected $urlGenerator;
	/** @var AutoCompleteController */
	protected $controller;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->collaboratorSearch = $this->createMock(ISearch::class);
		$this->autoCompleteManager = $this->createMock(IManager::class);
		$this->dispatcher = $this->createMock(IEventDispatcher::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);

		$this->controller = new AutoCompleteController(
			'core',
			$this->request,
			$this->collaboratorSearch,
			$this->autoCompleteManager,
			$this->dispatcher,
			$this->urlGenerator,
		);
	}

	public static function searchDataProvider(): array {
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

	#[\PHPUnit\Framework\Attributes\DataProvider('searchDataProvider')]
	public function testGet(array $searchResults, array $expected, string $searchTerm, ?string $itemType, ?string $itemId, ?string $sorter): void {
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
		$this->assertArrayNotHasKey('Link', $response->getHeaders());
	}

	public function testGetSetsLinkHeaderWhenMoreResultsExist(): void {
		$this->collaboratorSearch->expects($this->once())
			->method('search')
			->with('bob', [0], false, 2, 0)
			->willReturn([[
				'exact' => ['users' => [], 'robots' => []],
				'users' => [
					['label' => 'Bob Y.', 'value' => ['shareWith' => 'bob']],
					['label' => 'Bobby R.', 'value' => ['shareWith' => 'bobby']],
				],
			], true]);

		$this->request
			->method('getRequestUri')
			->willReturn('/ocs/v2.php/core/autocomplete/get?search=bob&limit=2');
		$this->urlGenerator
			->method('getAbsoluteURL')
			->with('/ocs/v2.php/core/autocomplete/get')
			->willReturn('https://cloud.example.com/ocs/v2.php/core/autocomplete/get');

		$response = $this->controller->get('bob', null, null, null, [0], 2, 0);
		$this->assertSame(
			'<https://cloud.example.com/ocs/v2.php/core/autocomplete/get?search=bob&shareTypes%5B0%5D=0&limit=2&offset=2>; rel="next"',
			$response->getHeaders()['Link']
		);
	}
}
