<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Collaboration\Collaborators;

use OC\Collaboration\Collaborators\Search;
use OC\Collaboration\Collaborators\SearchResult;
use OCP\Collaboration\Collaborators\ISearch;
use OCP\Collaboration\Collaborators\SearchResultType;
use OCP\IContainer;
use Test\TestCase;

class SearchResultTest extends TestCase {
	/** @var IContainer|\PHPUnit\Framework\MockObject\MockObject */
	protected $container;
	/** @var ISearch */
	protected $search;

	protected function setUp(): void {
		parent::setUp();

		$this->container = $this->createMock(IContainer::class);

		$this->search = new Search($this->container);
	}

	public function dataAddResultSet() {
		return [
			[[], ['exact' => []]],
			[['users' => ['exact' => null, 'loose' => []]], ['exact' => ['users' => []], 'users' => []]],
			[['groups' => ['exact' => null, 'loose' => ['l1']]], ['exact' => ['groups' => []], 'groups' => ['l1']]],
			[['users' => ['exact' => ['e1'], 'loose' => []]], ['exact' => ['users' => ['e1']], 'users' => []]],
		];
	}

	/**
	 * @dataProvider dataAddResultSet
	 * @param array $toAdd
	 * @param array $expected
	 */
	public function testAddResultSet(array $toAdd, array $expected): void {
		$result = new SearchResult();

		foreach ($toAdd as $type => $results) {
			$result->addResultSet(new SearchResultType($type), $results['loose'], $results['exact']);
		}

		$this->assertEquals($expected, $result->asArray());
	}

	public function dataHasResult() {
		$result = ['value' => ['shareWith' => 'l1']];
		return [
			[[],'users', 'n1', false],
			[['users' => ['exact' => null,      'loose' => [$result]]], 'users',  'l1', true],
			[['users' => ['exact' => null,      'loose' => [$result]]], 'users',  'l2', false],
			[['users' => ['exact' => null,      'loose' => [$result]]], 'groups', 'l1', false],
			[['users' => ['exact' => [$result], 'loose' => []]],        'users',  'l1', true],
			[['users' => ['exact' => [$result], 'loose' => []]],        'users',  'l2', false],
			[['users' => ['exact' => [$result], 'loose' => []]],        'groups', 'l1', false],

		];
	}

	/**
	 * @dataProvider dataHasResult
	 * @param array $toAdd
	 * @param string $type
	 * @param string $id
	 * @param bool $expected
	 */
	public function testHasResult(array $toAdd, $type, $id, $expected): void {
		$result = new SearchResult();

		foreach ($toAdd as $addType => $results) {
			$result->addResultSet(new SearchResultType($addType), $results['loose'], $results['exact']);
		}

		$this->assertSame($expected, $result->hasResult(new SearchResultType($type), $id));
	}
}
